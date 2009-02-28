<?php $payload->title = th('Add Post',false); ?>
<h1><?php th('Add a new Post'); ?></h1>
<?php
$form->create('post','add');
$form->text('name');
$form->text('body',array('textarea'));
$form->text('active');
$form->select('tag');
$form->close('Save');
?>