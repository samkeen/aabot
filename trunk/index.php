<?php
require 'app/bootstrap.php';
$logger = new Logger(Logger::DEBUG,dirname(__FILE__).'/extapi.log',CONSTS::$DEBUG_ACTIVE);
ENV::$log=$logger;
$custom_routes = isset($custom_routes)?$custom_routes:null;
$custom_contexts = isset($custom_contexts)?$custom_contexts:null;
$router = new Util_Router($custom_routes, $custom_contexts);
$controller = Controller_Factory::get_instance($router);
$controller->process();