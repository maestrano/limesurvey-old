<?php

/**
 * Mno DB Map Interface
 */
class MnoSoaBaseDB {
    protected static $_db;
    
    public static function initialize($db=null)
    {
        static::$_db = $db;
    }

    public static function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function getLocalIdByMnoId($mno_id, $mno_entity_name, $local_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function deleteIdMapEntry($local_id, $local_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
}

?>