<h1>Channels</h1>
<table>
<tr>
<th>Id</th><th>Name</th><th>Shortcode</th><th>API Key</th><th>Keyword</th>
<th>Signature Key</th><th>API Hostname</th><th>API Domain</th><th>API Scheme</th><th>API Port</th>
<th>API Usernaem</th><th>API Password</th>
<th>Active</th><th>Created</th><th>Modified</th><th>action</th>
</tr>
<?php foreach ($payload->channels as $channel) {?>
<tr>
<td><?php h($channel['channel_id']); ?></td>
<td><?php h($channel['name']); ?></td>
<td><?php h($channel['short_code']); ?></td>
<td><?php h($channel['api_key']); ?></td>
<td><?php h($channel['keyword']); ?></td>
<td><?php h($channel['signature_key']); ?></td>
<td><?php h($channel['api_domain']); ?></td>
<td><?php h($channel['api_scheme']); ?></td>
<td><?php h($channel['api_port']); ?></td>
<td><?php h($channel['api_username']); ?></td>
<td><?php h($channel['api_password']); ?></td>
<td><?php h($channel['active']?'Y':'N'); ?></td>
<td><?php h($channel['created']); ?></td>
<td><?php h($channel['modified']); ?></td>
<td><a href="/channels/edit/<?php h($channel['channel_id']); ?>">edit</a> : <a href="/channels/delete/<?php h($channel['channel_id']); ?>">delete</a></td>
</tr>
<?php } ?>
</table>
<a href="/channels/add">Add Channel</a>