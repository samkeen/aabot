<h1>Register</h1>
<?php if(array_get_else($payload->zeep_channel, 'api_key')) { ?>
<iframe 
  style="width: 100%; height: 300px; border: none;" 
  id="zeep_mobile_settings_panel" 
  src="https://secure.zeepmobile.com/subscription/settings?api_key=<?php echo array_get_else($payload->zeep_channel, 'api_key'); ?>&user_id=<?php echo $payload->user_id; ?>"
>
</iframe>
<?php } ?>