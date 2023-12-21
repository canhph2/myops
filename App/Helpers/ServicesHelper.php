<?php

namespace App\Helpers;

class ServicesHelper
{
    // usage:
    //    php _ops/lib slack "YOU MESSAGE";
    public static function SlackMessage(array $argv)
    {
        // === validate ===
        //    validate a message
        $message = $argv[2] ?? null;
        if (!$message) {
            echo "[ERROR] missing a MESSAGE\n";
            exit(); // END
        }
        //    validate env vars
        $repository = getenv('Repository');
        $branch = getenv('Branch');
        $slackBotToken = getenv('SLACK_BOT_TOKEN');
        $slackChannel = getenv('SLACK_CHANNEL');
        if (!$repository || !$branch || !$slackBotToken || !$slackChannel) {
            echo "[ERROR] missing a Branch or Repository or SLACK_BOT_TOKEN or SLACK_CHANNEL\n";
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
        if (!curl_exec($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        } else {
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            echo $responseCode === 200
                ? "\nMessage sent successfully\n"
                : "\nError sending message with code $responseCode \n";
        }

    }
}
