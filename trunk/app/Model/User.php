<?php
class Model_User extends Model_Base {
	
	protected $attribute_definitions = array(
		'username' => '/a-z0-9_- /i, min=4, max=50',
		'password' => 'min=4, max=100',
		'xmpp_jid' => 'email',
		'sms_number' => 'numeric, min=12, max=12', // 018005551212
		'age' => 'integer, min=1, max=150',
		'active' => 'boolean'
	);
	protected $relations = array (
		'has_and_belongs_to_many' => array(
            'group' => array(
                'conditions' => array(
                    'is_active'
                )
            )

        )
	);

}