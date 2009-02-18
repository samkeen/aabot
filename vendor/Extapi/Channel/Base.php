<?php
/**
 * Base class for all channels
 * Provides base utility methods and defines the interface of
 * - have_required_request_params()
 * - authenticate_request()
 * 
 *
 * @package			extapi
 * @subpackage		channel
 */
abstract class Extapi_Channel_Base {
	
	protected $config;
	// store sensitive values seperate from other config settings for security
	protected $channel_signing_key;
	protected $channel_account_password;
	
	protected $request;
	protected $logger;
	protected $requesting_channel_name;
	/*
	 * the total set of all communication fields for this specific type of channel
	 */
	protected $channel_communication_fields = array();
	/*
	 * any keys from $channel_communication_fields that are found in the 
	 * request are placed into this array
	 * [public]: need to access from Service classes
	 * @todo see if this still needs to be public
	 */
	public $mapped_channel_communication_fields = array();
	/*
	 * Fields that we require to be present in the request for this
	 * type of channel
	 */
	protected $required_channel_communication_fields = array();

	public abstract function authenticate_request();
		
	public function __construct($requesting_channel_name, array $request, Logger $logger) {
		$this->requesting_channel_name = $requesting_channel_name;
		$this->request = $request;
		$this->logger = $logger;
		$this->load_config('channels', $requesting_channel_name);
	}
	/**
	 * to be implemented by extending class
	 * @return boolean success of collecting all required request params
	 */
	public function have_required_request_params() {
		$collected_all_required_params = null;
		// verify that we have the expected number of required params
	 	foreach ($this->required_channel_communication_fields as $required_field) {
	 		if (empty($this->mapped_channel_communication_fields[$required_field])) {
	 			$this->logger->warn(__METHOD__.'Value for required field['.$required_field.'] found found to be empty');
	 			$collected_all_required_params = false;
	 		}
	 	}
	 	return $collected_all_required_params===null?true:false;
	}
	public function config() {
		return $this->config;
	}
	/**
	 * load the config file settings for this specific Channel
	 * populate
	 * - required_channel_communication_fields
	 * - mapped_channel_communication_fields
	 * - channel_communication_fields
	 * - channel_signing_key and channel_account_password
	 */
	private function load_config($file, $channel_name=null) {
		$config_folder =  dirname(dirname(__FILE__)).'/config/';
		$config = parse_ini_file($config_folder.$file.'.ini',true);
		$channel_type = $this->get_channel_type($channel_name, $config['channel_type_map']);
		$channel_key = $channel_type.'/'.$channel_name;
		foreach ($config as $key => $value) {
			if (substr($key,0,strlen($channel_key))==$channel_key) {
				$this->channel_signing_key = array_get_else($value,'signature_key');
				unset($value['signature_key']);
				$this->channel_account_password = array_get_else($value,'api_password');
				unset($value['api_password']);
				$this->config = $value;
			}
		}
		foreach (array_get_else($config,$channel_type.'/channel_fields/'.$channel_name,array()) as $key => $value) {
			if($key=='required_fields') {
				$this->required_channel_communication_fields = array_map('trim',explode(',',$value));
			} else {
				$this->mapped_channel_communication_fields[$key] = array_get_else($this->request, $value);
			}
		}
		$this->channel_communication_fields = array_get_else($config,$channel_type.'/channel_fields',array());
	}
	protected function config_get($key) {
		return array_get_else($this->config,$key);
	}
	private function get_channel_type($channel_name, $types_map) {
		return array_get_else($types_map,$channel_name);
	}
}

?>