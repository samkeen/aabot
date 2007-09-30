<?php
class SimpleDTO {
	
	private $base_attributes = array();
	
	public function __get($key) {
		return key_exists($key,$this->base_attributes) ? $this->base_attributes[$key] : null;
	}
	public function __set($key, $value) {
		$this->base_attributes[$key] = $value;
	}
}
?>