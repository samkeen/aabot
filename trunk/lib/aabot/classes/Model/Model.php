<?php
/**
 * all classes in /app/model should extend Model
 */
abstract class Model {
	
	/**
	 * if the model was defined as JSON could use
	 * that to define allowed attribs.
	 * On first request could actually convert the 
	 * JSON def to an actual php file and cache it to disc.
	 */
	public $allowed_attributes = array();
	/**
	 * need to do ORM here
	 */
	public function __construct($child_class) {
		$this->allowed_attributes = json_decode('../../../app/model/'.$child_class);
	}
	public function __get() {}
	public function __set() {}
	
	
}
?>