<?php
class Model_Tag extends Model_Base {

	protected $attribute_definitions = array(
		'name' => '/a-z0-9_- /i, min=4, max=50',
		'active' => 'boolean'
	);
	protected $relations = array (
		'belongs_to' => array(
            'post' => array(
                'conditions' => array(
                    'is_active'
                )
            )

        )
	);

}