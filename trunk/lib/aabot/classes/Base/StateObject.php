<?php
class StateObject {
	
	private $base_attributes = array();
	private $solid = false;

	public function __construct($additional_attributes=null, $solid=false) {
		$this->solid = $solid;
		$this->base_attributes = is_array($additional_attributes) 
			? array_merge($this->base_attributes, $additional_attributes)
			: $this->base_attributes;
	}
	
	public function __get($key) {
		return key_exists($key,$this->base_attributes) ? $this->base_attributes[$key] : null;
	}
	public function __set($key, $value) {
		$set_allowed = (! $this->solid) || key_exists($key,$this->base_attributes);
		if ($set_allowed) {
			$this->base_attributes[$key] = $value;
		}
		return $set_allowed;
	}
	/**
	 * Allows you to set up an attribute as an array and 
	 * append values to it
	 * ex.  start w/ $obj->foo = array([0]=>'a')
	 * call $obj->arpend->('foo','b')
	 * now $obj->foo = array([0]=>'a',[1]=>'b')
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function arpend($key, $value) {
		if (key_exists($key,$this->base_attributes)) {
			if (is_array($this->base_attributes[$key])) {
				$this->base_attributes[$key][] = $value;
			} else {
				$this->base_attributes[$key] = array($value);
			}
		}
	}
	public function defines($key) {
		return key_exists($key,$this->base_attributes);
	}
	public function solidify() {
		$this->solid = true;
	}
	public function liquify() {
		$this->solid = false;
	}
	public function toString() {
		"I need implemeted!!!".__METHOD__;
	}
	public function toJson() {
		return json_encode(get_object_vars($this));
	}
	public function toArray() {
		return get_object_vars($this);
	}
	function real_get_obj_vars() {
		if (is_array($this)) {
			foreach ($guts as $part) {
				print "found:".$part."\n";
				if (is_object($part)) {
					real_get_obj_vars($part);
				}
			}
		}
		if (is_object($this)) {
			foreach (get_object_vars($this) as $key => $value) {
				print "found: $key == $value\n";
				if (is_object($value)) {
					real_get_obj_vars($value);
				}
			}
		}
	}
}
?>