<?php $payload->title = th('Add Tag',false); ?>
<h1><?php th('Add a new Tag'); ?></h1>
<?php
$form->create('tag','add');
$form->text('name');
$form->text('active');
$form->close('Save');
?>