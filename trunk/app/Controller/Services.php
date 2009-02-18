<?php
/**
 * 
 * 
 *
 */
class Controller_Services extends Controller_Base {
	
	protected function init() {
		$this->default_response_type = CONSTS::$RESPONSE_HTML;
	}
	/**
	 * show list of services
	 */
	protected function index() {
		$service = new Model_Service();
		//$service->set('active',true);
		$this->payload->services = $service->find();
	}
	
	protected function add() {
		$service = new Model_Service();
		if ($this->recieved_form_data) {
			if ($service->save($this->form_data)) {
				$this->feedback = "The Service has been created";
				$this->redirect('/services');
			} else {
				$this->feedback = "There was a problem creating the service";
			}
		}
		$this->payload->profiles = $service->Profile(array('profile_id'=>'name'));
		// just display form	
	}
	protected function edit() {
		$service = new Model_Service();
		if ($this->recieved_form_data) {
			if ($service->save($this->form_data)) {
				$this->feedback = "The Service has been updated";
				$this->redirect('/services');
			} else {
				$this->feedback = "There was a problem creating the service";
			}
		}
		$service->set('service_id',$this->next_request_segment_value());
		$this->payload->profiles = $service->Profile(array('profile_id'=>'name'));
		$this->payload->service = $service->findOne();
	}
	protected function delete() {
		$service = new Model_Service();
		$service->set('service_id',$this->next_request_segment_value());
		if ($service->delete()) {
			$this->feedback = "The service has been deleted";
			$this->redirect('/services');
		} else {
			$this->feedback = "There was a problem deleting this service";
		}
		$this->payload->services = $service->find();
	}
	protected function over20() {
		$service = new Model_Service();
		// could also be: $service->set('active','=',true);
		$service->set('active',true);
		$service->set('age','>','20');
		$this->set_template('services/default');
		$this->payload->services = $service->find();
	}
}