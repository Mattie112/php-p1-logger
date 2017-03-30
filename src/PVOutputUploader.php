<?php
namespace Mattie112\php_p1_logger;

use Monolog\Logger;

class PVOutputUploader implements UploadInterface
{
    /** @var array */
    protected $config;
    /** @var Logger */
    protected $logger;

    private $last_run_time;
    private $run_rime;

    /**
     * PVOutputUploader constructor.
     *
     * @param array $config
     * @param Logger $logger
     */
    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->run_rime = $config["pvoutput"]["upload_time"];
    }

    /**
     * @param array $input
     */
    public function upload(array $input)
    {
        $time = time();
//        if (!$this->last_run_time) {
//            // run
//        }
        if ($time < $this->last_run_time + $this->run_rime) {
            return;
        }


        $data = [];
        $data["d"] = date("Ymd", $input["metadata"]["p1_timestamp"]); //yyyymmdd
        $data["t"] = date("H:i", $input["metadata"]["p1_timestamp"]); //hh:mm

//        $data["c1"] = "1"; // We submit lifetime values
//        $data["v3"] = ($input["energy"]["consumed"]["high"] + $input["energy"]["consumed"]["low"]) * 1000;

        $data["n"] = "1"; // We submit lifetime values
        if (isset($input["energy"]["currently_delivered"]) && $input["energy"]["currently_delivered"] > 0) {
            $data["v4"] = ($input["energy"]["currently_delivered"]) * -1000;
        } else {
            $data["v4"] = ($input["energy"]["currently_consumed"]) * 1000;
        }

        //  Now upload this data
        PVOutHelper::sendToPVOutput($this->config, $data, $this->logger);
        $this->last_run_time = time();
    }
}