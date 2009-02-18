<?php
/**
 * 
 * 
 *
 */
class Controller_Channels extends Controller_Base {
	
	protected function init() {
		$this->default_response_type = CONSTS::$RESPONSE_HTML;
	}
	/**
	 * show list of channels
	 */
	protected function index() {
		$channel = new Model_Channel();
		$this->payload->channels = $channel->find();
	}
	
	protected function add() {
		$channel = new Model_Channel();
		if ($this->recieved_form_data) {
			if ($channel->save($this->form_data)) {
				$this->feedback = "The Channel has been created";
				$this->redirect('/channels');
			} else {
				$this->feedback = "There was a problem creating the channel";
			}
		}
		$this->payload->profiles = $channel->Profile(array('profile_id'=>'name'));
		// just display form	
	}
	protected function edit() {
		$channel = new Model_Channel();
		if ($this->recieved_form_data) {
			if ($channel->save($this->form_data)) {
				$this->feedback = "The Channel has been updated";
				$this->redirect('/channels');
			} else {
				$this->feedback = "There was a problem creating the channel";
			}
		}
		$channel->set('channel_id',$this->next_request_segment_value());
		$this->payload->profiles = $channel->Profile(array('profile_id'=>'name'));
		$this->payload->channel = $channel->findOne();
	}
	protected function delete() {
		$channel = new Model_Channel();
		$channel->set('channel_id',$this->next_request_segment_value());
		if ($channel->delete()) {
			$this->feedback = "The channel has been deleted";
			$this->redirect('/channels');
		} else {
			$this->feedback = "There was a problem deleting this channel";
		}
		$this->payload->channels = $channel->find();
	}
	protected function over20() {
		$channel = new Model_Channel();
		// could also be: $channel->set('active','=',true);
		$channel->set('active',true);
		$channel->set('age','>','20');
		$this->set_template('channels/default');
		$this->payload->channels = $channel->find();
	}
}