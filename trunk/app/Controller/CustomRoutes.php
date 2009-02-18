<?php
class Controller_CustomRoutes extends Controller_Base {
	
	protected function index() {
		$this->payload->message = "Hello, this is the default controller";
		$this->payload->controller = print_r($this,1);
	}
	
	protected function home() {
		$this->payload->message = "Adding SMS to Trimet TransitTracker";
	}
	protected function about() {
		$this->payload->message = "How SMS was added to Trimet TransitTracker";
	}
}

?>
