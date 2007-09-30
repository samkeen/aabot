<?php
class Log_File_Writer extends Log_Writer {
	private $log_file_path;
	public function __construct($log_file_path) {
		$this->log_file_path = $log_file_path;
	}
	public function writeMessage($log_message) {
		if (is_writable($this->log_file_path)) {
			 if (!$handle = fopen($this->log_file_path, 'a')) {
         echo "[".__METHOD__."] Cannot open file ($this->log_file_path)";
         exit;
    }
    // Write $somecontent to our opened file.
    if (fwrite($handle, $log_message) === FALSE) {
        echo "[".__METHOD__."] Cannot write to file ($this->log_file_path)";
        exit;
    }
    fclose($handle);
		} else {
			echo "[".__METHOD__."] Log file ({$this->log_file_path}) not writable ";
			die();
		}
	}
}
?>