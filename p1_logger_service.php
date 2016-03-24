#!/usr/bin/php -q
<?php
/**
 * This file needs to be run to start the P1Logger service, as we need to listen to the serial-line it is more convenient
 * to just keep the process active than to trigger it through a CRON as we could get half of a serial message in that case
 */

require_once(__DIR__ . "/vendor/autoload.php");

$options = getopt("dc", ["help::"]);
if (!is_array($options) || isset($options["help"])) {
    echo "Usage:
  -d Debug Mode
  -c Log to Console
". PHP_EOL;
    exit();
}

//todo read ini file here
parse_ini_file(__DIR__."/config.ini");

$config_defaults = [];
$config = array_merge($config, $config_defaults);

// Use command-line option for debug_mode if set
if (isset($options["d"])) {
    $config["debug_mode"] = true;
}

// Use command-line option for log_to_console if set
if (isset($options["c"])) {
    $config["log_to_console"] = true;
}
