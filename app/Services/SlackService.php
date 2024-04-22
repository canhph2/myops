<?php

namespace App\Services;

use App\Enum\GitHubEnum;
use App\Enum\TagEnum;
use App\Helpers\AWSHelper;
use App\Traits\ConsoleUITrait;

class SlackService
{
    use ConsoleUITrait;

    /**
     * select appropriate Slack channel to notify
     * - production channel: notify to the team, the manager  (required: env > SLACK_CHANNEL_PRODUCTION)
     * - develop and test channel: notify to the developer (required: env > SLACK_CHANNEL)
     * - default will return: SLACK_CHANNEL
     *
     * @return string|null
     */
    private static function selectSlackChannel(): ?string
    {
        // myops | testing
        if (getenv('REPOSITORY') === GitHubEnum::MYOPS) {
            return getenv('SLACK_CHANNEL'); // END
        }
        // database-utils
        if (getenv('SLACK_CHANNEL_PRODUCTION') && getenv('REPOSITORY') === GitHubEnum::ENGAGE_DATABASE_UTILS) {
            return getenv('SLACK_CHANNEL_PRODUCTION'); // END
        }
        // master branches
        if (getenv('SLACK_CHANNEL_PRODUCTION') && getenv('BRANCH') === GitHubEnum::MASTER) {
            return getenv('SLACK_CHANNEL_PRODUCTION'); // END
        }
        //    in case don't config SLACK_CHANNEL_PRODUCTION, will fall to default below here for master branch
        // branches: staging, develop, ticket's branches
        return getenv('SLACK_CHANNEL'); // END
    }

    /**
     *  //todo check use internal
     * @return void
     */
    public static function sendMessage()
    {
        // === validate ===
        //    validate a message
        $message = self::args()->arg1;
        if (!$message) {
            self::LineTag(TagEnum::ERROR)->print("missing a MESSAGE");
            exit(); // END
        }
        //    validate env vars
        $repository = getenv('REPOSITORY');
        $branch = getenv('BRANCH');
        $slackBotToken = getenv('SLACK_BOT_TOKEN');
        $slackChannel = self::selectSlackChannel();
        if (!$repository || !$branch || !$slackBotToken || !$slackChannel) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->print("missing a BRANCH or REPOSITORY or SLACK_BOT_TOKEN or SLACK_CHANNEL");
            exit(); // END
        }

        // === handle ===
        $slackUrl = "https://slack.com/api/chat.postMessage";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $slackUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [sprintf("Authorization: Bearer %s", $slackBotToken)]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            "channel" => $slackChannel,
            "text" => sprintf("[%s] [%s] > %s", $repository, $branch, $message),
        ]));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // Suppress output
        $response = curl_exec($curl);
        if (!$response) {
            self::LineTag(TagEnum::ERROR)->print(curl_error($curl));
        } else {
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($responseCode === 200) {
                if (json_decode($response, true)['ok']) {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::SUCCESS])->print("Message sent successfully | Slack status OK | HTTP code $responseCode");
                } else {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print(json_decode($response, true)['error'] . " | Slack status NO | HTTP code $responseCode");
                }
            } else {
                self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print("Error sending message | HTTP code $responseCode");
            }
        }
    }

    /**
     * required AWS credential have access to env-ops (Secret Manager)
     * use internal lib
     * @return void
     */
    public static function sendMessageInternalUsing(
        string $message,
        string $repository = 'unknown_repository',
        string $branch = 'unknown_branch'
    )
    {
        // handle
        //    prepare envs
        putenv('REPOSITORY=' . $repository);
        putenv('BRANCH=' . $branch);
        putenv('SLACK_BOT_TOKEN=' . AWSHelper::getValueEnvOpsSecretManager('SLACK_BOT_TOKEN'));
        putenv('SLACK_CHANNEL=' . AWSHelper::getValueEnvOpsSecretManager('SLACK_CHANNEL'));
        self::sendMessage(['script path', 'slack', $message]);
    }
}
