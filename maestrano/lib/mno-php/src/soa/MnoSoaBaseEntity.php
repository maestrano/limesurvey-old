<?php

/**
 * Mno Entity Interface
 */
class MnoSoaBaseEntity extends MnoSoaBaseHelper
{                   
    const STATUS_ERROR = 1;
    const STATUS_NEW_ID = 2;
    const STATUS_EXISTING_ID = 3;
    const STATUS_DELETED_ID = 4;
    
    protected $_local_entity;
    protected static $_local_entity_name;
    protected static $_mno_entity_name;
    
    protected $_create_rest_entity_name;
    protected $_create_http_operation;
    protected $_update_rest_entity_name;
    protected $_update_http_operation;
    protected $_receive_rest_entity_name;
    protected $_receive_http_operation;
    protected $_delete_rest_entity_name;
    protected $_delete_http_operation;
    
    protected $_enable_delete_notifications=false;
    
    
    public function __construct() {
        global $opts;
        
        MnoSoaDB::initialize($opts['db_connection']);
        MnoSoaLogger::initialize();
    }
    
    /**************************************************************************
     *                         ABSTRACT METHODS                               *
     **************************************************************************/
    
    /**
    * Build a Maestrano entity message
    * 
    * @return MaestranoEntity the maestrano entity json object
    */
    protected function build() 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    protected function persist($mno_entity) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function getLocalEntityIdentifier() 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public static function getLocalEntityByLocalIdentifier($local_id)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public static function createLocalEntity()
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function getUpdates($timestamp) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function process_notification($notification)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    /**************************************************************************
     *                       COMMON INHERITED METHODS                         *
     **************************************************************************/
    
    public static function getLocalEntityName()
    {
        return static::$_local_entity_name;
    }
    
    protected static function getMnoEntityName()
    {
        return static::$_mno_entity_name;
    }
    
    protected function pushId() 
    {
        $local_id = $this->getLocalEntityIdentifier();
        if (empty($local_id)) {
          MnoSoaLogger::debug(__FUNCTION__ . " no local id, skipping");
            return;
        }
        
        $mno_id = MnoSoaDB::getMnoIdByLocalId($local_id, static::getLocalEntityName(), static::getMnoEntityName());
        if (!$this->isValidIdentifier($mno_id)) {
            return;
        }
        
        $this->_id = $mno_id->_id;
    }
    
    /**
    * Translate Maestrano identifier to local identifier
    * 
    * @return Status code 
    *           STATUS_ERROR -> Error
    *           STATUS_NEW_ID -> New identifier
    *           STATUS_EXISTING_ID -> Existing identifier
    *           STATUS_DELETED_ID -> Deleted identifier
    */
    protected function pullId() 
    {
      if (!empty($this->_id)) {
        $local_id = MnoSoaDB::getLocalIdByMnoId($this->_id, static::getMnoEntityName(), static::getLocalEntityName());
      
        if ($this->isValidIdentifier($local_id)) {
            $this->_local_entity = static::getLocalEntityByLocalIdentifier($local_id->_id);
            return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
        } else if ($this->isDeletedIdentifier($local_id)) {
            return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
        } else {
            $this->_local_entity = static::createLocalEntity();
            return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
        }
      }
      return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    public static function createGUID()
    {
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $guid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $guid;
    }
    
    /**************************************************************************
     *                            REST API METHODS                            *
     **************************************************************************/
    
    public function send($local_entity)
    {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        $this->_local_entity = $local_entity;
        
        $message = $this->build();
        $message = json_encode($message);
        $mno_had_no_id = empty($this->_id);
        if ($mno_had_no_id) {
            MnoSoaLogger::debug(__FUNCTION__ . " create new entity against url " . $this->_create_rest_entity_name);
            $response = $this->callMaestrano($this->_create_http_operation, $this->_create_rest_entity_name, $message);
        } else {
            MnoSoaLogger::debug(__FUNCTION__ . " update entity against url " . $this->_update_rest_entity_name . '/' . $this->_id);
            $response = $this->callMaestrano($this->_update_http_operation, $this->_update_rest_entity_name . '/' . $this->_id, $message);
        }
        
        if (empty($response)) {
            return false;
        }
  
        $local_entity_id = $this->getLocalEntityIdentifier();
        $local_entity_now_has_id = !empty($local_entity_id);
        
        $mno_response_id = $response->id;
        $mno_response_has_id = !empty($mno_response_id);
        if ($mno_had_no_id && $local_entity_now_has_id && $mno_response_has_id) {
            MnoSoaDB::addIdMapEntry($local_entity_id, static::getLocalEntityName(), $mno_response_id, static::getMnoEntityName());
        }
        
        MnoSoaLogger::debug(__FUNCTION__ . " end");
        return $mno_response_id;
    }
    
    public function receive($mno_entity) 
    {
        return $this->persist($mno_entity);
    }
    
    public function receiveNotification($notification) {
        $mno_entity = $this->callMaestrano($this->_receive_http_operation, $this->_receive_rest_entity_name . '/' . $notification->id);

        if (empty($mno_entity)) { return false; }
        
        return $this->receive($mno_entity);
    }
    
    public function sendDeleteNotification($local_id) 
    {
        MnoSoaLogger::debug(__FUNCTION__ .  " start local_id = " . $local_id);
        $mno_id = MnoSoaDB::getMnoIdByLocalId($local_id, $this->getLocalEntityName(), $this->getMnoEntityName());
  
        if ($this->isValidIdentifier($mno_id)) {
            MnoSoaLogger::debug(__FUNCTION__ . " corresponding mno_id = " . $mno_id->_id);
            
            if ($this->_enable_delete_notifications) {
                $response = $this->callMaestrano($this->_delete_http_operation, $this->_delete_rest_entity_name . '/' . $mno_id->_id);
                if (empty($response)) { 
                    return false; 
                }
            }
            
            MnoSoaDB::deleteIdMapEntry($local_id, $this->getLocalEntityName());
            MnoSoaLogger::debug(__FUNCTION__ .  " after deleting ID entry");
        }
        
        return true;
    }
    
    /**
     * Send/retrieve data from Maestrano integration service
     *
     * @param HTTPOperation {"POST", "PUT", "GET", "DELETE"}
     * @param String EntityName
     * @param JSON Request payload
     * @return JSON Response payload
     */
    protected function callMaestrano($operation, $entity, $msg='')
    {            
      MnoSoaLogger::debug(__FUNCTION__ .  " start");
      $maestrano = MaestranoService::getInstance();
      $url = $maestrano->getSoaUrl();
      $curl = curl_init($url . $entity);
      MnoSoaLogger::debug(__FUNCTION__ . " path = " . $url . $entity);
      MnoSoaLogger::debug(__FUNCTION__ . " maestrano msg = ".$msg);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic " . base64_encode($GLOBALS['api_key'].":".$GLOBALS['api_secret']), "Content-type: application/json"));
      curl_setopt($curl, CURLOPT_TIMEOUT, '600');
      
      MnoSoaLogger::debug(__FUNCTION__ . " before switch");
      
      switch ($operation) {
    case "POST":
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);
        break;
    case "PUT":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);
        break;
    case "GET":
        break;
          case "DELETE":
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
              break;
      }

      MnoSoaLogger::debug(__FUNCTION__ . " before curl_exec");
      $response = trim(curl_exec($curl));
      MnoSoaLogger::debug(__FUNCTION__ . " after curl_exec");
      MnoSoaLogger::debug(__FUNCTION__ . " response = ". $response);
      $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      
      MnoSoaLogger::debug(__FUNCTION__ . " status = ". $status);
      
      if ( $status != 200 ) {
            MnoSoaLogger::debug(__FUNCTION__ . " Error: call to URL $url failed with status $status, response $response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl), 0);
            curl_close($curl);
            return null;
      }

      curl_close($curl);

      $response = json_decode($response, false);
      
      return $response;
    }
}
?>