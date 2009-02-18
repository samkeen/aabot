<?php
class Controller_Pdxt extends Controller_Base {
	
	protected function init() {
		$this->default_response_type = CONSTS::$RESPONSE_TEXT;
	}
	protected function index() {
		$this->payload->message = "Hello, this is the PDXt controller";
		$this->payload->controller = print_r($this,1);
	}
	protected function sms() {
		switch ($this->next_request_segment_value()) {
			case 'receiver':
				$this->sms_receiver();
			break;
			
			default:
				// bypass the action template for the controller template
				$this->set_template('pdxt.php');
				$this->payload->message = "passed through the SMS action";
			break;
		}
	}
	protected function xmpp() {
		switch ($this->next_request_segment_value()) {
			case 'receiver':
				$this->xmpp_receiver();
			break;
			
			default:
				// bypass the action template for the controller template
				$this->set_template('pdxt.php');
				$this->payload->message = "passed through the XMPP action";
			break;
		}
		
	}
	protected function register() {
		$sms_channel = Util_VendorFactory::get_instance('extapi/channel/zeep', $this->router);
		$this->payload->zeep_channel = $sms_channel->config();
		$this->payload->user_id = 'samkeen';
	}
/*
 * Subscription ping
 * $_REQUESTArray
(
    [;c;] => pdxt/sms/receiver
    [sms_prefix] => pdxtt
    [short_code] => 88147
    [uid] => samkeen
    [min] => +15034733242
    [event] => SUBSCRIPTION_UPDATE
)
 * 
 * ?sms_prefix=pdxtt&short_code=88147&uid=samkeen&min=+15034733242&event=SUBSCRIPTION_UPDATE
 */

/*
 * user message
 * $_REQUESTArray
(
    [;c;] => pdxt/sms/receiver
    [sms_prefix] => pdxtt
    [short_code] => 88147
    [uid] => samkeen
    [body] => hello
    [min] => +15034733242
    [event] => MO
)
 * 
 * ?sms_prefix=pdxtt&short_code=88147&uid=samkeen&body=hello&min=+15034733242&event=MO
 */
	private function sms_receiver() {
		$this->use_layout = false;
		$requesting_channel = $this->next_request_segment_value();
		header('Content-type: text/plain',true);
		ENV::$log->debug('$_REQUEST'.print_r($_REQUEST,1));
		$sms_channel = Util_VendorFactory::get_instance('extapi/channel/'.$requesting_channel, $this->router);
		if ($sms_channel && $sms_channel->have_required_request_params() && $sms_channel->authenticate_request()) {
			ENV::load_vendor_file('Extapi/Service/Tmet');
			$tmet_service = new Extapi_Service_Tmet($sms_channel);
			$tmet_service->parse_request_statement();
			$tmet_service->act_on_request_statement();			
			if ($tmet_service->has_feedback()) {
				$arrivals = $tmet_service->gather_feedback();
				$this->payload->arrivals = array_get_else($arrivals,'arrivals');
				$this->payload->transit_stop = array_get_else($arrivals,'transit_stop');
				$this->payload->query_time = array_get_else($arrivals,'query_time');
			} else {
				$this->viewless();
			}
		} else {
			if (! $sms_channel) {
				ENV::$log->error(__METHOD__.' Util_VendorFactory::get_instance failed for [extapi/channel/'.$requesting_channel.']');
			} else {
				ENV::$log->notice(__METHOD__.' Required components were not found and/or authentcation failed for this request');
			}
			// don't respond to these requests.
			$this->viewless();
		}
	}
/*
 *     
 *  [from] => sam.sjk@gmail.com
    [from_resource] => AdiumA3314218
    [to] => pdxtt@extapi.com
    [message] => 1419
    [id] => purple6abd8054
    [subject] => 
    [thread] => 
    [type] => chat

extapi.com/pdxt/xmpp/receiver/x2http.xml?from=sam.sjk%40gmail.com&to=pdxtt%40extapi.com&message=1419&id=purple2145455a&subject=&thread=&type=chat
 */	
	private function xmpp_receiver() {
		$this->use_layout = false;
		$requesting_channel = $this->next_request_segment_value();
		ENV::$log->debug('$_REQUEST'.print_r($_REQUEST,1));
		$xmpp_channel = Util_VendorFactory::get_instance('extapi/channel/'.$requesting_channel, $this->router);
		
		if ($xmpp_channel && $xmpp_channel->have_required_request_params() && $xmpp_channel->authenticate_request()) {
			ENV::load_vendor_file('Extapi/Service/Tmet');
			$tmet_service = new Extapi_Service_Tmet($xmpp_channel);
			$tmet_service->enact();
			if ($tmet_service->has_feedback()) {	
				$arrivals = $tmet_service->gather_feedback();
				$this->payload->arrivals = array_get_else($arrivals,'arrivals');
				$this->payload->transit_stop = array_get_else($arrivals,'transit_stop');
				$this->payload->query_time = array_get_else($arrivals,'query_time');
				// let render to browser if html requested (.html)
				if($this->requested_response_type != CONSTS::$RESPONSE_HTML) {
					$xmpp_channel->send_channel_message($this->get_rendered_view());
					$this->viewless();
				}
			} else {
				$this->viewless();
			}
		} else {
			if (! $xmpp_channel) {
				ENV::$log->error(__METHOD__.' Util_VendorFactory::get_instance failed for [extapi/channel/'.$requesting_channel.']');
			} else {
				ENV::$log->notice(__METHOD__.' Required components were not found and/or authentcation failed for this request');
			}
			// don't respond to these requests.
			$this->viewless();
		}
	}

}