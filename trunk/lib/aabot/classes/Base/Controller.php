<?php
class Base_Controller {
	protected $logger;
	protected $env;
	protected $template_path;
	protected $template_contents;
	/**
	 * explicitly set the layout path to an empty string.  The
	 * extending controller can then set it to null which signifies 
	 * the no layout is to be used.
	 *
	 * @var string
	 */
	protected $layout_path = "";
	protected $p_; // payload

	protected $action;
	
	public function __construct(Env $env) {
		$this->env = $env;
		$this->logger = $env->logger;
		$this->p_ = new SimpleDTO();
		$this->setLayout();
	}
	/**
	 * Enter description here...
	 *
	 * @param string $action
	 */
	public function process($action) {
		$this->logger->debug(__METHOD__.' Calling process for action [' . $action .']');
		$this->action = $action;
		$this->setTemplate();
		if($this->actionExists()) {
			$this->logger->debug(__METHOD__.' Action [' . $action .'] Found on Controller [' . get_class($this) . '], now invoking');
			$this->$action();
		} else {
			$this->logger->info(__METHOD__.' Action NOT found [' . $action .']');
		}
		// STOP flow if we are expecting a layout but it does NOT exist
		if ($this->usingLayout() && ! $this->layoutExists()) {
			$this->logger->error(__METHOD__.' Using Layout ['.$this->layout_path.'] but does NOT exist');
			die("Couldn't find the expected layout: ".$this->layout_path);
		}
		if ($this->templateExists()) {
			$this->logger->debug(__METHOD__.' Template ['.$this->template_path.'] WAS found');
			$this->renderView();
		} else { // action method not found on controller and no template found
			$this->logger->error(__METHOD__.' Template ['.$this->template_path.'] NOT found');
			die("Couldn't find the expected template: ".$this->template_path);
		}
	}
	/**
	 * Called from the implementing controller in order
	 * to render the completed page.
	 */
	protected function renderView() {
		$this->digestTemplate();
		if ($this->usingLayout()) {
			// set a short name ref to $this->p_ for ease of use in the view.
			$p_ = $this->p_;
			include($this->layout_path);
		}
		
	}
	/**
	 * Stores the path to the layout file.
	 */
	private function setLayout() {
		$this->layout_path = file_exists($this->env->dir_layout . '/' . str_replace('Controller_','',get_class($this)) . '.php')
			? $this->env->dir_layout . '/' . str_replace('Controller_','',get_class($this)) . '.php'
			: $this->env->dir_layout . '/default.php';
	}
	private function setTemplate() {
		$this->template_path = file_exists($this->env->dir_view . '/' . str_replace('Controller_','',get_class($this)) . '/' . $this->action . '.php')
			? $this->env->dir_view . '/' . str_replace('Controller_','',get_class($this)) . '/' . $this->action . '.php'
			: null;
	}
	/**
	 * Stores the rendered contents of the template in 
	 * $template_contents to to be included in the layout
	 * (or rendeded on its own if no template) 
	 *
	 */
	private function digestTemplate() {
		// set a short name ref to $this->p_ for ease of use in the view.
		$p_ = $this->p_;
		include($this->template_path);
		if ($this->usingLayout()) {
			$this->logger->debug(__METHOD__.' Using Layout [' . $this->layout_path . ']');
			/**
			 * pull back any mutations of $p_ into $this->p_
			 * This allows templates to inject values into the 
			 * surrounding layout. (ex. define a stylesheet of js import)
			 */
			$this->p_ = $p_;
			$this->template_contents = ob_get_contents();
			ob_end_clean();
		} else {
			$this->logger->debug(__METHOD__.' Not using Layout (layout_path has been set to null)');
		}
	}
	private function actionExists() {
		return method_exists($this,$this->action);
	}
	private function usingLayout() {
		return $this->layout_path!==null;
	}
	private function templateExists() {
		return file_exists($this->template_path);
	}
	private function layoutExists() {
		return file_exists($this->layout_path);
	}

}
?>