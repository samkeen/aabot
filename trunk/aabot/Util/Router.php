<?php
/**
 * Handles URL Routing
 * 
 * example request URI
 * 
 * http://example.com/users.admin/edit/42
 * 
 * first goal of Router is to determine the Controller in the above example.  First the 
 * request portion (/users.admin/edit/42) is tokenized: array('users','edit','42')
 *
 */
class Util_Router {
	/*
	 * i.e. for admin/users/add if the controller folder structure is:
	 * Controller
	 *  	\admin
	 * 			\Users.php
	 * the context is 'admin' & the controller is 'users'
	 * & the action is 'add'
	 */
    const CONTEXT = '__context';
    const CONTROLLER = '__controller';
    const ACTION = '__action';
    const ARGUMENT = '__argument';

    public $raw_request_path = null;

    private $request_path_segments;

    private $protocol;
    private $subdomain;
    private $domain;
    private $controller_context;
    private $controller;
    private $action;
    private $arguments=array();
    private $parameters;
    
    private $getable_attributes = array(
        'protocol','subdomain','domain','controller_context','controller','action','arguments',
        'request_method','response_type', 'request_path_segments', 'parameters',
        'debug_requested'
    );
    private $setable_attributes = array(
        'controller','action'
    );

    private $request_segment_suffixes = array(
        self::CONTEXT => null,
        self::CONTROLLER => null,
        self::ACTION => null,
        self::ARGUMENT => array()
    );

    private $custom_routes;
    private $custom_contexts;
    private $request_method; //POST, PUT, DELETE, GET, ...
    private $response_type; // ex: htm, rss, txt, ...
    private $debug_requested = false;

    const REDIRECT_VAR = ';c;';
    const PATH_SEPARATOR = '/';
    const REQUEST_METHOD_TOKEN = '_method';
    const REQUEST_SEGMENT_SUFFIX_DELIMITER = '.';

    private $strip_from_request = array(self::REDIRECT_VAR=>null);

	public function __construct(array $custom_routes = null, $custom_contexts = null) {
        $this->custom_routes = $custom_routes;
        $this->custom_contexts = $custom_contexts;

        $this->protocol = array_get_else($_SERVER, 'SERVER_PROTOCOL');
        $domain_parts = array_reverse(explode('.', array_get_else($_SERVER, 'HTTP_HOST')));
        $this->subdomain = array_get_else($domain_parts, 2);
        $this->domain = $domain_parts[1].'.'.$domain_parts[0];
        $this->raw_request_path = array_get_else($_GET, self::REDIRECT_VAR, '/');
        $this->process_request($this->raw_request_path);
        $this->request_method = $this->determine_request_method();
        $this->determine_requested_response_type();

        $this->parameters = array_diff_key($_GET,$this->strip_from_request);
        $this->debug_requested = isset($this->parameters['debug'])&&$this->parameters['debug']  || ENV::DEBUG_ALWAYS;
//        var_dump($this);die;
	}

    /**
     * allow for calls such as:
     * - $request->arguments__3
     * - $request->controller__suffix
     * - $request->arguments__filter__suffix;
     *
     * @param <type> $key
     * @return <type>
     */
    public function __get($key) {
        return $this->get_requested_attribute($key);
	}
    public function  __set($key, $value) {
        if(in_array($key, $this->setable_attributes)) {
            $this->{$key} = $value;
        }
    }
    /**
     *
     * @param int $arg_index the index for the argument (left most argument in
     * path is considered index 0.
     * Also special string values of 'first' and 'last' are allowed
     * @return string The value for the requested response type
     * ex: 'htm', 'rss', 'txt', 'kml', ...
     */
    private function get_argument($arg_index, $value_or_suffix='value') {
        $argument = null;
        if($arg_index=='first') {
            $argument = $value_or_suffix=='suffix'
             ? current($this->request_segment_suffixes[self::ARGUMENT])
             : current($this->arguments);
        } else if($arg_index=='last') {
            $argument = $value_or_suffix=='suffix'
                ? end($this->request_segment_suffixes[self::ARGUMENT])
                : end($this->arguments);
        } else {
            $argument = $value_or_suffix=='suffix'
                ? array_notempty_else($this->request_segment_suffixes[self::ARGUMENT], $arg_index)
                : array_notempty_else($this->arguments, $arg_index);
        }
        return $argument;
    }
    private function process_request($app_portion_of_uri) {
        $app_portion_of_uri = '/'.ltrim($app_portion_of_uri, ' /');
		global $PATH__APP_ROOT; // ie: "/Library/WebServer/Documents/extapi/app"
        if(key_exists($app_portion_of_uri, $this->custom_routes)) {
            ENV::$log->debug(__METHOD__.' Found custom route ['.$this->custom_routes[$app_portion_of_uri].'] for original request ['.$app_portion_of_uri.']');
            $app_portion_of_uri = $this->custom_routes[$app_portion_of_uri];
        }
        $this->request_path_segments = explode(self::PATH_SEPARATOR, trim($app_portion_of_uri,' /'));
        foreach ($this->request_path_segments as $segemnt_index => $request_path_segment) {
            // if context is not already set, check for it
            $segment_parts = $this->get_segment_value_suffix_parts($request_path_segment);
            if(! $this->controller) {
                $this->controller = $this->record_request_segment(self::CONTROLLER, $request_path_segment);
                $this->controller_context = $this->request_segment_suffixes[self::CONTROLLER];
//                var_dump($this);
            } else if(! $this->action) { // controller has been set so look to set action
                $this->action = $this->record_request_segment(self::ACTION, $request_path_segment);
            } else { // context,controller,action set so put rest in arguments
                // set the remainder as segments
                $this->arguments = $this->record_request_segment(
                        self::ARGUMENT, array_slice($this->request_path_segments, $segemnt_index)
                );
                $this->arguments = $this->arguments ? $this->arguments : array();
            }
        }
    }
    	/**
	 * determine and return the type of REST request method
	 * ['put','delete','post','get']
	 *
	 * @return string The type of REST request method
	 */
	private function determine_request_method() {
		$acceptable_request_methods = array('put','delete','post','get');
		$request_method = 'get';
		if (count($_POST)) {
			if( ! isset($_POST[self::REQUEST_METHOD_TOKEN])) {
				$request_method = 'post';
			} else {
				$request_method = in_array($_POST[self::REQUEST_METHOD_TOKEN],$acceptable_request_methods)
					? $_POST[self::REQUEST_METHOD_TOKEN]
					: $request_method;
			}
		}
		return $request_method;
	}
    /**
     *
     * this is used to build the suffix array for request segments
     * ex: given "http://local.extapi.com/admin.super/users/edit.x/42"
     * will generate suffix array:
     *  '__context' => 'null'super'
     *  '__controller' => null
     *  '__action' => 'x'
     *  '__argument' =>
     *    array
     *         0 => null
     *
     *
     * @param const $segemnt_type [CONTEXT|CONTROLLER|ACTION|ARGUMENT]
     * @param string $request_path_segment The string value for this path segment
     * <note> if $request_path_segment is an array, then we are wholesale setting
     * arguments.
     *
     * @return string The value portion of this request segment.
     * ex: given 'scale.10' returns 'scale'
     * ex: given array('42', '73.rss') returns array('42','73')
     */
    private function record_request_segment($segemnt_type, $request_path_segment) {
        $segemnt_value = null;
        if(is_array($request_path_segment)) {
            foreach ($request_path_segment as $index => $argument_segment_value) {
                $segment_parts = $this->get_segment_value_suffix_parts($argument_segment_value);
                $segemnt_value[] = $segment_parts['value'];
                $this->request_segment_suffixes[self::ARGUMENT][] = $segment_parts['suffix'];
            }
        } else {
            $segment_parts = $this->get_segment_value_suffix_parts($request_path_segment);
            if($segemnt_type==self::ARGUMENT) {
                $segemnt_value = $segment_parts['value'];
                $this->request_segment_suffixes[self::ARGUMENT][] = $segment_parts['suffix'];
            } else {
                $segemnt_value = $segment_parts['value'];
                $this->request_segment_suffixes[$segemnt_type] = $segment_parts['suffix'];
            }
        }
        return $segemnt_value;
    }
    /**
     * utility method to determine if the given request segment has a suffix and
     * split it out a into
     * its respective parts: value and suffix (one must be present but both optional)
     * ex admin.super, admin, view.rss, ans .rss are all acceptable
     *
     * @param string  $request_segment_string  ex: 'view.json'
     * @return array ex: array('value' => 'view', 'suffix' => 'rss') || false if no suffix found
     */
    private function get_segment_value_suffix_parts($request_segment_string) {
        $parts = pathinfo($request_segment_string);
        $segment_parts['value'] = array_get_else($parts, 'filename');
        $segment_parts['suffix'] = array_get_else($parts, 'extension');
        return $segment_parts;
    }

    private function determine_requested_response_type() {
        $this->response_type = $this->get_argument('last','suffix');
        if(empty($this->response_type)) {
            $this->response_type = CONSTS::$RESPONSE_GLOBAL_DEFAULT;
        }
    }
    /**
     *
     * @param string $attribute
     * -ex $attribute strings:
     */
    private function get_requested_attribute($attribute) {
        $attribute_parts = explode('__', $attribute);
        $number_of_parts = count($attribute_parts);
        if(in_array($attribute_parts[0],$this->getable_attributes)) {
            if($number_of_parts==1 || $attribute_parts[0]!='arguments') {
                return $this->{$attribute_parts[0]};
            } else {
                return $this->get_argument($attribute_parts[1], array_get_else($attribute_parts, 2, 'value'));
            }
        }
        return null;
    }
}
?>