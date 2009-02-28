<?php $payload->title = th('Edit User',false); ?>
<h1><?php th('Edit a User'); ?></h1>
<?php
$form->create('user');
$form->text('username');
$form->text('password');
$form->text('xmpp_jid');
$form->text('sms_number');
$form->text('age');
$form->select('group');
$form->close('Save');
?>