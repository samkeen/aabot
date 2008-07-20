<?php
final class CONSTS {
	// paths that can be requested
	public static $BASE_APP_PATH = '/Library/WebServer/Documents/aabot/move-me-to-webroot';
	public static $BASE_LIB_PATH = '/Library/WebServer/Documents/aabot/lib';
	private static $APP_DIR = '/app';
	private static $LAYOUT_DIR = '/app/View/layout';
	private static $TEMPLATE_DIR = '/app/View/templates';
	
	private static $LIB_LAYOUT_DIR = '/View/layout';
	private static $LIB_TEMPLATE_DIR = '/View/templates';
	
	// default app settings
	const DEFAULT_REQUESTED_RESPONSE_TYPE = 'html';
	const DEFAULT_CONTROLLER = 'Default';
	const DEFAULT_TEMPLATE = 'default';
	const DEFAULT_LAYOUT = 'default';
	const DEFAULT_ACTION = 'default';
	
	//
	const REQUEST_CONTROLLER_NOT_FOUND_ACTION = 'file_not_found_action';
	
	static final function PATH($path, $append=null) {
		if(substr($path,0,4)=='LIB_') {
			return self::$BASE_LIB_PATH.self::$$path.$append;
		} else {
			return self::$BASE_APP_PATH.self::$$path.$append;
		}
		
	}
}