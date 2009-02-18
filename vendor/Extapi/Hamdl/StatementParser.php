<?php /* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * CommandLaguageParser helper class file. 
 *
 * @package		Shizzow
 */

/**
 * StatementParser helper class
 *
 * @package		Shizzow
 * @subpackage	Helper
 * @version		0.1.0
 */
class Hamdl_StatementParser {
	
	
	private $parsed_command_tokens = null;

	private $hash_token_dialect = array(
		// the structure of a token for this particular dialect
		'token_structure' => array(
			'pattern' => '/ ?#(\w+)/is',
			'token_index' => 1,
			'full_token_index' => 0
		),
		// tokens we know
		'tokens' => array(
			// message
			'm' => array(
				'pattern' => '/ ?#(m\W|message)(.*)$/is',
				'name' => 'message',
				// index in the preg_match array that will hold the message subject (the msg text)
				'subject_index' => 2
			),
			// shout
			's' => array(
				// looking for: '#s gd we are here for b & b' shout with implicit message
				'agro-pattern' => "/ ?#(s\W|shout) ?([\w\.'-_!~]+)([^#]*)$/is",
				// looking for: '#s gd'
				'pattern' => "/ ?#(s\W|shout) ?([\w\.'-_!~]+).*$/is",
				'name' => 'shout',
				// index in the preg_match array that will hold the shout subject (place shortname)
				'subject_index' => 2
			)
		),
		'aliases' => array('shout'=>'s','message'=>'m')
		
	);
	// the recieved statment text we are to attempt to parse
	private $statement_text;
	
	public function __construct($statement_text) {
		$this->statement_text = $statement_text;
	}
	/**
	 * Make first pass of the statment and parse out any tokens that match the token_structure for this dialect
	 * 
	 * @return boolean True if we find >0 tokens
	 */
	public function parse() {
		$was_successful = false;
		$full_token_index = $this->hash_token_dialect['token_structure']['full_token_index'];
		if(preg_match_all($this->hash_token_dialect['token_structure']['pattern'],$this->statement_text,$matches) ) {
			foreach ($matches[$this->hash_token_dialect['token_structure']['token_index']] as $index => $match) {
				$match = strtolower($match);
				if(strlen($match)>3) {
					$match = $this->check_for_token_alias($match);
				}
				$this->parsed_command_tokens[$index.'-'.$match]['token'] = $match;
				$this->parsed_command_tokens[$index.'-'.$match]['full_token'] = $matches[$full_token_index][$index];
				$this->parsed_command_tokens[$index.'-'.$match]['recognized'] = key_exists($match,$this->hash_token_dialect['tokens']);
				$this->parsed_command_tokens[$index.'-'.$match]['requested_action_successful'] = false;
				$this->parsed_command_tokens[$index.'-'.$match]['feedback_message'] = null;		
			}
			$this->parse_particular_command_tokens();
			$was_successful = true;
		}
		return $was_successful;
	}
	/**
	 * now for each token discovered in $this->parse(), parse its related subject from the statement
	 * -ex for statement "#s gd", the run of $this->parse() will pull out the token "s".  Then a pass through 
	 * this function will pull out the subject of "s" in this statement: "gd"
	 */
	private function parse_particular_command_tokens() {
		$matches = null;
		foreach ($this->parsed_command_tokens as $index => &$command_token) {
			if ($command_token['recognized']) {
				preg_match($this->hash_token_dialect['tokens'][$command_token['token']]['pattern'],$this->statement_text,$matches);
				$command_token['subject'] = $matches[$this->hash_token_dialect['tokens'][$command_token['token']]['subject_index']];
			}
		}	
	}
	/**
	 * accessor for the parsed tokens that have been parsed
	 * 
	 * @return array
	 */
	public function parsed_results() {
		return $this->parsed_command_tokens;
	}
	/**
	 * mutator for the parsed tokens.  Allows fields like 'requested_action_successful' to be updated 
	 * by other classes.
	 * 
	 * @param string $token_key The index key of the token we are mutating
	 * @param string $token_value_key 
	 * @param string $updated_value
	 */
//	public function update_parsed_token_value($token_key, $token_value_key, $updated_value) {
//		$updated = false;
//		if (isset($this->parsed_command_tokens[$token_key])) {
//			$this->parsed_command_tokens[$token_key][$token_value_key] =  $updated_value;
//			$updated = true;
//		}
//		return $updated;
//	}
	private function check_for_token_alias($token_name) {
		return isset($this->hash_token_dialect['aliases'][$token_name]) ? $this->hash_token_dialect['aliases'][$token_name] : $token_name;
	}
}