<?php
require 'lib/utils.php';
$logger = new Logger(Logger::DEBUG);
$controller = Controller_Factory::get_instance();
$controller->process();