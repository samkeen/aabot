<?php
class Env {
	
	private $base_path;
	private $base_attributes = array();
	public $logger;
	
	
	public function __construct($context_path=null) {
		$this->base_path = $context_path;
		$this->base_attributes['base_path'] = $context_path;
		$this->base_attributes['default_home'] = 'static/index.php';
		$this->base_attributes['dir_app'] = '/app';
		$this->base_attributes['dir_layout'] = '/app/view/layout';
		$this->base_attributes['dir_view'] = '/app/view';
	}
	
	public function __get($key) {
		if(key_exists($key,$this->base_attributes)) {
			$prefix = (substr($key,0,4)=='dir_') ? $this->base_path : "";
			return $prefix . $this->base_attributes[$key];
		} else {
			return null;
		}
	}
}

?>