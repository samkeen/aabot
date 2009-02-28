<?php $payload->title = th('New User Registration',false); ?>
<h1><?php th('Register a new User'); ?></h1>
<?php
$form->create('user','add');
$form->text('username');
$form->text('password');
$form->text('xmpp_jid');
$form->text('sms_number');
$form->text('age');
$form->select('group',array('top'=>'Select a Group'));
$form->close('Add');
?>