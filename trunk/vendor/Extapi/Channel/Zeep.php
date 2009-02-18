<?php
require 'Base.php';
class Extapi_Channel_Zeep extends Extapi_Channel_Base {
	
	public function __construct($requesting_channel_name, array $request, Logger $logger) {
		parent::__construct($requesting_channel_name, $request, $logger);
	}

	
	/**
	 * @see Channel_Base::authenticate_request()
	 * @return boolean Success of authentication
	 */
	public function authenticate_request() {
		$authenticated = false;
		$authenticated = 
			// authenticate the request is from Zeep
			$this->config_get('channel_short_code') == array_get_else($this->mapped_channel_communication_fields,'channel_short_code')
			&&
			// authenticate the user making the request
			array_get_else($this->mapped_channel_communication_fields,'channel_user_id') == 'samkeen'
			&&
			array_get_else($this->mapped_channel_communication_fields,'channel_user_number') == '15034733242';
		return $authenticated;
	}
	
	/**
	 * build the security http header for a given sms service
	 */
	private static function generate_authorization_headers($service_name,$api_key, $signing_key, $message_parameters_string ) {
		$authorization_header = null;
		switch($service_name) {
			case 'zeep':
				$httpDate = gmdate("D, d M Y H:i:s T");
				$canonical_string = $api_key . $httpDate . $message_parameters_string;				
				$b64Mac = base64_encode(hash_hmac('sha1', $canonical_string, $signing_key,true));
				$authorization_header = "Zeep " . $api_key . ":" . $b64Mac;
			break;
		}
		return array('Authorization' => $authorization_header, 'Date' => $httpDate);
	}
	
	/**
	* Send the SMS Notification message.
	*
	* 
	*/
	public function send_channel_message($channel_recipients, Util_Http $http) {
		$channel_recipients = is_array($channel_recipients)?$channel_recipients:array($channel_recipients);
		global $logger;
		$url = $this->config_get('api_uri');
		foreach ($channel_recipients as $channel_recipient) {
			$message = array_get_else($channel_recipient,'message');
			$username = array_get_else($channel_recipient,'user_name');
			if ($logger->debug()) {
				$logger->debug(__METHOD__.' Building auth header with '
					."\nservice_name[".$this->config_get('channel_name')."]"
					."\napi_key [".($this->config_get('api_key')!=null?'...'.substr($this->config_get('api_key'),-3):'null')."]"
					."\nsignature_key [".($this->channel_signing_key!=null?'...'.substr($this->channel_signing_key,-3):'null')."]"
					."\nusername [".$username."]"
					."\nbody [".$message."]");
			}
			if ($username!==null) {
				$authorization_headers = $this->generate_authorization_headers(
					$this->config_get('channel_name'),
					$this->config_get('api_key'), 
					$this->channel_signing_key,
					'user_id='.urlencode($username).'&body='.urlencode($message));
				$http->headers = $authorization_headers;
				$resp = $http->post($url, array('user_id' => $username, 'body' => $message));
				$logger->debug(__METHOD__.'SMS API RESPONSE: ['.$resp. ']');
			} else {
				$logger->error(__METHOD__.' SMS NOT posted. Username was null.  $sms_recipient:'.print_r($channel_recipient,1));
			}
		}
		/**
POST /api/send_message HTTP/1.1
Host: zeepmobile.com
Authorization: Zeep cef7a046258082993759bade995b3ae8:XGPPx8+Me8RBoEUTPO6LSiSLDn4=
Date: Sat, 27 Sep 2008 21:26:11 GMT
Content-Type: application/x-www-form-urlencoded
Content-Length: 70
user_id=1234&body=Art+thou+not+Romeo%2C+and+a+Montague%3F
		**/
	}
}

?>