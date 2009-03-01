<?php
/* 
 * $this->feedback->
 */

/**
 * Feedback is a wrapper around the SESSION to hold feedback messages
 * meant for display to the user
 *
 * @author sam
 */
class Controller_Helper_Feedback {

    const SESSION_KEY = '__feedback';
    const DEFAULT_FEEDBACK_KEY = '__message';

    public function  __construct() {
        $_SESSION[self::SESSION_KEY]= isset($_SESSION[self::SESSION_KEY])?$_SESSION[self::SESSION_KEY]:array();
    }

    public function add() {
        // if 2 args, consider $key, $value
        if(func_num_args()==2) {
            $_SESSION[self::SESSION_KEY][func_get_arg(0)][]=func_get_arg(1);
        } else { // consider $value
            $_SESSION[self::SESSION_KEY][self::DEFAULT_FEEDBACK_KEY][]=func_get_arg(0);
        }
    }
    /**
     * clear all feedback or for a specific key if given
     */
    public function clear($key=null) {
        if($key!==null) {
            unset($_SESSION[self::SESSION_KEY][$key]);
        } else {
            $_SESSION[self::SESSION_KEY] = array();
        }
    }
    /**
     *
     * @param mixed $key [optional]
     * @return array Note the array is removed from session once it is read
     */
    public function get($key=null) {
        $feedback = array();
        // if 2 args, consider $key
        if($key!==null && array_get_else($_SESSION[self::SESSION_KEY],$key)) {
            $feedback = $_SESSION[self::SESSION_KEY][$key];
            unset($_SESSION[self::SESSION_KEY][$key]);
        } else { // consider $value
            $feedback = array_get_else($_SESSION[self::SESSION_KEY],self::DEFAULT_FEEDBACK_KEY);
            $_SESSION[self::SESSION_KEY] = array();
        }
        return $feedback;
    }
}
?>
