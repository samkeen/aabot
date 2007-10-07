<?php
class Controller_Foo extends Base_Controller {
	
	
	public function bar() {
		$this->p_->message = "Message From ".__METHOD__;
		
		/**
		 * we can optionally set the layout to null.  This signals to the
		 * Controller that we are not using a contoller
		 */
		//$this->layout_path = null;

	}
}
?>