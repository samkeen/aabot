# Controller #

## General Notes ##
  * For a controller class of the name Controller\_Foo, the name on the file system and used in code is Foo (Controller_is dropped)._



## the process method ##

called as `public function process($action)`

  1. set the template based on the $action
    * if $action is null look for default action (index.php)
    * else look for file /app/view/{controller}/{action}.php


