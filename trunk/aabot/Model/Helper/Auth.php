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
    /*
     * ex: array(
     *  0 => 'action_1',
     *  1 => 'action_2'
     *  )
     */
    private $actions_to_authenticate = array();
    /*
     * ex: array(
     *  0 => array(
     *      'actions' => array('add','edit'),
     *      'groups' => array('steward', 'admin')
     *  ),
     *  1 => array(
     *      'actions' => array('delete'),
     *      'groups' => array('admin')
     *  )
     *  first match found is acted on (parsing starts at zero)
     */
    private $actions_to_authorize = array();

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
    public function login($credential, $password=null) {
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
    /**
     * Meant to be called in a Controller's init() method to set up what will
     * authenticated.
     *
     * @param mixed [optiona] $actions_to_authenticate If given can be array or
     * comma delim string.  If null, considered all actions
     */
    public function authenticate($actions_to_authenticate=null) {
        if($actions_to_authenticate===null) {
           $this->actions_to_authenticate = array('__ALL');
        } else {
            $this->actions_to_authenticate = !is_array($actions_to_authenticate)
                ? explode(',', $actions_to_authenticate)
                :$actions_to_authenticate;
        }
    }
    /**
     * Meant to be called in a Controller's init() method to set up what will
     * authorized.
     *
     * @param mixed $authorized_groups array or commma delim string
     * @param mixed [optional] $actions_to_authorize array or commma
     * delim string.    If null, considered all actions
     */
    public function authorize($authorized_groups, $actions_to_authorize=null) {
        $authorized_groups = !is_array($authorized_groups)
                ? explode(',', $authorized_groups)
                :$authorized_groups;
        if($actions_to_authorize===null) {
           $actions_to_authorize = array('__ALL');
        } else {
            $actions_to_authorize = !is_array($actions_to_authorize)
                ? explode(',', $actions_to_authorize)
                :$actions_to_authorize;
        }
        $this->actions_to_authorize[] = array('actions'=>$actions_to_authorize, 'groups' => $authorized_groups);
    }

    public function validate_credentials($requested_action) {
        $valid_credentials = false;
        // if no authentication set, return true
        if(empty ($this->actions_to_authenticate) || ! in_array($requested_action, $this->actions_to_authenticate)) {
            ENV::$log->debug(__METHOD__. "Requested action [{$requested_action}] passed authentication.  Not found in actions_to_authenticate ["
                .implode(', ',$this->actions_to_authenticate)."]");
           $valid_credentials = true;
        } else if(in_array('__ALL', $this->actions_to_authenticate) || in_array($requested_action, $this->actions_to_authenticate)) {
            if($this->loggedin()) {
                // check for authorization
                if(empty ($this->actions_to_authorize)) {
                    $valid_credentials = true;
                } else {
                    $valid_credentials = $this->has_group_for_action($requested_action);
                }
            }
        }
        return $valid_credentials;
    }
    private function has_group_for_action($requested_action) {

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
