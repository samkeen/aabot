<?php echo($payload->transit_stop['desc']."\n") ?>
<?php foreach ($payload->arrivals as $arrival) {
	if (isset($arrival['estimated'])) {
		$arrival_time = $arrival['estimated'];
		$arrival_time_txt = ':';
	} else {
		$arrival_time = $arrival['scheduled'];
		$arrival_time_txt = ' (scheduled) ';
	}
	$time_to_arrival = round(($arrival_time - $payload->query_time)/60000);
	$arrival_time_txt .=  $time_to_arrival.' minutes';
	$short_sign = array_get_else($arrival, 'shortSign');
?>
	<?php echo($short_sign . $arrival_time_txt) ?><?php echo($arrival['detour']=='true' ? ":detour on route\n" : "\n") ?>
<?php } ?>