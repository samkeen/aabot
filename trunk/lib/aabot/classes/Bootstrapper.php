<?php
class Bootstrapper {
	private $env;
	private $request_attributes = array('controller'=>null,'action'=>null);
	
	public function __construct(Env $env) {
		$this->env = $env;
		ini_set('include_path',$this->env->dir_view.':'.ini_get('include_path'));
	}
	public function strap($env=null) {
		$this->setControllerAndAction();
		$forward = $this->env->default_home;
		/**
		 * - if no controller send to default home page
		 */
		if ( ! $this->request_attributes['controller']) {
			require($forward);
			die();
		}
		/*
		 * if there was a controller in the request and an accompanying 
		 * controller file exixts, intanciate it and call it's process
		 *
		 */
		$controller_file = $this->env->dir_app.'/Controller/'.$this->request_attributes['controller'].'.php';
		if ($this->request_attributes['controller'] && file_exists($controller_file)) {
			$controller_name = "Controller_".ucfirst($this->request_attributes['controller']);
			$this->env->logger->debug(__METHOD__.' Invoking Controller [' . $controller_name . ']');
			$controller = new $controller_name($this->env);
			$controller->process($this->request_attributes['action']);
		} else {
			$message = 'controller requested ['.$this->request_attributes['controller'].'] does not exist';
			die($message);
		}
		
	}
	private function setControllerAndAction() {
		$elements = explode('/',$_GET['aa'],2);
		$this->request_attributes['controller'] = count($elements) ? ucfirst($elements[0]) : null;
		$this->request_attributes['action'] = count($elements)>1 ? $elements[1] : null;
	}
}
?>