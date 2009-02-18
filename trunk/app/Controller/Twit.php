<?php
class Controller_Twit extends Controller_Base {
	
	protected function init() {
		
	}
	protected function index() {
		$this->payload->message = "Hello, this is the Twitter controller";
		$this->payload->controller = print_r($this,1);
	}
}