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
	
	private  static $FILE_NOT_FOUND_TEMPLATE = '/default/file_not_found.php';
	
	static final function PATH($path, $append=null) {
		if(substr($path,0,4)=='LIB_') {
			return self::BASE_LIB_PATH.self::$$path.$append;
		} else {
			return self::BASE_APP_PATH.self::$$path.$append;
		}
		
	}
	/**
	 * This takes a relative path and first looks for it in app, then
	 * in lib.
	 *
	 * @param string $relative_path_to_file
	 * @param string $append
	 * @return unknown
	 */
	public static final function PATH_TO_TEMPLATE_FILE($relative_path_to_file) {
		$relative_path_to_file = ltrim($relative_path_to_file,'/ ');
		if(file_exists(self::BASE_APP_PATH.self::$TEMPLATE_DIR.'/'.$relative_path_to_file)) {
			return self::BASE_APP_PATH.self::$TEMPLATE_DIR.'/'.$relative_path_to_file;
		} else if (file_exists(self::BASE_LIB_PATH.self::$LIB_TEMPLATE_DIR.'/'.$relative_path_to_file)) {
			return self::BASE_LIB_PATH.self::$LIB_TEMPLATE_DIR.'/'.$relative_path_to_file;
		} else {
			return null;
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public static final function FILE_NOT_FOUND_TEMPLATE() {
		return self::PATH_TO_TEMPLATE_FILE(self::$FILE_NOT_FOUND_TEMPLATE);
	}
}