<?php
/**
 * 
 * 
 *
 */
class Controller_Posts extends Controller_Base {
	
	protected function init() {
		
	}
	/**
	 * show list of users
	 */
	protected function index() {
		$posts = new Model_Post();
		$this->payload->posts = $posts->find();
	}

	protected function view() {
		$user = new Model_Post();
		$user->set('user_id',$this->arguments__first);
		$this->payload->user = new SimpleDTO($user->findOne());
	}
	
	protected function add() {
		if ($this->recieved_form_data) {
			$post = new Model_Post();
			if ($post->save($this->form_data)) {
				$this->feedback = "The Post has been created";
				$this->redirect('/posts');
			} else {
				$this->feedback = "There was a problem creating the Post";
			}
		}	
	}
	protected function edit() {
		if ($this->recieved_form_data) {
			$post = new Model_Post();
			if ($post->save($this->form_data)) {
				$this->feedback = "The Post has been updated";
				$this->redirect('/posts');
			} else {
				$this->feedback = "There was a problem creating your Post";
			}
		}
        if( ! $this->arguments__first) {
            $this->redirect('/posts');
        } else {
            $post = new Model_Post();
            $post->set('post_id',$this->arguments__first);
            $this->payload->post = $post->findOne();
        }
		
	}
	protected function delete() {
		$user = new Model_Post();
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
		$user = new Model_Post();
		// could also be: $user->set('active','=',true);
		$user->set('active',true);
		$user->set('age','>','20');
		$this->set_template('admin/users/index');
		$this->payload->users = $user->find();
	}
}