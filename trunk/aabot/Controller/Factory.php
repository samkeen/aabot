<?php

class Controller_Factory {
	
	public static function get_instance(Util_Router $router) {
		global $logger;
		$requested_controller = $router->controller;
        $controller = null;
		if ($controller_name = ENV::get_controller_classname($requested_controller)) {
			$logger->debug(__METHOD__.' Invoking Controller [' . $controller_name . ']');
			$controller = new $controller_name($router);
		} else {
			$logger->debug(__METHOD__.' Unknown requested Contoller [' . $requested_controller . '] Sending to Unknown request handler');
			$controller = new Controller_Default($router);
			$controller->add_debug_message('Unable to locate a file for Controller ['.$requested_controller.']');
			$controller->process(ENV::FILE_NOT_FOUND_TEMPLATE(), CONSTS::$FILE_NOT_FOUND_ACTION);
			exit();
		}
		return $controller;
	}
	private static function custom_route($custom_routes) {
		$requested_route = $_SERVER['REQUEST_URI'];
		return isset($custom_routes[$requested_route]) ? $custom_routes[$requested_route] : false;
	}
}