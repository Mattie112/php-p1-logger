<?php
/**
 * Created by PhpStorm.
 * User: Matthijs
 * Date: 23-3-2016
 * Time: 20:13
 */
require_once "vendor/autoload.php";


$serial = new PhpSerial();

$serial->deviceSet("/dev/ttyAMA0");

$serial->confBaudRate(115200);
$serial->confParity("none");
$serial->confCharacterLength(8);
$serial->confStopBits(1);
$serial->confFlowControl("none");

$open = $serial->deviceOpen();

if (!$open) {
    die();
}

while (true) {
    $data = $serial->readPort();

    if ($data) {
        echo $data;
    }

    // As the data is send only every 10 sec we might want to use the time to catch up sleep
    sleep(1);
}
