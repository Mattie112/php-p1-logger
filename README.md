# php-p1-logger
===================

p1-data-logger is a small PHP script to fetch, parse and upload data from your DSMR 4 compliant meter. Currently it supports upload to PVOutput.

----------


# Installation and Setup
----------

* Install php (sudo apt-get install php5-cli for example or just php5 if you want the full web stack)
* Install composer (see https://getcomposer.org/download/)
* Git clone the source with `git clone https://github.com/Mattie112/php-p1-parser.git`
* Execute a `composer install` to fetch dependencies
* Copy the example.ini to config.ini and edit this file
* Checkout the "example.php" and "export_data.php" file to get you started!

----------


# Automatic upload to PVOut
----------
Run the service: `php p1_logger_service.php`


----------


#Licence
----------
<a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-sa/4.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike 4.0 International License</a>.