<?php $payload->title = th('Profiles',false); ?>
<h1>Profiles</h1>
<table>
<tr>
<th>Id</th><th>Name</th><th>Active</th><th>Created</th><th>Modified</th><th>action</th>
</tr>
<?php foreach ($payload->profiles as $profile) { ?>
<tr>
<td><?php h($profile['profile_id']); ?></td>
<td><?php h($profile['name']); ?></td>
<td><?php h($profile['active']?'Y':'N'); ?></td>
<td><?php h($profile['created']); ?></td>
<td><?php h($profile['modified']); ?></td>
<td><a href="/profiles/edit/<?php h($profile['profile_id']); ?>">edit</a> : <a href="/profiles/delete/<?php h($profile['profile_id']); ?>">delete</a></td>
</tr>
<?php } ?>
</table>
<a href="/profiles/add">Add Profile</a>