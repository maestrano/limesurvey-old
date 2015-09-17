<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaDB extends MnoSoaBaseDB {
    /**
    * Update identifier map table
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    * @param	string	mno_id                  Maestrano entity identifier
    * @param	string	mno_entity_name         Maestrano entity name
    *
    * @return 	boolean Record inserted
    */    
    public static function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) {	
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " start - local_id: " . $local_id . ", local_entity_name: " . strtoupper($local_entity_name) . ", mno_id: " . $mno_id . ", mno_entity_name: " . strtoupper($mno_entity_name));
        $query = "INSERT INTO mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) 
                  VALUES 
                  (:mno_entity_guid,:mno_entity_name,:app_entity_id,:app_entity_name,UTC_TIMESTAMP)";
        $result=Yii::app()->db->createCommand($query)
                    ->bindValue(":mno_entity_guid", $mno_id)
                    ->bindValue(":mno_entity_name", strtoupper($mno_entity_name))
                    ->bindValue(":app_entity_id", $local_id)
                    ->bindValue(":app_entity_name", strtoupper($local_entity_name))
                    ->query();
        $id = getLastInsertID('{{mno_id_map}}');
        MnoSoaLogger::debug("addIdMapEntry query = ".$query);
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " end");
        
        return true;
    }
    
    /**
    * Get Maestrano GUID when provided with a local identifier
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    *
    * @return 	boolean Record found	
    */
    
    public static function getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name)
    {
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
        $mno_entity = null;
        
	// Fetch record
	$query = "SELECT mno_entity_guid, mno_entity_name, deleted_flag "
                . "FROM mno_id_map "
                . "WHERE app_entity_id=:app_entity_id"
                . " and app_entity_name=:app_entity_name"
                . " and mno_entity_name=:mno_entity_name";
    $result = Yii::app()->db->createCommand($query)
                ->bindValue(":app_entity_id", $local_id)
                ->bindValue(":app_entity_name", strtoupper($local_entity_name))
                ->bindValue(":mno_entity_name", strtoupper($mno_entity_name))
                ->queryRow();
        
	// Return id value
	if (count($result) != 0) {
            $mno_entity_guid = trim($result["mno_entity_guid"]);
            $mno_entity_name = trim($result["mno_entity_name"]);
            $deleted_flag = trim($result["deleted_flag"]);
            
            if (!empty($mno_entity_guid) && !empty($mno_entity_name)) {
                $mno_entity = (object) array (
                    "_id" => $mno_entity_guid,
                    "_entity" => $mno_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
        
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " returning mno_entity = ".json_encode($mno_entity));
	return $mno_entity;
    }
    
    public static function getLocalIdByMnoId($mno_id, $mno_entity_name, $local_entity_name)
    {
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
	$local_entity = null;
        
	// Fetch record
	$query = "SELECT app_entity_id, app_entity_name, deleted_flag "
                . "FROM mno_id_map "
                . "WHERE mno_entity_guid=:mno_entity_guid"
                ." and mno_entity_name=:mno_entity_name"
                ." and app_entity_name=:app_entity_name";
        $result = Yii::app()->db->createCommand($query)
                    ->bindValue(":mno_entity_guid", $mno_id)
                    ->bindValue(":mno_entity_name", strtoupper($mno_entity_name))
                    ->bindValue(":app_entity_name", strtoupper($local_entity_name))
                    ->queryRow();
        
	// Return id value
	if (count($result) != 0) {
            $app_entity_id = trim($result["app_entity_id"]);
            $app_entity_name = trim($result["app_entity_name"]);
            $deleted_flag = trim($result["deleted_flag"]);
            
            if (!empty($app_entity_id) && !empty($app_entity_name)) {
                $local_entity = (object) array (
                    "_id" => $app_entity_id,
                    "_entity" => $app_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
	
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " returning mno_entity = ".json_encode($local_entity));
	return $local_entity;
    }
    
    public static function deleteIdMapEntry($local_id, $local_entity_name) 
    {
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
        // Logically delete record
        $query = "UPDATE mno_id_map "
                . "SET deleted_flag=1 "
                . "WHERE app_entity_id=:app_entity_id"
                ." and app_entity_name=:app_entity_name";
        $result = Yii::app()->db->createCommand($query)
                    ->bindValue(":app_entity_id", $local_id)
                    ->bindValue(":app_entity_name", strtoupper($local_entity_name))
                    ->query();
        
        MnoSoaLogger::debug("deleteIdMapEntry query = ".$query);
        
        return true;
    }
}

?>