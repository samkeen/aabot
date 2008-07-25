<?php
require 'CONSTS.php';
require CONSTS::BASE_LIB_PATH.'/utils.php';

$custom_routes = array(
	'/' => '/custom/home'
);
$logger = new Logger(Logger::DEBUG);
$controller = Controller_Factory::get_instance($custom_routes);
$controller->process();