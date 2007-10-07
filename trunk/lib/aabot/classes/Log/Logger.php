<?php
class Log_Logger {
	
	public $log_level;
	
	const DEBUG = 10;
	const INFO = 5;
	const ERROR = 0;
	
	private $error_type_human = array(
		self::DEBUG => "DEBUG",
		self::INFO => "INFO ",
		self::ERROR => "ERROR"
	);
	
	private $log_writer;
	
	public function __construct(Log_Writer $log_writer, $log_level) {
		$this->log_writer = $log_writer;
		$this->log_level = $log_level;
	}
	
	public function debug($log_message) {
		if ($this->log_level >= self::DEBUG) {
			$this->logMessage($log_message,self::DEBUG);
		}
	}
	public function info($log_message) {
		if ($this->log_level >= self::INFO) {
			$this->logMessage($log_message,self::INFO);
		}
	}
	public function error($log_message) {
		if ($this->log_level >= self::ERROR) {
			$this->logMessage($log_message,self::ERROR);
		}
	}
	protected function logMessage($log_message, $log_level) {
		$message = "[".$this->error_type_human[$log_level]."] ".$log_message;
		$this->log_writer->writeMessage($message);
	}
}
?>