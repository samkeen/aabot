<?php $payload->title = th('Posts',false); ?>
<h1>Posts</h1>
<table>
<tr>
<th>Name</th><th>Body</th><th>Active</th><th>Created</th><th>Modified</th><th>action</th>
</tr>
<?php foreach ($payload->posts as $post) { ?>
<tr>
<td><?php h($post['name']); ?></td>
<td><?php h($post['body']); ?></td>
<td><?php h($post['active']?'Y':'N'); ?></td>
<td><?php h($post['created']); ?></td>
<td><?php h($post['modified']); ?></td>
<td><a href="/posts/edit/<?php h($post['post_id']); ?>">edit</a> : <a href="/posts/delete/<?php h($post['post_id']); ?>">delete</a></td>
</tr>
<?php } ?>
</table>
<a href="/posts/add">Add Post</a>