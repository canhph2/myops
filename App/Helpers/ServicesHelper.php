<?php

namespace App\Helpers;

class ServicesHelper
{
    public static function SlackMessage(array $argv)
    {
        // === validate ===
        //    validate a message
        $message = $argv[2] ?? null;
        if (!$message) {
            TextHelper::messageERROR("missing a MESSAGE");
            exit(); // END
        }
        //    validate env vars
        $repository = getenv('REPOSITORY');
        $branch = getenv('BRANCH');
        $slackBotToken = getenv('SLACK_BOT_TOKEN');
        $slackChannel = getenv('SLACK_CHANNEL');
        if (!$repository || !$branch || !$slackBotToken || !$slackChannel) {
            TextHelper::messageERROR("[ENV] missing a BRANCH or REPOSITORY or SLACK_BOT_TOKEN or SLACK_CHANNEL");
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
            TextHelper::messageERROR(curl_error($curl));
        } else {
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($responseCode === 200) {
                if (json_decode($response, true)['ok']) {
                    TextHelper::messageSUCCESS("Message sent successfully | Slack status OK | HTTP code $responseCode");
                } else {
                    TextHelper::messageERROR(json_decode($response, true)['error'] . " | Slack status NO | HTTP code $responseCode");
                }
            } else {
                TextHelper::messageERROR("Error sending message | HTTP code $responseCode");
            }
        }

    }
}
