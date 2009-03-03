<?php
abstract class Controller_Base {
	
	public $model_name;
	public $payload;
	public $recieved_form_data = false;
	public $form_data = array();
	public $name;

	// resp type to fall back to if none explicitly requested 
	protected $default_response_type = null;
	protected $request_method = null;
	protected $logger = null;
	// the name used for related files in the View directory (matches name in URL).
	// ex: Class name CustomRoutes will have view_dir_name of custom_routes
	protected $view_dir_name;
	protected $router;
	protected $requested_action = null;

    private $actions_to_authenticate = array();
    /*
     * ex: array(
     *  0 => array(
     *      'actions' => array('add','edit'),
     *      'groups' => array('steward', 'admin')
     *  ),
     *  1 => array(
     *      'actions' => array('delete'),
     *      'groups' => array('admin')
     *  )
     *  first match found is acted on (parsing starts at zero)
     */
    private $actions_to_authorize = array();
	/**
     *
     * @var Controller_Helper_Feedback
     */
	protected $feedback;
    /**
     *
     * @var Model_Helper_Auth
     */
    protected $auth;
	
	// these are ment to be overridden in Controllers
	protected $use_template = true;
	protected $use_layout = true;
	protected $template_file = null;
	protected $layout_file = null;
	
	// collected debug messages that can be shown in the view
	private $debug_messages = array();
	
	
	protected $rendered_template;
	
	
	/**
	 * Enter description here...
	 *
	 * @param Util_Router $router
	 */
	public function __construct(Util_Router $router) {
		global $logger;
        $this->feedback = new Controller_Helper_Feedback();
        $this->auth = new Model_Helper_Auth();
        
		$this->logger = $logger;
		$this->router = $router;
		$this->name = strtolower(str_replace('Controller_','',get_class($this)));
		
		$this->default_response_type = CONSTS::$RESPONSE_GLOBAL_DEFAULT;
		$this->request_method = $this->router->request_method;
		$this->detect_recieved_data();
		$this->view_dir_name = $this->router->controller;
		$this->set_model_name();
		
		$this->payload = new SimpleDTO();
		$this->init();
	}

    /**
     * pass unknown get's down to Router
     * @param string $name
     * @return void
     */
    public function  __get($name) {
        return $this->router->{$name};
    }
	/**
	 * allow a controller init method or action to declare it does not return a view.
	 */
	protected function viewless() {
		$this->use_layout = false;
		$this->use_template = false;
	}
	/**
	 * file not found internal action
	 * called from Factory if Controller is not found
	 */
	protected function file_not_found_action() {
		$this->logger->debug(__METHOD__.' Calling base controller internal File Not Found Action');
		$this->payload->message = "You've requested an unknown resource";
	}
	protected function redirect($path, $die_afterwards = true) {
		header('Location: '.$path);
		if ($die_afterwards) {
			die();
		}
	}

	/**
	 * main driver method for a controller
	 * 
	 * - first determine the action
	 * - call the action: this allows the controller to override things
	 *   like layouts and templates. 
	 */
	public function process($override_template = null, $override_action = null) {
		$this->logger->debug(__METHOD__.' Calling process');
		if ($override_action===null) {
			$this->determine_requested_action();
		}
		if ($this->logger->debug() && $override_action!==null) {
			$this->logger->debug(__METHOD__.' Action has been set to OVERRIDE VALUE: ['.$override_action.']');
		}
		// call the action
		$this->call_action($override_action);
		
		$this->construct_view($override_template);
	}
	public function construct_view($override_template = null, $return_view_as_string = false) {
		if ($override_template===null && $this->use_template) {
			$this->set_template_for_action();
		} else {
			$this->template_file = $override_template;
		}
		
		
		if ($this->use_template && ! file_exists($this->template_file)) {
			$this->logger->notice(__METHOD__.' requested template file not found ['.$this->template_file.'], sending to file not found');
			// override the $layout=null, $action=null, $view=null
			$override_template = ENV::FILE_NOT_FOUND_TEMPLATE();
			$this->add_debug_message('Unable to locate file for Template ['.$this->template_file.']');
			$this->template_file = $override_template;
		}
		
		
		if ($this->logger->debug() && $override_template!==null) {
			$this->logger->debug(__METHOD__.' Template has been set to OVERRIDE VALUE: ['.$override_template.']');
		}
		
		$this->set_layout();
		return $this->render_view($return_view_as_string);
	}
	
	/**
	 * each controller must define its own default action
	 */
//	protected abstract function index();
	/**
	 * each controller must define its own init action
	 * init is called at the end of the Base Controller __constructor
	 */
	protected function init(){}
	/**
	 * Sets the template file path.  First looks in App, then Lib
	 *
	 * @param string $relative_path The relative path (from the View dir) to the template file
	 */	
	protected function set_template($relative_path) {
		$file_path = null;
		if ($this->template_file = ENV::get_template_path($relative_path)) {
			$this->logger->debug(__METHOD__.' Explicitly setting template file to :'.$this->template_file);
		} else {
			$this->logger->debug(__METHOD__.'  Attempted to Explicitly set template file to : ['
				.ENV::PATH('TEMPLATE_DIR','/'.$relative_path). '] But file did not exist.');
		}
	}
	
	/**
	 * render the template first and bring any variables defined in it into then
	 * namespace of the layout when it is rendered (if thier is a layout to render.
	 */
	protected function render_view($return_as_string=false) {
		if($return_as_string) {
			ob_start(null,null,true);
		}
		if ($this->use_template) {
			$this->digest_template();
		}
		if ($this->use_layout) {
			// set a short name ref to $this->payload for ease of use in the view.
			$payload = $this->payload;
			$feedback = $this->feedback;
			include($this->layout_file);
		}
		if (ENV::debug_active() && $this->router->debug_requested) {
			include(ENV::get_template_path('blocks/debug'));
		}
		if($return_as_string) {
			$rendered_view = ob_get_contents();
			ob_end_clean();
			return $rendered_view;
		}
	}
	/**
	 * Allow the controller to grab the view as a string.
	 */
	public function get_rendered_view($override_template = null) {
		return $this->construct_view($override_template, true);
	}
	protected function add_debug_message($message, $escape_html = true) {
		$this->debug_messages[] = $escape_html ? htmlentities($message, ENT_QUOTES, 'UTF-8') : $message;
	}
	/**
	 * return the collected debug messages
	 */
	protected function debug_messages($in_html_form = true) {
		return $in_html_form
			? '<ul><li>'.implode('</li><li>',$this->debug_messages).'</li></ul>' 
			: implode("\n",$this->debug_messages);
	}
    /**
     * Meant to be called in a Controller's init() method to set up what will
     * authenticated.
     *
     * @param mixed [optiona] $actions_to_authenticate If given can be array or
     * comma delim string.  If null, considered all actions
     */
    protected function authenticate($actions_to_authenticate=null) {
        if($actions_to_authenticate===null) {
           $this->actions_to_authenticate = array('__ALL'); 
        } else {
            $this->actions_to_authenticate = !is_array($actions_to_authenticate)
                ? explode(',', $actions_to_authenticate)
                :$actions_to_authenticate;
        }
    }
    /**
     * Meant to be called in a Controller's init() method to set up what will
     * authorized.
     *
     * @param mixed $authorized_groups array or commma delim string
     * @param mixed [optional] $actions_to_authorize array or commma
     * delim string.    If null, considered all actions
     */
    protected function authorize($authorized_groups, $actions_to_authorize=null) {
        $authorized_groups = !is_array($authorized_groups)
                ? explode(',', $authorized_groups)
                :$authorized_groups;
        if($actions_to_authorize===null) {
           $actions_to_authorize = array('__ALL');
        } else {
            $actions_to_authorize = !is_array($actions_to_authorize)
                ? explode(',', $actions_to_authorize)
                :$actions_to_authorize;
        }
        $this->actions_to_authorize[$actions_to_authorize] = $authorized_groups;
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
		$feedback = $this->feedback;
        $form = new View_Form($this);
		ob_start();
		include($this->template_file);
		if ($this->use_layout) {
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
	private function set_model_name() {
		$this->model_name = strtolower(rtrim(str_replace('Controller_','',get_class($this)),'s'));
	}
	private function call_action($action=null) {
		$the_action = $action!==null?$action:$this->requested_action;
		$this->logger->debug(__METHOD__.' Invoking Action [' . $the_action .'] ');
        $this->auth->validate_credentials($the_action);
        $this->$the_action();
	}

    private function validate_credentials($the_action) {
        // check if authenticated

        // check if authorized
    }
	private function determine_requested_action() {
		$possible_action = $this->router->action;
        $possible_action = $this->router->controller_context!==null ? $this->router->controller_context.'__'.$possible_action: $possible_action;
        if($possible_action!==null && method_exists($this,$possible_action)) {
			$this->requested_action = $possible_action;
			$this->logger->debug(__METHOD__.'  Action was found to be: '.$this->requested_action);	
		} else { // use the default action
			if (!empty($this->router->action)) { // non-existant action so 404
                $this->logger->warn(__METHOD__.'  Did not find requested Action['.$possible_action.'] Sending to File not found');
            } else { // no requested action so use default
                $this->requested_action = $this->router->controller_context ? $this->router->controller_context.'__'.CONSTS::$DEFAULT_ACTION:CONSTS::$DEFAULT_ACTION;
                $this->logger->debug(__METHOD__.' No action supplied, using default action ['.$this->requested_action .']');
            }
		}
	}
	private function set_template_for_action() {
		// if the template has not been set, set it here.  this allows the controller to set it.
		if($this->template_file === null) {
			$this->template_file = $this->detemine_deepest_template_match();
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
        // rempliment match to arguments files
		$template_path = $this->view_dir_name.'/'.$this->router->action.'/';
        $template_path = str_replace('//', '/', $template_path);
        $request_file_path_segments = $this->router->request_path_segments;
		for($index=count($this->router->arguments);$index>=1;$index--) {
			$segment_names = array_slice($this->router->arguments,0,$index);
			$possible_template_file = $template_path.implode('/',$segment_names).".php";
			$this->logger->debug(__METHOD__.' trying template match for: '.$possible_template_file);
			if ($deepest_template_file_path = ENV::get_template_path($possible_template_file)) {
				$this->logger->debug(__METHOD__.' Found deepest template file match: '.$possible_template_file);
				break;
			}
		}
		// look for a template for the action
		if ( ! $deepest_template_file_path) { 
            $file_path = $this->router->controller_context !== null
                ? $this->view_dir_name.'/'.$this->router->controller_context .'/'.str_replace($this->router->controller_context.'__', '', $this->requested_action).'.php'
                : $this->view_dir_name.'/'.$this->requested_action.'.php';
			$this->logger->debug(__METHOD__.' trying template match for: '.ENV::PATH('TEMPLATE_DIR','/').$file_path);
			if ($deepest_template_file_path = ENV::get_template_path($file_path) ) {
				$this->logger->debug(__METHOD__.' Found deepest template file match[action]: '.ENV::PATH('TEMPLATE_DIR','/').$file_path);
			}
		}
		// finally look for a template for the contoller
		if ( ! $deepest_template_file_path) {
            $file_path = $this->view_dir_name.'.php';
			$this->logger->debug(__METHOD__.' trying template match for: '.ENV::PATH('TEMPLATE_DIR','/').$file_path);
			if ($deepest_template_file_path = ENV::get_template_path($file_path)) {
				$this->logger->debug(__METHOD__.' Found deepest template file match[controller]: '.ENV::PATH('TEMPLATE_DIR','/').$file_path);
			}
		}
		return $deepest_template_file_path;
	}
	/**
	 * Stores the path to the layout file.
	 */
	private function set_layout() {
		if ($this->layout_file===null && $this->use_layout) {
			$layout_dir = ENV::PATH('LAYOUT_DIR','/');
			$lib_layout_dir = ENV::PATH('LIB_LAYOUT_DIR','/');
			if (file_exists($layout_dir.$this->view_dir_name . '.php')) {
				$this->layout_file = $layout_dir .$this->view_dir_name . '.php';
			} else {
				$this->layout_file = file_exists($layout_dir . CONSTS::$DEFAULT_LAYOUT . '.php')
					? $layout_dir . CONSTS::$DEFAULT_LAYOUT . '.php'
					: $lib_layout_dir . CONSTS::$DEFAULT_LAYOUT.'.php';
			}
		}
	}
	/**
	 * @todo include form tokens
	 */
	private function detect_recieved_data() {
		if (count($_POST) && isset($_POST['__method'])) {
			$this->recieved_form_data = true;
			$this->form_data = $_POST;
		}
	}
	public function get_response_type() {
		return $this->router->response_type;
	}
}
?>