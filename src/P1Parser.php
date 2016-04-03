<?php
/**
 * Created by PhpStorm.
 * User: Matthijs
 * Date: 24-3-2016
 * Time: 19:30
 */

namespace Mattie112\php_p1_logger;


use DateTime;
use Monolog\Logger;

class P1Parser
{
    /** @var Logger */
    protected $logger;

    /** @var array The databuffer as an array */
    protected $data;

    /**
     * P1Parser constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param $databuffer
     *
     * @return array
     */
    public function parseTelegram($databuffer)
    {
        $reply = [];

        $this->data = explode("\r\n", $databuffer);
        foreach ($this->data as $item) {
            $this->logger->debug("Data element found", [$item]);
        }

        $reply["metadata"]["p1_version_number"] = $this->parse($databuffer, '/1-3:0\.2\.8.*\((.*)\)/', "P1 version number");
        $timestamp = $this->parse($databuffer, '/0-0:1\.0\.0.*\((.*)\)/', "Timestamp of the telegram");
        $reply["metadata"]["text_message_codes"] = $this->parse($databuffer, '/0-0:96\.13\.1.*\((.*)\)/', "Text message codes");
        $reply["metadata"]["text_message"] = $this->parse($databuffer, '/0-0:96\.13\.0.*\((.*)\)/', "Text message");
        $reply["metadata"]["device_type"] = $this->parse($databuffer, '/0-1:24\.1\.0.*\((.*)\)/', "Device type");
        $reply["metadata"]["energy_equipment_identifier"] = $this->parse($databuffer, '/0-0:96\.1\.1.*\((.*)\)/', "Energy equipment Identifier");
        $reply["metadata"]["gas_equipment_identifier"] = $this->parse($databuffer, '/0-1:96\.1\.0.*\((.*)\)/', "Gas equipment Identifier");

        // Electricity part
        $reply["energy"]["consumed"]["low"] = $this->parse($databuffer, '/1-0:1\.8\.1.*\((.*)\*kWh\)/', "Energy consumed low");
        $reply["energy"]["consumed"]["high"] = $this->parse($databuffer, '/1-0:1\.8\.2.*\((.*)\*kWh\)/', "Energy consumed high");

        $reply["energy"]["produced"]["low"] = $this->parse($databuffer, '/1-0:2\.8\.1.*\((.*)\*kWh\)/', "Energy produced low");
        $reply["energy"]["produced"]["high"] = $this->parse($databuffer, '/1-0:2\.8\.2.*\((.*)\*kWh\)/', "Energy produced high");

        $reply["energy"]["currently_delivered"] = $this->parse($databuffer, '/1-0:2\.7\.0.*\((.*)\*kW\)/', "Actual energy delivered");
        $reply["energy"]["currently_consumed"] = $this->parse($databuffer, '/1-0:1\.7\.0.*\((.*)\*kW\)/', "Actual energy consumed");
        $reply["energy"]["tariff"] = $this->parse($databuffer, '/0-0:96\.14\.0.*\((.*)\)/', "Energy tariff indicator");
        $reply["energy"]["power_failures"] = $this->parse($databuffer, '/0-0:96\.7\.21.*\((.*)\)/', "Power failures in any phase");
        $reply["energy"]["long_power_failures"] = $this->parse($databuffer, '/0-0:96\.7\.9.*\((.*)\)/', "Long power failures in any phase");
        $reply["energy"]["voltage_sags_L1"] = $this->parse($databuffer, '/1-0:32\.32\.0.*\((.*)\)/', "Voltage sags phase L1");
        $reply["energy"]["voltage_swells_L1"] = $this->parse($databuffer, '/1-0:32\.36\.0.*\((.*)\)/', "Voltage swells phase L1");
        $reply["energy"]["instantaneous_current_L1"] = $this->parse($databuffer, '/1-0:31\.7\.0.*\((.*)\*A\)/', "Instantaneous current L1");
        $reply["energy"]["instantaneous_consumed_power_L1"] = $this->parse($databuffer, '/1-0:21\.7\.0.*\((.*)\*kW\)/', "Instantaneous consumed power L1");
        $reply["energy"]["instantaneous_delivered_power_L1"] = $this->parse($databuffer, '/1-0:22\.7\.0.*\((.*)\*kW\)/', "Instantaneous delivered power L1");

        // Gas part
        $gas_timestamp = $this->parse($databuffer, '/0-1:24\.2\.1\((.*)\)/U', "Gas measurement timestamp");
        $reply["gas"]["total"] = $this->parse($databuffer, '/0-1:24\.2\.1.*\((.*)\*m3\)/', "Total gas used");

        // Stuff in the P1 spec but not reported by my energy meter (single-phase)
        $reply["energy"]["switch"] = $this->parse($databuffer, '/0-0:96\.3\.10.*\((.*)\)/', "Electricity switch position");
        $reply["energy"]["threshold"] = $this->parse($databuffer, '/0-0:17\.0\.0.*\((.*)\*kW\)/', "Actual threshold electricity");
        $reply["energy"]["voltage_sags_L2"] = $this->parse($databuffer, '/1-0:52\.32\.0.*\((.*)\)/', "Voltage sags phase L2");
        $reply["energy"]["voltage_sags_L3"] = $this->parse($databuffer, '/1-0:72\.32\.0.*\((.*)\)/', "Voltage sags phase L3");
        $reply["energy"]["voltage_swells_L2"] = $this->parse($databuffer, '/1-0:52\.36\.0.*\((.*)\)/', "Voltage swells phase L2");
        $reply["energy"]["voltage_swells_L3"] = $this->parse($databuffer, '/1-0:71\.36\.0.*\((.*)\)/', "Voltage swells phase L3");
        $reply["energy"]["instantaneous_current_L2"] = $this->parse($databuffer, '/1-0:51\.7\.0.*\((.*)\)/', "Instantaneous current L2");
        $reply["energy"]["instantaneous_current_L3"] = $this->parse($databuffer, '/1-0:71\.7\.0.*\((.*)\)/', "Instantaneous current L3");
        $reply["energy"]["instantaneous_positive_power_L2"] = $this->parse($databuffer, '/1-0:41\.7\.0.*\((.*)\)/', "Instantaneous positive power L2");
        $reply["energy"]["instantaneous_positive_power_L3"] = $this->parse($databuffer, '/1-0:61\.7\.0.*\((.*)\)/', "Instantaneous positive power L3");
        $reply["energy"]["instantaneous_negative_power_L2"] = $this->parse($databuffer, '/1-0:42\.7\.0.*\((.*)\)/', "Instantaneous negative power L2");
        $reply["energy"]["instantaneous_negative_power_L3"] = $this->parse($databuffer, '/1-0:62\.7\.0.*\((.*)\)/', "Instantaneous negative power L3");
        $reply["gas"]["valve"] = $this->parse($databuffer, '/0-1:24\.4\.0.*\((.*)\)/', "Gas valve");

        // Format the P1 timestamp so we can use it without the need to parse it
        if (isset($timestamp) && $timestamp) {
            $p1_time = DateTime::createFromFormat("ymdHis", substr($timestamp, 0, -1));
            $reply["metadata"]["p1_timestamp"] = $p1_time->getTimestamp();
            $reply["metadata"]["p1_readable_time"] = $p1_time->format("d/m/Y H:i:s");
        }

        // Format the P1 timestamp so we can use it without the need to parse it
        if (isset($gas_timestamp) && $gas_timestamp) {
            $p1_time = DateTime::createFromFormat("ymdHis", substr($gas_timestamp, 0, -1));
            $reply["gas"]["timestamp"] = $p1_time->getTimestamp();
            $reply["gas"]["readable_time"] = $p1_time->format("d/m/Y H:i:s");
        }

        $this->logger->debug("Parsed databuffer", [$reply]);

        return $reply;
    }

    /**
     * Generic function to parse data
     *
     * @param string $databuffer The input string
     * @param string $regex The regular expression
     * @param string $name An option name for debugging purposes
     *
     * @return mixed|false
     */
    protected function parse($databuffer, $regex, $name = null)
    {
        if (preg_match($regex, $databuffer, $match)) {
            $this->logger->debug("Parsed data " . $name, [$match[1]]);

            return $match[1];
        }

        return false;
    }
}