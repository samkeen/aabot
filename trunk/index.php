<?php
$context_path = '/www/aabot';
$log_file_path = $context_path.'/log/default.log';
$logger = new Log_Logger(new Log_File_Writer($log_file_path),Config::LOG_LEVEL);
$logger->debug("Logger Awake");

function __autoload($class) {
	global $context_path;
	if(file_exists($context_path . "/app/" . str_replace('_', '/', $class) . '.php')) {
		require $context_path . "/app/" . str_replace('_', '/', $class) . '.php';
	} else {
		require $context_path . "/lib/aabot/classes/" . str_replace('_', '/', $class) . '.php';
	}
}
$env = new Env($context_path);
$env->logger = $logger;
$bs = new Bootstrapper($env);
$bs->strap();
?>