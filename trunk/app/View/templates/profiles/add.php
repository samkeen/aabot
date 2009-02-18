<?php $payload->title = th('Add Profile',false); ?>
<h1><?php th('Add a new Profile'); ?></h1>
<?php
$form->create('profile','add');
$form->text('name');
$form->text('active');
$form->select('user_id');
$form->close('Add');
?>