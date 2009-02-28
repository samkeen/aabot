<?php
/**
 * 
 * 
 *
 */
class Controller_Tags extends Controller_Base {
	
	protected function init() {
		
	}
	/**
	 * show list of users
	 */
	protected function index() {
		$tags = new Model_Tag();
		$this->payload->tags = $tags->find();
	}

	protected function view() {
		$user = new Model_Tag();
		$user->set('user_id',$this->arguments__first);
		$this->payload->user = new SimpleDTO($user->findOne());
	}
	
	protected function add() {
		if ($this->recieved_form_data) {
			$tag = new Model_Tag();
			if ($tag->save($this->form_data)) {
				$this->feedback = "The Tag has been created";
				$this->redirect('/tags');
			} else {
				$this->feedback = "There was a problem creating the Tag";
			}
		}	
	}
	protected function edit() {
		if ($this->recieved_form_data) {
			$tag = new Model_Tag();
			if ($tag->save($this->form_data)) {
				$this->feedback = "The Tag has been updated";
				$this->redirect('/tags');
			} else {
				$this->feedback = "There was a problem creating your Tag";
			}
		}
        if( ! $this->arguments__first) {
            $this->redirect('/tags');
        } else {
            $tag = new Model_Tag();
            $tag->set('tag_id',$this->arguments__first);
            $this->payload->tag = $tag->findOne();
        }
		
	}
	protected function delete() {
		$user = new Model_Tag();
		$user->set('user_id',$this->arguments__first);
		if ($user->delete()) {
			$this->feedback = "The User has been deleted";
			$this->redirect('/users.admin');
		} else {
			$this->feedback = "There was a problem deleting this user";
		}
		$this->payload->users = $user->find();
	}
	protected function over20() {
		$user = new Model_Tag();
		// could also be: $user->set('active','=',true);
		$user->set('active',true);
		$user->set('age','>','20');
		$this->set_template('admin/users/index');
		$this->payload->users = $user->find();
	}
}