<?php /* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
* SMS Helper class file.  Currrently only responsible for page forwarding.
*
* @package		Shizzow
*/

/**
* SMS Helper class.
*
* @package		Shizzow
* @subpackage	Helper
* @version		0.1.0
*/
class Extapi_Sms_Helper {
	
	/**
	 * feedback message templates
	 */
	// $1 = the favorite shortname they sent in their msg (i.e. foo in this ex: "#s foo")
	const PLACE_NOT_RECOGNIZED = 'The Place name "$1" was not recognized';
	const USERS_LOCATION_NOT_KNOW = 'We were unable to determine your current location.  You will need to use #s in your next message';
	// $1 = the command token (i.e. #s) they sent in their message
	const COMMAND_NOT_RECOGNIZED = 'The command: "$1" was not recognized';
	// 1$ = the message text they sent to shizzow
	const NO_ACTIONABLE_ITEMS_PARSED = 'Huh?? No commands were found in your statment: "$1"';
	// $1 = user name
	// $2 = sms service keyword
	// $3 = sms service shortcode
	const EVENT_ACCOUNT_ENABLED_SUCCESS = '$1, SMS is on for Shizzow.
To shout,txt "$2 #s {fav place short code}" to $3
See othr cmd\'s @ shizzow.com/sms';
	// 1$ = the message text they sent to shizzow
	const SMS_NOT_ACTVE_FOR_THIS_ACCOUNT = 'SMS is not active for your account. Go to http://shizzow.com/preferences/contact to enable';

	// $1 = user name
	const EVENT_ACCOUNT_ENABLED_FAIL = 'Sorry $1, your sms access activation failed, please contact support@shizzow.com';
	
	private $logger;
	
	// who do we know how to talk to
	private $know_sms_services = array('textmarks','zeep');
	
	// generalized conversation components we recieve from the SMS services.  (we get to these key names using the glossaries below)
	private $sms_conversation_fields = array('text'=>null,'service_keyword'=>null,'conversation_timestamp'=>null,'security_signature'=>null,'sms_user_id'=>null, 'sms_user_number' => null);
	
	// convert a particular service keynames to our generalized ones ($this->sms_conversation_fields)
	// FORMAT: array ( {their term} => {our term}, {their term} => {our term}, ... )
	private $textmarks_conversation_glossary = array('txt'=>'text','kw'=>'service_keyword','ts'=>'conversation_timestamp','sig'=>'security_signature','uid'=>'sms_user_id','event' => 'event');
	private $zeep_conversation_glossary = array('body'=>'text','sms_prefix'=>'service_keyword','ts'=>'conversation_timestamp','sig'=>'security_signature','uid'=>'sms_user_id','event' => 'event','min' => 'sms_user_number');
	
	// StatementParser object
	private $statement_parser = null;
	
	// result back from the $statement_parser
	/** example
	 * $parsed_results	Array [2]	
	0-s	Array [6]	
		token	(string:1) s	
		full_token	(string:6) #shout	
		recognized	(boolean) true	
		requested_action_successful	(boolean) false	
		feedback_message	null	
		subject	(string:2) gd	
	1-m	Array [6]	
		token	(string:1) m	
		full_token	(string:3)  #m	
		recognized	(boolean) true	
		requested_action_successful	(boolean) false	
		feedback_message	null	
		subject	(string:5) hello
	 */
	private $parsed_results;
	
	// is there a feedback message to send back to the user through SMS
	private $have_feedback = false;
	
	// feedback txt to sent back as a reply
	private $feedback_text;
	
	// determines if we are in the DEV keyword context or PROD keyward context
	public $in_dev_context = false;
	
	// output destined for SMS reply when in DEBUG mode
	private $debug_summary = 'DEBUG:';
	
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}
	/**
	 * Harvest the needed key/values from the $recieved_command_components for the given $sms_service
	 * 
	 * @param string $sms_service Name of the service we are currently talking with
	 * @param array $recieved_command_components Key/Values we've recieved from the SMS service
	 * @return boolean success||failure
	 */
	public function gather_conversation_components_for($sms_service, $recieved_command_components) {
		$sms_service = strtolower(trim($sms_service));
		$was_successful = false;
		if ( ! in_array($sms_service,$this->know_sms_services)) {
			$this->logger->error(__METHOD__.' passed commands from a service we do not know how to converse with.'
				.'  SERVICE: ['.$sms_service.'] KNOWN SERVICES: '.print_r($this->know_sms_services,1)
				.'RECEIVED COMMAND COMPONENTS: '.print_r($recieved_command_components,1));
		} else {		
			foreach ($this->{$sms_service.'_conversation_glossary'} as $service_fieldname => $our_fieldname) {
				$this->sms_conversation_fields[$our_fieldname] = isset($recieved_command_components[$service_fieldname]) 
					? trim($recieved_command_components[$service_fieldname])
					: null;
			}
			$this->in_dev_context = isset(Weave::instance()->sms['keyword_dev']) && $this->sms_conversation_fields['service_keyword'] == trim(Weave::instance()->sms['keyword_dev']);
			if ($this->in_dev_context) {
				$this->logger->debug("This request is In DEBUG context as the sms service keyword is: [".$this->sms_conversation_fields['service_keyword']."]");
			}
			if (isset($recieved_command_components['event'])&&$recieved_command_components['event'] = Weave::instance()->sms['event_account_update'] ) {
				$was_successful = isset($this->sms_conversation_fields['sms_user_number']) && isset($this->sms_conversation_fields['sms_user_id']);
			} else {
				$was_successful = isset($this->sms_conversation_fields['text']) && isset($this->sms_conversation_fields['sms_user_id']);
			}
		}
		if ($this->logger->debug()) {
			$this->logger->debug(__METHOD__.' Found conversation components for service:['.$sms_service.']'.print_r($this->sms_conversation_fields,1));
		}
		return $was_successful;
	}
	/**
	 * All the heavy lifting happens here. Tokenize and parse the statement and determine if we recognize the 
	 * requested action.  If so, enact the action(s)
	 * 
	 * @param array $user The user is needed to give context to actions (such as SHouting)
	 */
	public function parse_statement(array $user) {
		/*
		 * now look for command tokens in the conversation text
		 */
		$this->statement_parser = new Helper_StatementParser($this->sms_conversation_fields['text']);
		// if this is a account update message (user is enabling sms), perform update and return
		$this->logger->debug(__METHOD__.' $user:>'.print_r($user,1));

		if ($this->statement_is_account_update()) {
			$this->update_users_sms_account($user);
			return;
		// be sure that user has SMS enabled in Shizzow
		} else if ( ! $this->sms_active_for_user($user)) {
			$this->have_feedback = true;
			$this->feedback_text .= self::SMS_NOT_ACTVE_FOR_THIS_ACCOUNT;
			return;
		}
		if($this->statement_parser->parse()) {
			$this->parsed_results = $this->statement_parser->parsed_results();
			$this->apply_business_rules();
			foreach ($this->parsed_results as $index => $parsed_result) {
				if ($parsed_result['recognized']) {
					switch ($parsed_result['token']) {
						case 's': // shout
							if ($place = $this->determine_place($parsed_result['subject'],$user['people_id'], $index)) {
								$shouts_controller = new Controller_Shouts();
								$shouts_controller->shout($user['people_id'],$place['places_key'],$place['latitude'],$place['longitude'],array_get_else($parsed_result,'shout_message'),3);
								$this->debug_summary .= "\nshoutd frm \"".$place['places_name'].'": '.array_get_else($parsed_result,'shout_message');
								$this->parsed_results[$index]['requested_action_successful'] = true;
							} else {
								if ($parsed_result['subject']=='<current_location>') {
									$this->parsed_results[$index]['feedback_message'] = str_replace('$1',$parsed_result['subject'],self::USERS_LOCATION_NOT_KNOW);
								} else {
									$this->parsed_results[$index]['feedback_message'] = str_replace('$1',$parsed_result['subject'],self::PLACE_NOT_RECOGNIZED);
								}
								
								
								$this->have_feedback = true;
							}
							break;
					}
				} else {
					$this->parsed_results[$index]['feedback_message'] = str_replace('$1',$parsed_result['full_token'],self::COMMAND_NOT_RECOGNIZED);
					$this->have_feedback = true;
				}
			}
		} else {
			$this->have_feedback = true;
			$this->feedback_text .= str_replace('$1',$this->sms_conversation_fields['text'],self::NO_ACTIONABLE_ITEMS_PARSED);
		}
	}
	private function sms_active_for_user($user) {
		return array_notempty_else($user,'sms_number');
//		return isset($user['notify_on']) && isset($user['notify_method']) && $user['notify_on'] && $user['notify_method'] == 'sms';
	}
	private function statement_is_account_update() {
		return $this->sms_conversation_fields['event']==Weave::instance()->sms['event_account_update'];
	}
	private function update_users_sms_account($user) {
		$people_controller = new Controller_People();
		$update_values = array(
			'sms_uid' => $user['people_name'],
			'sms_number' => $this->sms_conversation_fields['sms_user_number'],
		);
		$this->have_feedback = true;
		if ( ! $people_controller->updateProfileValues($user['people_id'],$update_values)) {
			$this->logger->error(__METHOD__.' Failed to update the users profile in order to turn on SMS');
			$this->feedback_text .= str_replace('$1',$user['people_name'],self::EVENT_ACCOUNT_ENABLED_FAIL);
		} else {
			$this->feedback_text .= str_replace(array('$1','$2','$3'),
				array($user['people_name'],
					$this->sms_conversation_fields['service_keyword'],
					Weave::instance()->sms['service_shortcode']
				),
				self::EVENT_ACCOUNT_ENABLED_SUCCESS
			);
		}
	}
	/**
	 * Return the values needed for authen/author 
	 * 
	 * @return array Values we will need for authentication and authorization
	 */
	public function authentication_components() {
		return array('sms_user_id' => $this->sms_conversation_fields['sms_user_id'],
			'security_signature'=>$this->sms_conversation_fields['security_signature'],
			'conversation_timestamp'=>$this->sms_conversation_fields['conversation_timestamp'],
			'service_keyword' => trim($this->sms_conversation_fields['service_keyword'])
		);
	}
	/**
	 * commands we have parsed from a statement
	 * 
	 * @return array parsed commands
	 */
	public function parsed_commands() {
		return $this->parsed_results;
	}
	/**
	 * Passthrough authentication to the Shz Authentication Helper
	 * 
	 * @return array Attributes of the authenticated user
	 */
	public function authenticate_request($sms_service) {
		$user = Helper_Authentication::authenticateBySmsUid($sms_service, $this->authentication_components());
		if ( ! $user) {
			$this->debug_summary .= "\n Authentication and/or Authorization failed";
		}
		return $user;
	}
	/**
	 * build the security http header for a given sms service
	 */
	public static function generate_authorization_headers($service_name,$api_key, $signing_key, $message_parameters_string ) {
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
	 * Do we have feedback txt destined for SMS reply
	 * @return boolean
	 */
	public function has_feedback() {
		return $this->have_feedback;
	}
	/**
	 * Gather all the txt meant to be sent back as a SMS reply
	 */
	public function gather_feedback() {
		if ($this->parsed_results) {
			foreach ($this->parsed_results as $parsed_token) {
				$this->feedback_text .= !empty($parsed_token['feedback_message']) ? "\n".$parsed_token['feedback_message'] : '';
			}
		}
		return ltrim($this->feedback_text,"\n");
	}
	/**
	 * Debug txt to send back as SMS reply
	 */
	public function debug_summary() {
		return "\n".$this->debug_summary;
	}
	/**
	 * for related tokens we apply business rules
	 * ex: if their is a shout token (s), we look for message token(s) and
	 * set their message txt to the shout token's "shout_message" key.  We also unset 
	 * the message tokens in $this->parsed_results as we don't need them anymore
	 */
	private function apply_business_rules() {
		/*
		 * wed messages to shouts
		 */
		$shout_tokens = $this->contains_tokens_of_type('s');
		$message_tokens = $this->contains_tokens_of_type('m');
		// looking for "#s gd #m here for b & b"
		if($shout_tokens && $message_tokens) {
			$shout_message = '';
			foreach ($message_tokens as $index => $message_token) {
				$shout_key = key($shout_tokens);
				$shout_message .= ' '.$message_token['subject'];
				unset($this->parsed_results[$index]);
			}
			$this->parsed_results[$shout_key]['shout_message'] = trim($shout_message);
		}
		// looking for "#m here for b & b"
		if ($message_tokens && !$shout_tokens) {
			$shout_message = '';
			foreach ($message_tokens as $index => $message_token) {
				$shout_message .= ' '.$message_token['subject'];
				unset($this->parsed_results[$index]);
			}
			// create a shout on the fly, tagged that the shout location is the user's current location <current_location>
			$this->parsed_results['0-s']['token'] = 's';
			$this->parsed_results['0-s']['recognized'] = true;
			$this->parsed_results['0-s']['requested_action_successful'] = false;
			$this->parsed_results['0-s']['feedback_message'] = null;
			$this->parsed_results['0-s']['subject'] = '<current_location>';
			$this->parsed_results['0-s']['shout_message'] = trim($shout_message);
		}
		
	}
	/**
	 * 
	 * @param $type string ex: 'm', 's', ... 
	 */
	private function contains_tokens_of_type($type) {
		$tokens_of_type = null;
		foreach ($this->parsed_results as $result_key => $result) {
			if (strstr($result_key,'-'.$type)) {
				$tokens_of_type[$result_key] = $result;
			}
		}
		return $tokens_of_type;
	}
	private function determine_place($place_name, $user_id) {
		$place = null;
		$places_controller = new Controller_Places();
		if ($place_name=='<current_location>') {
			if($place = Helper_Shouts::knowPeoplesCurrentLocation($user_id)) {
				$place['places_name'] = '<current_location>';
			}
		} else {
			$place = $places_controller->get_place_by_favorite_name($place_name,$user_id);
		}
		return $place;
	}
	public static function text_service_response_is_error($resp) {
####### @todo Turn on once #743 fixed
		return FALSE;
		return strtoupper(trim($resp)) != 'OK';
	}
	
}
?>
