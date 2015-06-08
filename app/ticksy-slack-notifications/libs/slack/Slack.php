<?php
require 'SlackAttachment.php';

class Slack {

    private $webhook_url;
    private $username;
    private $icon_url;

    private $messages;

    # Constructor
    public function Slack($webhook_url) {

        if ( empty($webhook_url) ) {
            return false;
        }

        $default = array(
            'webhook_url' => $webhook_url,
            'username' => 'Ticksy',
            'icon_url' => 'https://ticksy.com/app/_theme/Ticksy_3.0/shared_assets/favicons/favicon-96x96.png',
        );

        // Initialize
        $this->webhook_url = $default['webhook_url'];
        $this->username = $default['username'];
        $this->icon_url = $default['icon_url'];
    }

    // Push to slack
    public function push_to_slack($messages) {

        if ( !$this->webhook_url ) {
            return false;
        }

        $this->messages = $messages;

        // PUSH
        $slack_args = array(
            'username' => $this->username,
            'icon_url' => $this->icon_url,
            //'channel' => '#other-channel',    // A public channel override
            //'channel' => '@username',         // A Direct Message override
            'attachments' => array()
        );

        foreach ($messages as $i => $message) {
            $attachment = new SlackAttachment();
            $attachment->fallback = $message['title'] . ' - ' . $message['text'];
            $attachment->pretext = $message['pretext'];
            $attachment->title = $message['title'];
            $attachment->text = $message['text'];

            $slack_args['attachments'][] = $attachment;
        }

        $payload = json_encode($slack_args);

        $data = $payload;

        //$this->debug($data);

        // A very simple PHP example that sends a HTTP POST to a remote site
        // https://api.slack.com/incoming-webhooks

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$this->webhook_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        //print_r($server_output);
        //echo '<br>';

        curl_close ($ch);

        // further processing ....
        if ($server_output != "ok") {
            echo 'Curl problem.<br>';
        }
    }
    
    private function debug($arg) {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
}