<?php
class Controller_Default extends Controller_Base {
	
	protected function index() {
		$this->payload->message = "Hello, this is the default controller";
		$this->payload->controller = print_r($this,1);
	}
	/**
	 * expose the protected function add_debug_message so Factory can use it
	 */
	public function add_debug_message($message, $escape_html = true) {
		return parent::add_debug_message($message,$escape_html);
	}
	protected function file_not_found_action() {
		$this->payload->message = "You've requested an unknown resource";
	}
	
	
}

?>
