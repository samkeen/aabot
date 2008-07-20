<?php
abstract class Controller_Base {
	protected $request_context;
	protected $request_segments = null;
	protected $requested_response_type = null;
	protected $request_method = null;
	protected $logger = null;
	protected $name;
	
	protected $requested_action = null;
	/**
	 * you can send in an optional action name when you contruct a controller
	 * and that is set to this variable.  Then when $controller->process() is
	 * called, this $queued action is invoked (ignoring anything determined by url
	 * analysis).
	 * example usage in the Controller_Factory when an unknown controller is called in the URL.
	 * The factory intanciates the Default controller thusly
	 * - $controller = new Controller_Default($request_context,CONSTS::REQUEST_CONTROLLER_NOT_FOUND_ACTION);
	 * then when $controller->process() is called the contoller_not_found_action action of the controller 
	 * will be invoked. 
	 *
	 * @var string
	 */
	protected $queued_action = null;
	
	protected $template_file = null;
	protected $layout_file = null;
	
	protected $payload;
	protected $rendered_template;
	
	/**
	 * Enter description here...
	 *
	 * @param array $request_context
	 * @param string $queued_action (optional action to be called after this contoller is instaciated.)
	 * @param string $relative_template_path (optional Template file to use regarless of requested URL)
	 */
	public function __construct(array $request_context, $queued_action=null, $relative_template_path=null) {
		global $logger;
		$this->logger = $logger;
		$this->logger->debug(print_r($this,1));
		
		$this->request_context = $request_context;
		$this->queued_action = $queued_action;
		if ($relative_template_path!==null) {
			$this->set_template($relative_template_path);
		}
		$this->request_segments = $request_context['request_segments'];
		$this->requested_response_type = $request_context['requested_response_type'];
		$this->request_method = $request_context['request_method'];
		$this->name = strtolower(str_ireplace('controller_','',get_class($this)));
		$this->payload = new SimpleDTO();
		
	}
	/**
	 * main driver method for a controller
	 */
	public function process() {
		$this->logger->debug(__METHOD__.' Calling process');
		$this->determine_requested_action();
		$this->set_template_for_action();
		$this->set_layout();
		$this->call_action();
		$this->render_view();
	}
	/**
	 * each controller must define its own default action
	 */
	protected abstract function default_action();
	/**
	 * Sets the template file path.  First looks in App, then Lib
	 *
	 * @param string $relative_path The relative path (from the View dir) to the template file
	 */	
	protected function set_template($relative_path) {
		$relative_path = ltrim($relative_path,'/');
		$file_path = null;
		if (file_exists(CONSTS::PATH('TEMPLATE_DIR','/'.$relative_path))) {
			$this->logger->debug(__METHOD__.' Explicitly setting template file to :'.CONSTS::PATH('TEMPLATE_DIR','/'.$relative_path));
			$this->template_file = CONSTS::PATH('TEMPLATE_DIR','/'.$relative_path);
		} else if (file_exists(CONSTS::PATH('LIB_TEMPLATE_DIR','/'.$relative_path))) {
			$this->logger->debug(__METHOD__.' Explicitly setting template file to :'.CONSTS::PATH('LIB_TEMPLATE_DIR','/'.$relative_path));
			$this->template_file = CONSTS::PATH('LIB_TEMPLATE_DIR','/'.$relative_path);
		} else {
			$this->logger->debug(__METHOD__.'  Attempted to Explicitly set template file to : ['
				.CONSTS::PATH('TEMPLATE_DIR','/'.$relative_path). '] But file did not exist.');
			$this->template_file = CONSTS::PATH('TEMPLATE_DIR','/'.$relative_path);
		}
	}
	
	/**
	 * render the template first and bring any variables defined in it into then
	 * namespace of the layout when it is rendered (if thier is a layout to render.
	 */
	protected function render_view() {
		$this->digest_template();
		if ($this->using_layout()) {
			// set a short name ref to $this->payload for ease of use in the view.
			$payload = $this->payload;
			include($this->layout_file);
		}
	}
	/**
	 * Stores the rendered contents of the template in 
	 * $rendered_template to to be included in the layout
	 * (or rendeded on its own if no template) 
	 *
	 */
	private function digest_template() {
		// set a short name ref to $this->payload for ease of use in the view.
		$payload = $this->payload;
		if ( ! file_exists($this->template_file)) {
			$this->logger->notice(__METHOD__.' requested template file not found ['.$this->template_file.'], sending to file not found');
			Util_Core::send_to_unknown_request($this->request_context);
		}
		ob_start();
		include($this->template_file);
		if ($this->using_layout()) {
			$this->logger->debug(__METHOD__.' Using Layout [' . $this->layout_file . ']');
			/**
			 * pull back any mutations of $payload into $this->payload
			 * This allows templates to inject values into the 
			 * surrounding layout. (ex. define a head title, stylesheet, or js import) 
			 */
			$this->payload = $payload;
			$this->rendered_template = ob_get_contents();
			ob_end_clean();
		} else {
			$this->logger->debug(__METHOD__.' Not using Layout (layout_path was found to be [null])');
		}
	}
	
	private function call_action() {
		$the_action = $this->requested_action;
		$this->logger->debug(__METHOD__.' Invoking Action [' . $the_action .'] ');
		$this->$the_action();
	}
	private function determine_requested_action() {
		if ($this->queued_action) {
			$this->logger->notice(__METHOD__.' Found queued Action so setting requested action to: '.$this->queued_action);
			$this->requested_action = $this->queued_action;
		} else {
			// if we have a leftmost segemnt and it is a action method for this controller
			if(isset($this->request_segments[0]) && method_exists($this,str_replace('-','_',$this->request_segments[0]).'_action')) {
				$this->requested_action = str_replace('-','_',array_shift($this->request_segments)).'_action';
				$this->logger->debug(__METHOD__.'  Action was found to be: '.$this->requested_action);	
			} else { // use the default action
				if ($this->logger->debugEnabled()) {
					if (isset($this->request_segments[0])) {
						$this->logger->debug(__METHOD__.'  Did not find requested Action['.str_replace('-','_',$this->request_segments[0])
							.'_action] Sending to File not found');
						Util_Core::send_to_unknown_request($this->request_context);	
					} else {
						$this->requested_action = CONSTS::DEFAULT_ACTION.'_action';
						$this->logger->debug(__METHOD__.' No action supplied, using default action ['.CONSTS::DEFAULT_ACTION.'_action]');
					}
				}
			}
		}
	}
	private function set_template_for_action() {
		// if the template has not been set, set it here.  this allows the controller to set it.
		if($this->template_file === null) {
			$this->template_file = $this->detemine_deepest_template_match();
//			$action_name = str_replace('_action','',$this->requested_action);
//			$template_file_for_action = CONSTS::PATH('TEMPLATE_DIR','/') . $this->name .'/'. $action_name . '.php';
//			if (file_exists($template_file_for_action)) {
//				$this->template_file = $template_file_for_action;
//				$this->logger->debug(__METHOD__.'  found template file for the requested action: '.$template_file_for_action);
//			} else {
//				$default_template_file = CONSTS::PATH('TEMPLATE_DIR','/') . CONSTS::DEFAULT_TEMPLATE . '.php';
//				$this->template_file = $default_template_file;
//				$this->logger->debug(__METHOD__.'  unable to find template file for the requested action ['
//					.$template_file_for_action. '] Instead sending to "file not found Action');
//			}
		}
		
	}
	/**
	 * For example if the request URL is "http://example.com/courses/math/algebra/algebra2"
	 * we will look for the following templates in this order and use the first one found
	 * /{template_dir}/courses/math/algebra/algebra2.php
	 * /{template_dir}/courses/math/algebra.php
	 * /{template_dir}/courses/math.php
	 *
	 */
	private function detemine_deepest_template_match() {
		$deepest_template_file_path = null;
		$template_path = CONSTS::PATH('TEMPLATE_DIR','/').$this->name.'/'.str_replace('_action','',$this->requested_action).'/';
		for($index=count($this->request_segments);$index>=1;$index--) {
			$possible_template_file = $template_path.implode('/',array_slice($this->request_segments,0,$index)).".php";
			$this->logger->debug(__METHOD__.' trying template match for: '.$possible_template_file);
			if (file_exists($possible_template_file)) {
				$this->logger->debug(__METHOD__.' Found deepest template file match: '.$possible_template_file);
				$deepest_template_file_path = $possible_template_file;
				break;
			}
		}
		if ( ! $deepest_template_file_path && file_exists(CONSTS::PATH('TEMPLATE_DIR','/').$this->name.'/'.str_replace('_action','',$this->requested_action).'.php')) {
			$this->logger->debug(__METHOD__.' Found deepest template file match: '.CONSTS::PATH('TEMPLATE_DIR','/').$this->name.'/'.str_replace('_action','',$this->requested_action).'.php');
			$deepest_template_file_path = CONSTS::PATH('TEMPLATE_DIR','/').$this->name.'/'.str_replace('_action','',$this->requested_action).'.php';
		}
		return $deepest_template_file_path;
	}
	/**
	 * Stores the path to the layout file.
	 */
	private function set_layout() {
		$layout_dir = CONSTS::PATH('LAYOUT_DIR','/');
		$lib_layout_dir = CONSTS::PATH('LIB_LAYOUT_DIR','/');
		$this->layout_file = file_exists($layout_dir . $this->name . '.php')
			? $layout_dir . $this->name . '.php'
			: $lib_layout_dir . CONSTS::DEFAULT_LAYOUT.'.php';
	}
	private function using_layout() {
		return $this->layout_file !== null;
	}
}
?>