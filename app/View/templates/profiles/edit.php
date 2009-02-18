<?php $payload->title = th('Edit Profile',false); ?>
<h1><?php th('Edit Profile'); ?></h1>
<?php 
$form->create('profile','edit');
$form->text('name');
$form->text('active');
$form->select('user_id');
$form->close('Save');
?>