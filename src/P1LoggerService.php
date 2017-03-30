<?php
/**
 * Created by PhpStorm.
 * User: Matthijs
 * Date: 24-3-2016
 * Time: 19:53
 */

namespace Mattie112\php_p1_logger;

use Exception;
use Monolog\Logger;
use PhpSerial;

class P1LoggerService
{
    /** @var Logger */
    protected $logger;
    /** @var array Array with config values */
    protected $config;
    /** @var PhpSerial The object for the serial port */
    protected $serial;
    /** @var string Local buffer to keep the last P1 telegram */
    protected $databuffer;
    /** @var  P1Parser */
    protected $parser;
    /** @var UploadInterface[] */
    protected $uploaders;

    /**
     * P1LoggerService constructor.
     *
     * @param array $config
     * @param Logger $logger
     */
    public function __construct(array $config, Logger $logger)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->parser = new P1Parser($logger);
        $this->serial = $this->initializeSerialPort($config);
        $this->uploaders[] = new PVOutputUploader($config, $logger);
    }

    /**
     * Helper function to initialize the connection to the Serial Port
     *
     * @param array $config
     *
     * @return PhpSerial
     */
    protected function initializeSerialPort(array $config)
    {
        $this->logger->info("Trying to open a serial port", ["config" => $this->config["serial"]]);

        $serial = new PhpSerial();

        $serial->deviceSet($config["serial"]["device"]);

        $serial->confBaudRate(115200);
        $serial->confParity("none");
        $serial->confCharacterLength(8);
        $serial->confStopBits(1);
        $serial->confFlowControl("none");

        try {
            $open = $serial->deviceOpen();
            $this->logger->info("Serial port is now open and ready!", ["config" => $this->config["serial"]]);
            if (!$open) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->logger->emergency("Unable to open serial port", ["config" => $this->config["serial"]]);
            exit(1);
        }

        return $serial;

    }

    public function handleData()
    {
        // We will now parse the data
        $parsed_data = $this->parser->parseTelegram($this->databuffer);

        // Now we have a nice array with key=>values we can use
        foreach ($this->uploaders as $uploader) {
            $uploader->upload($parsed_data);
        }

        // Reset the databuffer
        $this->databuffer = null;
    }

    /**
     * The main loop of the program
     */
    public function run()
    {
        while (true) {

            if (isset($this->config["simulate"]) && $this->config["simulate"] == true) {
                $data = "/XMX5LGBBFFB23127xxxx2\r\n\r\n1-3:0.2.8(42)\r\n0-0:1.0.0(160327165616S)\r\n0-0:96.1.1(4530303034303xxxxxxx238343xxx)\r\n1-0:1.8.1(000755.889*kWh)\r\n1-0:2.8.1(000096.825*kWh)\r\n1-0:1.8.2(000310.532*kWh)\r\n1-0:2.8.2(000321.425*kWh)\r\n0-0:96.14.0(0001)\r\n1-0:1.7.0(00.000*kW)\r\n1-0:2.7.0(02.514*kW)\r\n0-0:96.7.21(00001)\r\n0-0:96.7.9(00000)\r\n1-0:99.97.0(0)(0-0:96.7.19)\r\n1-0:32.32.0(00000)\r\n1-0:32.36.0(00000)\r\n0-0:96.13.1()\r\n0-0:96.13.0()\r\n1-0:31.7.0(011*A)\r\n1-0:21.7.0(00.000*kW)\r\n1-0:22.7.0(02.514*kW)\r\n0-1:24.1.0(003)\r\n0-1:96.1.0(473030313xxxxxxx73838303xxx)\r\n0-1:24.2.1(160327160000S)(00303.186*m3)\r\n!644A\r\n";
            } else {
                $data = $this->serial->readPort();
            }

            if ($data) {
                $this->logger->debug("Received data from the serialport", ["data" => $data]);

                // It is possible to read in the middle of a transmission so we can do two things
                // Simply overwriting the databuffer and if we have an incomplete message simply ignore it
                // or adding it to the existing buffer and depending on an other function to clear the buffer
                $this->databuffer = $this->databuffer . $data;

                // Only handle the data if we have a new message, we don't want to process the same message multiple times
                $this->handleData();
            }

            // Data is send only once every 10 secs by the smart meter
            sleep(10);
        }

    }
}