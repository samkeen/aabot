<?php
class controller_foo extends Base_Controller {
	
	
	public function bar() {
		$this->p_->message = "Message From ".__METHOD__;
		$this->renderLayout();
	}
}
?>