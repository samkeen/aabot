<?php
class Util_Http {
	
	
	public static function send_to_unknown_request($request_context) {
		self::call_controller_action(
			'Controller_Default',
			$request_context, 
			CONSTS::REQUEST_CONTROLLER_NOT_FOUND_ACTION,
			'/default/file_not_found.php'
		);
	}
	
	private static function call_controller_action($controller_name, $request_context, $action_name, $relative_template_path=null) {
		$controller = new $controller_name($request_context,$action_name,$relative_template_path);
		$controller->process();
		exit();
	}

}
