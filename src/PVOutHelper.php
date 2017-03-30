<?php
namespace Mattie112\php_p1_logger;


use Monolog\Logger;

/**
 * Class PVOutHelper
 *
 * Class taken from my hosola-data-logger, todo create separate repo with this helper class to avoid duplication
 *
 * @package Inverter
 */
class PVOutHelper
{

    /**
     * Settings with [pvout][apikey] and [pvout][system_id]
     * data formatted how pvoout it wants http://pvoutput.org/help.html#api-spec
     *
     * @param $settings
     * @param $data
     * @param Logger $logger
     *
     * @throws \Exception
     */
    public static function sendToPVOutput($settings, $data, Logger $logger)
    {
        $headers = "Content-type: application/x-www-form-urlencoded\r\n";
        $headers .= "X-Pvoutput-Apikey: " . $settings["pvoutput"]["api_key"] . "\r\n";
        $headers .= "X-Pvoutput-SystemId: " . $settings["pvoutput"]["system_id"] . "\r\n";

        $http_build_query = http_build_query($data);
        $options = [
            "http" => [
                "header" => $headers,
                "method" => "POST",
                "content" => $http_build_query,
            ],
        ];
        $logger->debug("Http query", [$options]);

        try {
            $context = stream_context_create($options);
            $output = file_get_contents($settings["pvoutput"]["url"], false, $context);

            $logger->debug("Data send to pvoutput!", ["output" => $output]);
        } catch (\HttpException $e) {
            $logger->warning("Unable to connect to pvoutput", ["error" => $e->getMessage()]);
        }
    }
}