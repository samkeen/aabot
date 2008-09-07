<?php
class Controller_Default extends Controller_Base {
	
	protected function default_action() {
		$this->payload->message = "Hello, this is the default controller";
		$this->payload->controller = print_r($this,1);
	}
	
	protected function file_not_found_action() {
		$this->payload->message = "You've requested an unknown resource";
	}
	
	
}

?>
