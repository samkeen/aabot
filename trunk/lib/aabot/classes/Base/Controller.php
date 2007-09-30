<?php
class Base_Controller {
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
	
	public function __construct($env) {
		$this->env = $env;
		$this->p_ = new SimpleDTO();
		$this->setLayout();
	}
	/**
	 * Enter description here...
	 *
	 * @param string $action
	 */
	public function process($action) {
		$this->action = $action;
		$this->setTemplate();
		if($this->actionExists()) {
			$this->$action();
		} else if ($this->layoutExists() && $this->templateExists()) {
			$this->renderLayout();
		} else { // action method not found on controller and no template found
			$message = "Action [$action] was not found for Controller " .get_class($this);
			die($message);
		}
	}
	/**
	 * Called from the implementing controller in order
	 * to render the completed page.
	 */
	protected function renderLayout() {
		$this->digestTemplate();
		// set a short name ref to $this->p_ for ease of use in the view.
		$p_ = $this->p_;
		include($this->layout_path);
	}
	/**
	 * Stores the path to the layout file.
	 */
	private function setLayout() {
		$this->layout_path = file_exists($this->env->dir_layout . '/' . str_replace('controller_','',get_class($this)) . '.php')
			? $this->env->dir_layout . '/' . str_replace('controller_','',get_class($this)) . '.php'
			: $this->env->dir_layout . '/default.php';
	}
	private function setTemplate() {
		$this->template_path = file_exists($this->env->dir_view . '/' . str_replace('controller_','',get_class($this)) . '/' . $this->action . '.php')
			? $this->env->dir_view . '/' . str_replace('controller_','',get_class($this)) . '/' . $this->action . '.php'
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
		/**
		 * pull back any mutations of $p_ into $this->p_
		 * This allows templates to inject values into the 
		 * surrounding layout. (ex. define a stylesheet of js import)
		 */
		$this->p_ = $p_;
		$this->template_contents = ob_get_contents();
		ob_end_clean();
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