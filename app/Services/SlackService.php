<?php

namespace App\Services;

use App\Enum\GitHubEnum;
use App\Enum\ProgressEnum;
use App\Enum\TagEnum;
use App\Helpers\AWSHelper;
use App\Helpers\GitHubHelper;
use App\Helpers\ValidationHelper;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

class SlackService
{
    use ConsoleBaseTrait, ConsoleUITrait;

    /**
     * Will select appropriate Slack channel to notify
     * - production channel: notify to the team, the manager  (required: env > SLACK_CHANNEL)
     * - develop and test channel: notify to the developer (required: env > SLACK_CHANNEL_DEV)
     * - default will return: SLACK_CHANNEL_DEV
     *
     * @return string|null
     */
    private static function selectSlackChannel(): ?string
    {
        // myops | testing
        if (getenv('REPOSITORY') === GitHubEnum::MYOPS) {
            return getenv('SLACK_CHANNEL_DEV'); // END
        }
        // database-utils
        if (getenv('SLACK_CHANNEL') && getenv('REPOSITORY') === GitHubEnum::ENGAGE_DATABASE_UTILS) {
            return getenv('SLACK_CHANNEL'); // END
        }
        // master branches
        if (getenv('SLACK_CHANNEL') && getenv('BRANCH') === GitHubEnum::MASTER) {
            return getenv('SLACK_CHANNEL'); // END
        }
        // in case don't config SLACK_CHANNEL, will fall to default below here for master branch
        //    branches: staging, develop, ticket's branches
        return getenv('SLACK_CHANNEL_DEV'); // END
    }

    /**
     * Mode 1: command line
     * @return void
     */
    public static function sendMessageConsole(): void
    {
        self::sendMessage(self::arg(1), getenv('REPOSITORY'), getenv('BRANCH'),
            getenv('SLACK_BOT_TOKEN'), self::selectSlackChannel());
    }

    /**
     * required these envs:  DEVICE, REPOSITORY
     * format: <app> slack-progress sub-command <additional message>
     * @return void
     */
    public static function sendMessageProgressConsole(): void
    {
        // validate
        ValidationHelper::validateSubCommandOrParam1('sub-command-of-progress', ProgressEnum::SUPPORT_SUB_COMMANDS);
        // handle
        switch (self::arg(1)) {
            case ProgressEnum::START:
                $message = trim(sprintf("%s starts to build the project %s %s", getenv('DEVICE'),
                    GitHubHelper::getRepositoryInfoByName(getenv('REPOSITORY'))->getFamilyName(), self::arg(2)));
                self::sendMessage($message, getenv('REPOSITORY'), getenv('BRANCH'),
                    getenv('SLACK_BOT_TOKEN'), self::selectSlackChannel());
                break;
            case ProgressEnum::FINISH:
                $message = trim(sprintf("%s just finished building and deploying the project %s %s", getenv('DEVICE'),
                    GitHubHelper::getRepositoryInfoByName(getenv('REPOSITORY'))->getFamilyName(), self::arg(2)));
                self::sendMessage($message, getenv('REPOSITORY'), getenv('BRANCH'),
                    getenv('SLACK_BOT_TOKEN'), self::selectSlackChannel());
                break;
            default:
                break;
        }
    }

    /**
     * - Mode 2: To use internal MyOps application:
     *    - call with custom parameters
     *    - require AWS credential have access to env-ops (Secret Manager)
     * @param string|null $customMessage
     * @param string $customRepository
     * @param string $customBranch
     * @return void
     */
    public static function sendMessageInternal(string $customMessage = null, string $customRepository = 'custom_repository',
                                               string $customBranch = 'custom_branch'): void
    {

        self::sendMessage($customMessage, $customRepository, $customBranch,
            AWSHelper::getValueEnvOpsSecretManager('SLACK_BOT_TOKEN'),
            AWSHelper::getValueEnvOpsSecretManager('SLACK_CHANNEL_DEV')
        );
    }


    /**
     * @param string|null $message
     * @param string|null $repository
     * @param string|null $branch
     * @param string|null $slackBotToken
     * @param string|null $slackChannel
     * @return void
     */
    private static function sendMessage(string $message = null, string $repository = null, string $branch = null,
                                        string $slackBotToken = null, string $slackChannel = null): void
    {
        // validate
        if (!$message || !$repository || !$branch || !$slackBotToken || !$slackChannel) {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)
                ->print("missing a MESSAGE or a BRANCH or REPOSITORY or SLACK_BOT_TOKEN or SLACK_CHANNEL");
            exit(); // END
        }
        // handle
        $slackUrl = "https://slack.com/api/chat.postMessage";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $slackUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [sprintf("Authorization: Bearer %s", $slackBotToken)]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            "channel" => $slackChannel, "text" => sprintf("[%s | %s] %s", $repository, $branch, $message),
        ]));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // Suppress output
        $response = curl_exec($curl);
        if (!$response) {
            self::LineTag(TagEnum::ERROR)->print(curl_error($curl));
        } else {
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($responseCode === 200) {
                if (json_decode($response, true)['ok']) {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::SUCCESS])->print("Your message have sent successfully | Slack status is OK | HTTP code is $responseCode");
                } else {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print(json_decode($response, true)['error'] . " | Slack status is NO | HTTP code is $responseCode");
                }
            } else {
                self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print("Sending message has got an error | HTTP code is $responseCode");
            }
        }
    }
}
