<?php $payload->title = th('New Channel',false); ?>
<h1><?php th('Register a new Channel'); ?></h1>
<?php
$form->create('channel','add');
$form->text('name');
$form->text('shortcode');
$form->text('api_key');
$form->text('keyword');
$form->text('signature_key');
$form->text('api_domain');
$form->text('api_scheme');
$form->text('api_port');
$form->text('api_username');
$form->text('api_password');
$form->select('profile_id');
$form->close('Add');
?>