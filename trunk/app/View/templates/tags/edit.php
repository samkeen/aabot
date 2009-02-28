<?php $payload->title = th('Edit Tag',false); ?>
<h1><?php th('Edit a Tag'); ?></h1>
<?php
$form->create('tag','edit');
$form->text('name');
$form->text('active');
$form->close('Save');
?>