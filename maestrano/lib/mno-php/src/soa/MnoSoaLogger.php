<?php

/**
 * Mno DB Map Interface
 */
class MnoSoaLogger {
    protected static $_app_prefix = "";
    
    public static function initialize()
    {
        if (empty(self::$_app_prefix)) {
            $maestrano = MaestranoService::getInstance();
            self::$_app_prefix = $maestrano->getSettings()->app_id;
        }
    }
    
    public static function debug($msg) 
    {
        error_log(self::$_app_prefix . " [debug] " . $msg);
    }
    
    public static function warn($msg)
    {
        error_log(self::$_app_prefix . " [warn] " . $msg);
    }
    
    public static function error($msg)
    {
        error_log(self::$_app_prefix . " [error] " . $msg);
    }
    
    public static function info($msg)
    {
        error_log(self::$_app_prefix . " [info] " . $msg);
    }
}

?>