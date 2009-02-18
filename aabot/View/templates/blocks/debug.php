<div id="debug-block">
<dl>
<dt>Log Statements For This Requesst</dt>
<?php foreach (ENV::$log->buffered_statements() as $log_statement) { ?>
	<dd><pre><?php h($log_statement); ?></pre></dd>
<?php } ?>
</dl>
</div>