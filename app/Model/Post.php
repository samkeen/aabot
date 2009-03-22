<?php
class Model_Post extends Model_Base {

	protected $attribute_definitions = array(
		'name' => '/a-z0-9_- /i, min=4, max=255',
		'body' => 'text',
		'active' => 'boolean',
        'user_id' => 'int'
	);
	protected $relations = array (
		'has_and_belongs_to_many' => array(
            'tag' => array(
                'conditions' => array(
                    'is_active' => true
                )
            )

        ),
        'has_many' => array(
            'comment' => array(
                'conditions' => array(
                    'is_active' => true
                )
            )
        )
	);

}