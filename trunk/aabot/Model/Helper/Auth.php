<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Auth
 * example session:
 * $_SESSION
    (
        [__feedback] => Array
            (
            )
        [__auth] => Array
            (
                [__id] => 1
                [__groups] => Array
                    (
                        [0] => Admins
                        [1] => Posters
                    )
            )
    )
 *
 * @author sam
 */
class Model_Helper_Auth {

    const USER_TABLE = 'user';
    const USERNAME_FIELD = 'username';
    const USER_PASSWORD_FIELD = 'password';
    const USER_GROUP_TABLE = 'group';
    const USER_GROUP_JOIN_TABLE = 'group_user';

    const AUTH_FAIL_REDIRECT = '/auth/login';
    
    const HASH_SALT = 'fhu&8(3bf76$@jjd-a *66D sjasdff[}J';

    const AUTHENTICATE_SESSION_KEY = '__auth';
    const AUTHENTICATE_ID_SESSION_KEY = '__id';
    const AUTHORIZE_GROUPS_SESSION_KEY = '__groups';
    const REQUESTED_ACTION_SESSION_KEY = '__requested';

    // public flag for controllers to find out why auth failed
    public $failed_for = 'authentication'; // could also be 'authorization'


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
    /**
     *
     * @var array default actions to authorize for __owner call
     */
    private $owner_actions_to_authorize = array('edit','delete');
    private $authorize_for_owner = false;

    private $db_handle = null;
    private $model_context_name;
    private $model_context_id;

    public function  __construct($model_name, $model_id) {
        $this->model_context_name = $model_name;
        $this->model_context_id = $model_id;
    }
    /**
     *
     * @return int the id of the user logged in or false if no user logged in;
     */
    public function logged_in() {
        $loggedin = false;
        if(isset($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::AUTHENTICATE_ID_SESSION_KEY])) {
            $loggedin = (bool)intval(($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::AUTHENTICATE_ID_SESSION_KEY]));
        }
        return $loggedin;
    }
    public function post_authenticate_url() {
        $url = '/';
        if(isset($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::REQUESTED_ACTION_SESSION_KEY])
            && !empty ($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::REQUESTED_ACTION_SESSION_KEY])) {
            $url = '/'.trim($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::REQUESTED_ACTION_SESSION_KEY],' /');
        }
        return $url;
    }
    /**
     * Determin if the logged in user has a particular group.
     * 
     * @param string $group_name
     * @return boolean
     */
    public function in_group($group_name) {
        $in_group = false;
        if($this->logged_in()) {
            $groups = array_get_else($_SESSION[self::AUTHENTICATE_SESSION_KEY][self::AUTHORIZE_GROUPS_SESSION_KEY], array());
        }
        return in_array($group_name, $groups);
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
            $password = array_get_else($credential, self::USER_PASSWORD_FIELD);
            $credential = array_get_else($credential, self::USERNAME_FIELD);
        }
        $authenticated = false;
        $logged_in_user_id = null;
        try {
            $this->init_db();
            $statement_text = "SELECT `user_id` FROM `".self::USER_TABLE.'` WHERE '
                .self::USERNAME_FIELD.' = :username AND '.self::USER_PASSWORD_FIELD.' = :password';
            $statement = $this->db_handle->prepare($statement_text);
            $statement->bindValue(':username', $credential);
            $statement->bindValue(':password', $this->hash($password));
            ENV::$log->debug(__METHOD__." Executing {$statement_text}, ['{$credential}' , '...password...']");
            $statement->execute();
            $logged_in_user_id = $statement->fetchColumn();
            if($logged_in_user_id!==false) {
                $authenticated = true;
                $_SESSION[self::AUTHENTICATE_SESSION_KEY][self::AUTHENTICATE_ID_SESSION_KEY] = $logged_in_user_id;
            }
            if($authenticated) { //get groups
                $statement_text = "SELECT g.`name`
                    FROM `".self::USER_GROUP_TABLE."` g
                    JOIN `".self::USER_GROUP_JOIN_TABLE."` gu ON g.`group_id` = gu.`group_id`
                    WHERE gu.`user_id` = :user_id";
                $statement = $this->db_handle->prepare($statement_text);
                $statement->bindValue(':user_id', $logged_in_user_id);
                ENV::$log->debug(__METHOD__." Executing {$statement_text}, ['{$logged_in_user_id}']");
                $statement->execute();
                $groups = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                if(is_array($groups)) {
                    $_SESSION[self::AUTHENTICATE_SESSION_KEY][self::AUTHORIZE_GROUPS_SESSION_KEY] = $groups;
                }
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
     * special cases:
     *  - 'admin__*'  : wildcard to match all actions starting with 'admin__'
     */
    public function authorize($authorized_groups, $actions_to_authorize=null) {
        $authorized_groups = ! is_array($authorized_groups)
                ? explode(',', $authorized_groups)
                :$authorized_groups;
        if(current($authorized_groups)=='__owner') {
           $actions_to_authorize = $actions_to_authorize===null ? $this->owner_actions_to_authorize : $actions_to_authorize;
           $this->authorize_for_owner = true;
        } else if($actions_to_authorize===null) {
           $actions_to_authorize = array('__ALL');
        } else {
            $actions_to_authorize = !is_array($actions_to_authorize)
                ? explode(',', $actions_to_authorize)
                :$actions_to_authorize;
        }
        $this->actions_to_authorize[] = array('actions'=>$actions_to_authorize, 'groups' => $authorized_groups);
    }

    public function validate_credentials($requested_action, $requested_path) {
        if($requested_action=='login' || $requested_action=='logout') {
            return true;
        }
        $_SESSION[self::AUTHENTICATE_SESSION_KEY][self::REQUESTED_ACTION_SESSION_KEY]=$requested_path;
        $valid_credentials = false;
        // if no authentication set, return true
        if(empty ($this->actions_to_authenticate) 
                || ! in_array('__ALL', $this->actions_to_authenticate)
                && ! in_array($requested_action, $this->actions_to_authenticate)) {

            ENV::$log->debug(__METHOD__. "Requested action [{$requested_action}] passed authentication.  Not found in actions_to_authenticate ["
                .implode(', ',$this->actions_to_authenticate)."]");
           $valid_credentials = true;
        } else if(in_array('__ALL', $this->actions_to_authenticate) || in_array($requested_action, $this->actions_to_authenticate)) {
            if($this->logged_in()) {
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
        $has_group = null;
        $found_authorization_rule_for_action = false;
        $logged_in_groups = array_get_else($_SESSION[self::AUTHENTICATE_SESSION_KEY],self::AUTHORIZE_GROUPS_SESSION_KEY, array());
        // first check if we are doing a special case '__owner' auth.
        if( $this->authorize_for_owner
            && in_array($requested_action, $this->owner_actions_to_authorize)
            && $this->is_owner()) {
            ENV::$log->debug(__METHOD__." Logged in user [".$this->logged_in()."] found to be owner of model [{$this->model_context_name}] with id [{$this->model_context_id}]");
            return true;
        }
        foreach ($this->actions_to_authorize as $authorizations) {
            if(in_array('__ALL', $authorizations['actions']) || in_array($requested_action, $authorizations['actions']) )  {
                if(ENV::$log->debug()){
                    ENV::$log->debug(__METHOD__." Authorizing agaist first found mathch for action [{$requested_action}] "
                        ." actions[".implode(', ', $authorizations['actions'])."] => authorizations [".implode(', ', $authorizations['groups'])."]");
                }
                $found_authorization_rule_for_action = true;
                $has_group = array_intersect($logged_in_groups, $authorizations['groups']);
                if(!empty($has_group)) {
                    break;
                }
            }
        }
        if( ! $found_authorization_rule_for_action) {
            ENV::$log->debug(__METHOD__." no authorization rules found for requested action [{$requested_action}] ");
            $has_group = true;
        } else if(ENV::$log->debug() && !empty($has_group)){
            ENV::$log->debug(__METHOD__." User authorized for action [{$requested_action}] "
                ." since they have group(s) [".implode(', ', $has_group)."]");
        } else if(empty($has_group)) {
            ENV::$log->debug(__METHOD__." authorization for action [{$requested_action}] "
                ." failed for logged in user with group(s) [".implode(', ', $logged_in_groups)."] did not Intersect a required group ["
                .implode(', ', $authorizations['groups'])."]");
            $this->failed_for = 'authorization';
        }
        return !empty($has_group);
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

    private function is_owner() {
        if($this->model_context_name===null || $this->model_context_id===null) {
            ENV::$log->error(__METHOD__." model name [{$this->model_context_name}] and/or model id [{$this->model_context_id}] empty. cannot authorize __owner");
            return false;
        } else {
            $owned = false;
            if($logged_in_user_id = $this->logged_in()) {
                $this->init_db();
                $statement_text = "SELECT `user_id` FROM `{$this->model_context_name}` WHERE `{$this->model_context_name}_id` = :posession_id AND `user_id` = :user_id";
                $statement = $this->db_handle->prepare($statement_text);
                $statement->bindValue(':posession_id', $this->model_context_id);
                $statement->bindValue(':user_id', $logged_in_user_id);
                ENV::$log->debug(__METHOD__." Executing {$statement_text}, ['{$this->model_context_id}', '{$logged_in_user_id}']");
                $statement->execute();
                $owned = (bool)$statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        }
        return $owned;
    }
}
?>
