<?php $payload->title = th('Edit Service',false); ?>
<h1><?php th('Edit Service'); ?></h1>
<?php 
$form->create('service','edit');
$form->text('name');
$form->text('api_key');
$form->text('api_uri');
$form->text('active');
$form->select('profile_id');
$form->close('Save');
?>