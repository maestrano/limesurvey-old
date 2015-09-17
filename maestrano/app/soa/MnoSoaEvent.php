<?php

/**
 * Mno Event Class
 */
class MnoSoaEvent extends MnoSoaBaseEvent {
  protected static $_local_entity_name = "EVENT";

  protected function pushEvent() {
    // Events are not created locally
  }

  protected function pullEvent() {
    
  }

  public static function createLocalEntity() {
    $obj = (object) array();
    $obj->languages = 'en';
    return $obj;
  }

  public static function getLocalEntityByLocalIdentifier($local_id) {
    return MnoSurveyHelper::getLocalEntityByLocalIdentifier($local_id);
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    MnoSoaLogger::debug("start saveLocalEntity status=$status " . json_encode($this->_local_entity));
    MnoSurveyHelper::saveAsLabel('EVENTS', $this->_id, $this->_code, $this->_name, 'EV');
  }

  public function getLocalEntityIdentifier() {
    return (isset($this->_id)) ? $this->_id : $this->_local_entity->mno_uid;
  }
}

?>