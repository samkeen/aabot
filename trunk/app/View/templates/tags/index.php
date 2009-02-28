<?php $payload->title = th('Tags',false); ?>
<h1>Tags</h1>
<table>
<tr>
<th>Name</th><th>Active</th><th>Created</th><th>Modified</th><th>action</th>
</tr>
<?php foreach ($payload->tags as $tag) { ?>
<tr>
<td><?php h($tag['name']); ?></td>
<td><?php h($tag['active']?'Y':'N'); ?></td>
<td><?php h($tag['created']); ?></td>
<td><?php h($tag['modified']); ?></td>
<td><a href="/tags/edit/<?php h($tag['tag_id']); ?>">edit</a> : <a href="/tags/delete/<?php h($tag['tag_id']); ?>">delete</a></td>
</tr>
<?php } ?>
</table>
<a href="/tags/add">Add Tag</a>