<h1>View User</h1>
<?php $user = $payload->user; ?>
<dl>
<dt><?php h($user->username); ?> (<?php h($user->user_id); ?>)</dt>
<dd>SMS Number: <?php h($user->sms_number); ?></dd>
<dd>JID: <?php h($user->xmpp_jid); ?></dd>
<dd>Age: <?php h($user->age); ?></dd>
<dd>Active: <?php h($user->active?'Y':'N'); ?></dd>
<?php if($user->Profile) { 
	foreach ($user->Profiles as $profile) { ?>

<?php }
} ?>
</dl>
<a href="/admin/users">All Users</a>