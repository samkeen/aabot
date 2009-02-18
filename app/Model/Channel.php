<?php
class Model_Channel extends Model_Base {
	
	protected $attribute_definitions = array(
		'name' => '/a-z0-9_- /i, min=4, max=50',
		'short_code' => '/a-z0-9_- /i, min=1, max=25',
		'api_key' => '/a-z0-9_-/i, min=1, max=100',
		'keyword' => '/a-z0-9_-/i, min=1, max=100',
		'signature_key' => '/a-z0-9_-/i, min=1, max=100',
		'api_hostname' => '/a-z0-9_-\./i,min=6, max=100',
		'api_domain' => '/a-z0-9_-\./i,min=6, max=100',
		'api_scheme' => '/(http)|(https)/',
		'api_port' => 'int, min=10, max=10000',
		'api_username' => '/a-z0-9_- /i, min=4, max=50',
		'api_password' => 'min=4, max=50',
		'active' => 'boolean'
	);
	protected $relations = array (
		'belongs_to' => 'Profile'
	);

}