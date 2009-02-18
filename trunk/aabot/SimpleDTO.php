<?php
/**
 * Lightweight, simple DTO
 *
 */
class SimpleDTO {
	
	private $base_attributes = array();
	
	public function __construct($attributes=null) {
		$this->base_attributes = $attributes!==null?$attributes:array();
	}
	
	public function __get($key) {
		return key_exists($key,$this->base_attributes) ? $this->base_attributes[$key] : null;
	}
	public function __set($key, $value) {
		$this->base_attributes[$key] = $value;
	}

    public function dump_attributes() {
        return $this->base_attributes;
    }
}
?>