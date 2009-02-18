<?php $payload->title = th('Services',false); ?>
<h1>Services</h1>
<table>
<tr>
<th>Id</th><th>Name</th><th>API Key</th><th>API URI</th>
<th>Active</th><th>Created</th><th>Modified</th><th>action</th>
</tr>
<?php foreach ($payload->services as $service) { ?>
<tr>
<td><?php h($service['service_id']); ?></td>
<td><?php h($service['name']); ?></td>
<td><?php h($service['api_key']); ?></td>
<td><?php h($service['api_uri']); ?></td>
<td><?php h($service['active']?'Y':'N'); ?></td>
<td><?php h($service['created']); ?></td>
<td><?php h($service['modified']); ?></td>
<td><a href="/services/edit/<?php h($service['service_id']); ?>">edit</a> : <a href="/services/delete/<?php h($service['service_id']); ?>">delete</a></td>
</tr>
<?php } ?>
</table>
<a href="/services/add">Add Service</a>