<?php

class Controller_Factory {
	
	public static function get_instance($custom_routes=null) {
		global $logger;
		$url_context_param = null;
		if($custom_routes) {
			if ($custom_route = self::custom_route($custom_routes)) {
				$url_context_param = $custom_route;
			}
		} else {
			$url_context_param = array_notempty_else($_GET,'c');
		}
		
		$request_context = get_context($url_context_param);
		// if there is at least one request segment, set the first as the requested
		// controller name and remove it from the request segments.
		$requested_controller = isset($request_context['request_segments'][0])
			? ucfirst(array_shift($request_context['request_segments']))
			: CONSTS::DEFAULT_CONTROLLER;
		/*
		 * if there was a controller in the request and an accompanying 
		 * controller file exixts, intanciate it and call it's process
		 * method
		 */
		$controller = null;
		$controller_file = CONSTS::PATH('APP_DIR','Controller/').$requested_controller.'.php';
		$logger->debug(__METHOD__.'  File for requested Controller is: '.$controller_file);
		if (file_exists($controller_file)) {
			$controller_name = "Controller_".$requested_controller;
			$logger->debug(__METHOD__.' Invoking Controller [' . $controller_name . ']');
			$controller = new $controller_name($request_context);
		} else {
			$logger->error(__METHOD__.'  controller file requested ['.$controller_file.'] does not exist');
			Util_Core::send_to_unknown_request($request_context);
		}
		return $controller;
	}
	private static function custom_route($custom_routes) {
		$requested_route = $_SERVER['REQUEST_URI'];
		return isset($custom_routes[$requested_route]) ? $custom_routes[$requested_route] : false;
	}
}