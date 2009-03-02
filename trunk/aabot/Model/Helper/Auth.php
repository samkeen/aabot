<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Auth
 *
 * @author sam
 */
class Model_Helper_Auth {

    const USER_TABLE = 'user';
    const USERNAME_FIELD = 'username';
    const USER_PASSWORD_FEILD = 'password';
    const HASH_SALT = 'fhu&8(3bf76$@jjd-a *66D sjasdff[}J';

    const AUTHENTICATE_SESSION_KEY = '__auth';

    private $db_handle = null;

    public function loggedin() {
        $loggedin = false;
        if(isset($_SESSION[self::AUTHENTICATE_SESSION_KEY]) && $_SESSION[self::AUTHENTICATE_SESSION_KEY]) {
            $loggedin = true;
        }
        return $loggedin;
    }
    public function deauthenticate() {
        $_SESSION[self::AUTHENTICATE_SESSION_KEY]=null;
    }
    /**
     *
     * @param mixed $credential if array, considered ['username'=>'...','password'=>'...'
     * @param string $password
     * @return boolean
     */
    public function authenticate($credential, $password=null) {
        if(is_array($credential)) {
            $credential = array_get_else($credential, 'auth',$credential);
            $password = array_get_else($credential, self::USER_PASSWORD_FEILD);
            $credential = array_get_else($credential, self::USERNAME_FIELD);
        }
        $authenticated = false;
        try {
            $this->init_db();
            $statement_text = "SELECT `user_id` FROM `".self::USER_TABLE.'` WHERE '
                .self::USERNAME_FIELD.' = :username AND '.self::USER_PASSWORD_FEILD.' = :password';
            $statement = $this->db_handle->prepare($statement_text);
            $statement->bindValue(':username', $credential);
            $statement->bindValue(':password', $this->hash($password));
            ENV::$log->debug(__METHOD__." Executing {$statement_text}, ['{$credential}' , '...password...']");
            $statement->execute();
            $result = $statement->fetchColumn();
            if($result!==false) {
                $authenticated = true;
                $_SESSION[self::AUTHENTICATE_SESSION_KEY] = $result;
            }
        } catch (Exception $e) {
			ENV::$log->error(__METHOD__.'-'.$e->getMessage());
		}
        return $authenticated;
    }

    private function init_db() {
        if ($this->db_handle==null) {
            if($config = ENV::load_config_file('db_conf')) {
                $this->db_handle = new Model_DBHandle($config);
            } else {
                ENV::$log->error(__METHOD__.' Unable to load db config file');
            }
        }
	}

    private function hash($password) {
        return hash('sha1',$password.self::HASH_SALT);
    }
}
?>
