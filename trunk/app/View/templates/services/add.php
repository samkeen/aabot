<?php $payload->title = th('Add Service',false); ?>
<h1><?php th('Register a new Service'); ?></h1>
<?php
$form->create('service','add');
$form->text('name');
$form->text('api_key');
$form->text('api_uri');
$form->text('active');
$form->select('profile_id');
$form->close('Add');
?>