<?php $payload->title = th('Edit Post',false); ?>
<h1><?php th('Edit a Post'); ?></h1>
<?php
$form->create('post','edit');
$form->text('name');
$form->text('body',array('textarea'));
$form->text('active');
$form->close('Save');
?>