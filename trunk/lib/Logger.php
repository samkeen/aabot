<?php
/**
 * Very simple Logging class.  Allowed you to add debugging statments in code and
 * "turn them off" in production
 * 
 * @author "Sam Keen" <sam@pageindigo.com>
 * 
 */
class Logger {
	
	public $log_level;
	const DEBUG = 40;
	const NOTICE = 30;
	const WARN = 20;
	const ERROR = 10;
	private $error_type_human = array(
		self::DEBUG => "DEBUG",
		self::NOTICE => "NOTICE",
		self::WARN => "WARN",
		self::ERROR => "ERROR"
	);
	/*
	 * hold the optional full path to the log file.  If not defined, we log to 
	 * the PHP defined system log using error_log();
	 */
	private $log_file_path;
	/**
	 * @param $log_level The level at which to log.
	 * @param $log_file_path If given we will attempt to write
	 * log statments to that file, if not given we will write to the 
	 * system log
	 */
	public function __construct($log_level, $log_file_path=null) {
		$this->log_file_path = $log_file_path;
		$this->log_level = $log_level;
	}
	/**
	 * @return boolean Tells you if the Debug level is turned on. Used
	 * to wrapp expensive expressions that are building debug statments.
	 */
	public function debugEnabled() {
		return $this->log_level >= self::DEBUG;
	}
	/**
	 * Log a message at debug level
	 * @param $log_message The message to log
	 */
	public function debug($log_message) {
		if ($this->log_level >= self::DEBUG) {
			$this->logMessage($log_message,self::DEBUG);
		}
	}
	/**
	 * Log a message at info level
	 * @param $log_message The message to log
	 */
	public function notice($log_message) {
		if ($this->log_level >= self::NOTICE) {
			$this->logMessage($log_message,self::NOTICE);
		}
	}
	/**
	 * Log a message at info level
	 * @param $log_message The message to log
	 */
	public function warn($log_message) {
		if ($this->log_level >= self::WARN) {
			$this->logMessage($log_message,self::WARN);
		}
	}
	/**
	 * Log a message at error level
	 * @param $log_message The message to log
	 */
	public function error($log_message) {
		if ($this->log_level >= self::ERROR) {
			$this->logMessage($log_message,self::ERROR);
		}
	}
	/**
	 * log message if we are set at or above the $log_level supplied
	 */
	private function logMessage($log_message, $log_level) {
		$message = "[".$this->error_type_human[$log_level]."] ".$log_message;
		if ($this->log_file_path===NULL) {
			$this->writeSystemMessage($message);
		} else {
			$this->writeFileMessage($message);
		}
	}
	private function writeSystemMessage($log_message) {
		error_log($log_message);
	}
	private function writeFileMessage($log_message) {
		if (is_writable($this->log_file_path)) {
			if (!$handle = fopen($this->log_file_path, 'a')) {
				$error = "[".__METHOD__."] CANNOT OPEN FILE ($this->log_file_path)";
				error_log('+!+!+!+!+!+'.$error.' TO WRITE MESSAGE: '. $log_message);
			}
			// Write $somecontent to our opened file.
			if (fwrite($handle, $log_message."\n") === FALSE) {
				$error = "[".__METHOD__."] CANNOT WRITE TO FILE ($this->log_file_path)";
				error_log('+!+!+!+!+!+'.$error.' TO WRITE MESSAGE: '. $log_message);
			}
			fclose($handle);
		} else {
			error_log("+!+!+!+!+!+[".__METHOD__."] LOG FILE ({$this->log_file_path}) NOT WRITABLE!!! ");
		}
	}
	
	
}
?>