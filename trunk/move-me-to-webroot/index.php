<?php
require 'CONSTS.php';
require CONSTS::$BASE_LIB_PATH.'/utils.php';

$logger = new Logger(Logger::DEBUG);
$controller = Controller_Factory::get_instance();
$controller->process();
