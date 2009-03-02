<?php
/* 
 * These contenets are applied to all controllers
 */

$authorize_contexts = array(
    'admin' => array(
        'include' => array(
            'groups' => array(
                'admin' => array(
                    'include' => array(
                        'create', 'read', 'update', 'delete'
                    ),
                    'exclude' => array(
                        
                    )
                )
            ),
            'users' => array(

            )
        ),
        'exclude' => array(
            
        )
    )
)
?>
