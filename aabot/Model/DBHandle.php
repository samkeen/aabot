<?php

class Model_DBHandle {
	private $hostname;
	private $database;
	private $username;
	private $password;
	
	private $db_handle;
	
	public function __construct($config) {
		$this->hostname = $config['hostname'];
		$this->username = $config['username'];
		$this->password = $config['password'];
		$this->database = $config['database'];
		$this->connect();
	}
	public function __destruct() {
		$this->db_handle = null;
	}
	
	public function query($sql) {
		$result = null;	
		try {
			$stmt = $this->db_handle->query($sql);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			ENV::$log->error(__METHOD__.$e->getMessage());
		}
		return $result;
	}
	public function execute($sql) {
		$count = null;	
		try {
			$count = $this->db_handle->exec($sql);
		} catch (Exception $e) {
			ENV::$log->error(__METHOD__.$e->getMessage());
		}
		return $count;
	}
	public function prepare($statement_text) {
		return $this->db_handle->prepare($statement_text);
	}
    public function last_insert_id($sequence_id=null) {
        $last_insert_id = null;
        try {
			$last_insert_id = $this->db_handle->lastInsertId($sequence_id);
		} catch ( Exception $e ) {
			ENV::$log->error(__METHOD__.$e->getMessage());
		}
        return $last_insert_id;
    }
	private function connect() {
		try {
			$this->db_handle = new PDO('mysql:host='.$this->hostname.';dbname='.$this->database, $this->username, $this->password);
		} catch ( Exception $e ) {
			ENV::$log->error(__METHOD__.$e->getMessage());
		}
	}
}