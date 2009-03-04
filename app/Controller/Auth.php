<?php
/**
 * 
 * 
 *
 */
class Controller_Auth extends Controller_Base {
	
	protected function init() {
		$this->default_response_type = CONSTS::$RESPONSE_HTML;
	}
	/**
	 * show list of users
	 */
	protected function login() {
        if ($this->recieved_form_data) {
            if($this->auth->login($this->form_data)) {
                $this->redirect('/');
            } else {
                $this->feedback->add("Login failed, Username and/or Password was incorrect");
			}
        }
	}

	protected function logout() {
		$this->auth->deauthenticate();
		$this->feedback->add("Logout successful");
        $this->redirect('/');
	}
	
	protected function admin__add() {
		if ($this->recieved_form_data) {
			$user = new Model_User();
			if ($user->save($this->form_data)) {
				$this->feedback = "The User has been created";
				$this->redirect('/users.admin');
			} else {
				$this->feedback = "There was a problem creating the user";
			}
		}	
	}
	protected function admin__edit() {
		if ($this->recieved_form_data) {
			$user = new Model_User();
			if ($user->save($this->form_data)) {
                $this->feedback->add("The User has been updated");
				$this->redirect('/users.admin');
			} else {
                $this->feedback->add('error', "There was a problem creating your account");
			}
		}
		$user = new Model_User();
		$user->set('user_id',$this->arguments__first);
		$this->payload->user = $user->findOne();
	}
	protected function admin__delete() {
		$user = new Model_User();
		$user->set('user_id',$this->arguments__first);
		if ($user->delete()) {
			$this->feedback = "The User has been deleted";
			$this->redirect('/users.admin');
		} else {
			$this->feedback = "There was a problem deleting this user";
		}
		$this->payload->users = $user->find();
	}
	protected function admin__over20() {
		$user = new Model_User();
		// could also be: $user->set('active','=',true);
		$user->set('active',true);
		$user->set('age','>','20');
		$this->set_template('admin/users/index');
		$this->payload->users = $user->find();
	}
}