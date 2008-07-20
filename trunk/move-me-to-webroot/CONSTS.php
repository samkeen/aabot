<?php
final class CONSTS {
	// You will need to edit these 2 for your particular environment
	const BASE_APP_PATH = '/Library/WebServer/Documents/aabot/move-me-to-webroot/app';
	const BASE_LIB_PATH = '/Library/WebServer/Documents/aabot/lib';
	
	
	private static $APP_DIR = '/';
	private static $LAYOUT_DIR = '/View/layout';
	private static $TEMPLATE_DIR = '/View/templates';
	
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
			return self::BASE_LIB_PATH.self::$$path.$append;
		} else {
			return self::BASE_APP_PATH.self::$$path.$append;
		}
		
	}
}