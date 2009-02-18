<?php echo($payload->transit_stop['desc']."\n") ?>
<?php foreach ($payload->arrivals as $arrival) {
	if (isset($arrival['estimated'])) {
		$arrival_time = $arrival['estimated'];
		$arrival_time_txt = ':';
	} else {
		$arrival_time = $arrival['scheduled'];
		$arrival_time_txt = ':sched:';
	}
	$time_to_arrival = round(($arrival_time - $payload->query_time)/60000);
	$arrival_time_txt .=  $time_to_arrival.'m';
	$short_sign = str_ireplace(' to ','To',$arrival['shortSign']);
	$short_sign = str_ireplace('portland','Prtlnd',$short_sign);
	$short_sign = str_ireplace('beaverton','Beavrtn',$short_sign);
?>
<?php echo($short_sign . $arrival_time_txt) ?><?php echo($arrival['detour']=='true' ? ":detour on route\n" : "\n") ?>
<?php } ?>