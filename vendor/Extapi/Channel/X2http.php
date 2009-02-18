<?php
require 'Base.php';
class Extapi_Channel_X2http extends Extapi_Channel_Base {
	
	public function __construct($requesting_channel_name, array $request, Logger $logger) {
		parent::__construct($requesting_channel_name, $request, $logger);
	}

	
	/**
	 * @see Channel_Base::authenticate_request()
	 *
	 */
	public function authenticate_request() {
		$authenticated = false;
		$authenticated = 
			array_get_else($this->mapped_channel_communication_fields,'channel_user_id') == 'sam.sjk@gmail.com';
		return $authenticated;
	}
	
	/**
	 * build the security http header for a given sms service
	 */
	private static function generate_authorization_headers($service_name,$api_key, $signing_key, $message_parameters_string ) {
		throw new Exception("FAIL: ".__METHOD__." NOT IMPLEMENTED");
	}
	/**
	 * shortcut method for $this->send_channel_messages(array $channel_recipients);
	 * 
	 * @param string $message The fully rendered message to send
	 */
	public function send_channel_message($message) {
		return $this->send_channel_messages(array(0 => array('user_name' => $this->mapped_channel_communication_fields['channel_user_id'], 'message' => $message)));
	}
	/**
	* Send the SMS Notification message.
	*
	* @param array $channel_recipients 'user_name' => {username}, 'message' => {message}, ...
	*/
	public function send_channel_messages(array $channel_recipients) {
		ENV::load_vendor_file('XMPPHP/XMPP.php');
		$conn = new XMPPHP_XMPP(
			$this->config_get('api_host_name'), 
			$this->config_get('api_uri_port'), 
			$this->config_get('api_username'), 
			$this->channel_account_password, 
			'extapi', 
			$this->config_get('api_domain_name'), 
			$printlog=false, 
			$loglevel=XMPPHP_Log::LEVEL_INFO
		);
		try {
		    $conn->connect();
		    $conn->processUntil('session_start');
		    $conn->presence();
		    foreach ($channel_recipients as $recipient) {
		    	$conn->message(array_get_else($recipient,'user_name'), array_get_else($recipient,'message'),'chat',null,null,$this->mapped_channel_communication_fields['channel_short_code']);
		    	ENV::$log->debug(__METHOD__."send user [".array_get_else($recipient,'user_name')."] message via XMPP :\n".array_get_else($recipient,'message'));
		    }
		    $conn->disconnect();
		} catch(XMPPHP_Exception $e) {
		    ENV::$log->error(__METHOD__.': '.$e->getMessage());
		}
	}
}

?>