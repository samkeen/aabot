<?php $payload->title = th('Login',false); ?>
<h1><?php th('Login'); ?></h1>
<?php
$form->create('auth','login');
$form->text('username');
$form->text('password');
$form->close('login');
?>