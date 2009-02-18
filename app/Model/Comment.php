<?php
class Model_Comment extends Model_Base {

	protected $attribute_definitions = array(
		'name' => 'min=4, max=255',
		'body' => 'text',
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