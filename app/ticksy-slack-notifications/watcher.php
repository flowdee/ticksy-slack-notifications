<?php
/*
 * Load Libraries
 */
require 'libs/ticksy/Ticksy.php';
require 'libs/slack/Slack.php';
require 'libs/bridge/Bridge.php';

/*
 * DON'T FORGET TO UPDATE THE CONFIG.PHP FILE!
 */
require 'config.php';

if ( !function_exists('curl_version') ) {
    die('PHP Curl extension required. Please update your WebHosting.');
}

$bridge = new Bridge();
// Nothing more to do for you. Relax! :)
