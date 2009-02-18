<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Naming
 *
 * @author sam
 */
class Util_Naming {
    public static function modelize($name) {
        return 'Model_'.ucfirst($name);
    }
}
?>
