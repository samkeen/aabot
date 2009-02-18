<?php
class Model_Service extends Model_Base {
	
	protected $attribute_definitions = array(
		'name' => '/a-z0-9_- /i, min=4, max=50',
		'api_key' => '/a-z0-9_-/i, min=1, max=100',
		'api_uri' => 'http_uri,min=6, max=100',
		'active' => 'boolean'
	);
	protected $relations = array (
		'belongs_to' => 'Profile'
	);

}