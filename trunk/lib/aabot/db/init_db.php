<?php
/**
 * Initialize the database 
 * - create the schema version table
 */
$init_db = array(
	"up" => "
		CREATE TABLE IF NOT EXISTS aabot (
			schema_version TINYINT DEFAULT 0 COMMENT 'the current migration version of the site schema'
		)
		CHARACTER SET utf8
		COMMENT = 'Table to store various meta data about a abbot site'
	"
	);
///phpinfo();
$db_conf = json_decode(file_get_contents('../../../db/database.conf'));
$json = '{"a":1,"b":2,"c":3,"d":4,"e":5}';

//var_dump($db_conf->development);

//var_dump(json_decode(file_get_contents('../../../db/database.conf'), true));
//die;
Migrator::initDB($db_conf);
?>