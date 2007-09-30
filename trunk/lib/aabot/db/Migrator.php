<?php
/**
 * 
 */
class Migrator {
	
	public static function initDB($db_conf) {
  	
  }
	
  protected function runUp() {}
  protected function runDown() {}
  
  protected function connect() {
  	try {
			$dbh = new PDO('mysql:host=localhost;dbname=test', $user, $pass);
			foreach ($dbh->query('SELECT * from FOO') as $row) {
			  print_r($row);
			}
			$dbh = null;
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			}
		}

}
?>