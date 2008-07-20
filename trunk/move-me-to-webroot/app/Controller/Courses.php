<?php
class Controller_Courses extends Controller_Base {
	
	protected function default_action() {
		// set the template
		
		$this->payload->message = "Hello, this is the Courses controller";
		$this->payload->controller = print_r($this,1);
	}
	
	protected function baseball_gm_action() {
//		$this->set_template($this->determine_template());
		$this->payload->message = "Hello, this is the Courses controller XXX";
		$this->payload->controller = print_r($this,1);
	}
	
	
	private function determine_template() {
		$template_file = isset($this->request_segments[0])?$this->request_segments[0]:'course';
		$action_name = str_replace('_action','',$this->requested_action);
		return $this->name.'/'.$action_name.'/'.$template_file.'.php';
	}
	
	
}