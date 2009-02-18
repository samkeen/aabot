<?php
// @todo determine why were're needing a require onece and why not looking in .
require_once (dirname(__FILE__).'/Base.php');

class Extapi_Service_Tmet extends Extapi_Service_Base {
	
	private $stop_id = null;
	private $vehicle_id = null;

	/**
	 * 
	 */
	function __construct(Extapi_Channel_Base $channel) {
		parent::__construct($channel);
		$this->load_config('services','tmet');
		$this->http_util = new Util_Http();
	}

	/**
	 * 
	 * @see Extapi_Service_Base::parse_request_statement()
	 */
	public function parse_request_statement() {
		$statement_parts = explode(' ',array_get_else($this->channel->mapped_channel_communication_fields,'text',array()));
		$this->stop_id = array_get_else($statement_parts,0);
		$this->vehicle_id = array_get_else($statement_parts,1);
	}
	
	/**
	 * 
	 * @see Extapi_Service_Base::act_on_request_statement()
	 */
	public function act_on_request_statement() {
		$this->feedback = $this->get_arrivals($this->stop_id, $this->vehicle_id);
	}
	
	/**
	 * 
	 * @see Extapi_Service_Base::gather_feedback()
	 */
	public function gather_feedback() {
		return $this->feedback;
	}
	
	private function get_arrivals($stop_id=null, $vehicle_id=null) {
		$transit_service_resp = null;
		global $logger;
		if( $stop_id===null) {
			$logger->warn(__METHOD__.' No StopId provided, unable to process request');
		} else {
			$arrivals_api_url = $this->config_get('api_uri').'/arrivals?';
			$transit_service_resp = $this->http_util->get($arrivals_api_url,array('locIDs'=>urlencode($this->stop_id),'appID'=>$this->config_get('api_key')));
			$transit_service_resp = $this->parse_response($transit_service_resp);
			if ($transit_service_resp && $vehicle_id !== null) {
				// filter out all vehicles except the one requested
				
			}
		}
		return $transit_service_resp; // array('stop_info'=>..., 'transit_arrivals_data' => ...)
	}

	/**
	 * @todo refactor tmet::parse_response. verify that we are getting all the usefull info
	 * - consider a generalized xml parser: send it an array structure of values we are expecting and
	 * have it reture the structure populated
	 */
	private function parse_response($transit_service_resp) {
		global $logger;
		$parsed_response = null;
		if ($transit_service_resp) {
			$resp_xml_obj = null;
			try {
				$resp_xml_obj = @new SimpleXMLElement($transit_service_resp);
				$parsed_response['query_time'] = isset($resp_xml_obj['queryTime'])?(double)$resp_xml_obj['queryTime']:time();
				$location = $this->get_attributes_for_node($resp_xml_obj->location);
				if ($location) {
					$parsed_response['transit_stop']['desc'] = array_get_else($location,'desc');
					$parsed_response['transit_stop']['direction'] = array_get_else($location,'dir');
					$parsed_response['transit_stop']['latitude'] = array_get_else($location,'lat');
					$parsed_response['transit_stop']['longitude'] = array_get_else($location,'lng');
					$parsed_response['transit_stop']['stop_id'] = array_get_else($location,'locid');
				}
				if (isset($resp_xml_obj->arrival)) {
					foreach ($resp_xml_obj->arrival as $arrival) {
						$this_arrival = $this->get_attributes_for_node($arrival);
						$this_arrival['block_position'] = $this->get_attributes_for_node($arrival->blockPosition);
						$this_arrival['block_position']['trip'] = $this->get_attributes_for_node($arrival->blockPosition->trip);
						$parsed_response['arrivals'][] = $this_arrival;
					}
				}
			} catch (Exception $e) {
				$logger->error(__METHOD__.' Unble to parse transit response as XML: '.$e->getMessage());
			}
		}
		return $parsed_response; // array('stop_info'=>..., 'transit_arrivals_data' => ...)
	}
	private static function get_attributes_for_node($element_node) {
		$attributes = (array)$element_node;
		return isset($attributes['@attributes']) ? $attributes['@attributes'] : null;
	}
	

}

?>