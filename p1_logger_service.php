#!/usr/bin/php -q
<?php
/**
 * This file needs to be run to start the P1Logger service, as we need to listen to the serial-line it is more convenient
 * to just keep the process active than to trigger it through a CRON as we could get half of a serial message in that case
 */

use Mattie112\php_p1_logger\P1LoggerService;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;

require_once(__DIR__ . "/vendor/autoload.php");

$class = P1LoggerService::class;
$ident = (new \ReflectionClass($class))->getShortName();

$options = getopt("dc", ["help::"]);
if (!is_array($options) || isset($options["help"])) {
    echo "Usage:
  -d Debug Mode
  -c Log to Console
" . PHP_EOL;
    exit();
}

// Parse the config file
$config = parse_ini_file(__DIR__ . "/config.ini");

$config_defaults = [
    "serial" => [
        "device" => "/dev/ttyAMA0",
        "baudrate" => "115200",
        "parity" => "none",
        "databits" => 8,
        "stopbits" => 1,
        "flow_control" => "none",
    ],
    "debug_mode" => false,
    "log_to_console" => false
];
$config = array_merge($config, $config_defaults);

// Use command-line option for debug_mode if set
if (isset($options["d"])) {
    $config["debug_mode"] = true;
}

// Use command-line option for log_to_console if set
if (isset($options["c"])) {
    $config["log_to_console"] = true;
}

// Default we'll log info, or use debug if requested
$level = Logger::INFO;
if ($config["debug_mode"]) {
    $level = Logger::DEBUG;
}

// Set log target
if ($config["log_to_console"]) {
    $log_handler = new StreamHandler("php://stdout", $level);
} else {
    $log_handler = new SyslogHandler($ident, LOG_LOCAL0, $level);
    $log_handler->setFormatter(new LineFormatter("%message% %context% %extra%"));
}

// Create Logger
$logger = new Logger($ident);
$logger->pushHandler($log_handler);

//Now start the actual service
$service = new P1LoggerService($config, $logger);
$service->run();

exit(0);