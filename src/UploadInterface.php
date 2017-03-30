<?php
namespace Mattie112\php_p1_logger;

use Monolog\Logger;

interface UploadInterface
{
    public function __construct(array $config, Logger $logger);

    /**
     * @param array $input
     *
     * @return
     */
    public function upload(array $input);

}