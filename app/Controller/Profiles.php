<?php
/**
 * 
 * 
 *
 */
class Controller_Profiles extends Controller_Base {
	
	protected function init() {
		$this->default_response_type = CONSTS::$RESPONSE_HTML;
	}
	/**
	 * show list of profiles
	 */
	protected function admin__index() {
		$profile = new Model_Profile();
		//$profile->set('active',true);
		$this->payload->profiles = $profile->find();
	}
	
	protected function admin__add() {
		$profile = new Model_Profile();
		if ($this->recieved_form_data) {
			if ($profile->save($this->form_data)) {
				$this->feedback = "The Profile has been created";
				$this->redirect('/profiles');
			} else {
				$this->feedback = "There was a problem creating the profile";
			}
		}
//		$this->payload->users = $profile->User(array('user_id'=>array('username','age')));
		$this->payload->users = $profile->User(array('user_id'=>'username'));	
	}
	protected function admin__edit() {
		if ($this->recieved_form_data) {
			$profile = new Model_Profile();
			if ($profile->save($this->form_data)) {
				$this->feedback = "The Profile has been updated";
				$this->redirect('/profiles');
			} else {
				$this->feedback = "There was a problem creating the profile";
			}
		}
		$profile = new Model_Profile();
		$this->payload->users = $profile->User(array('user_id'=>'username'));
		$profile->set('profile_id',$this->next_request_segment_value());
		$this->payload->profile = $profile->findOne();
	}
	protected function admin__delete() {
		$profile = new Model_Profile();
		$profile->set('profile_id',$this->next_request_segment_value());
		if ($profile->delete()) {
			$this->feedback = "The profile has been deleted";
			$this->redirect('/profiles');
		} else {
			$this->feedback = "There was a problem deleting this profile";
		}
		$this->payload->profiles = $profile->find();
	}
	protected function admin__over20() {
		$profile = new Model_Profile();
		// could also be: $profile->set('active','=',true);
		$profile->set('active',true);
		$profile->set('age','>','20');
		$this->set_template('profiles/default');
		$this->payload->profiles = $profile->find();
	}
}