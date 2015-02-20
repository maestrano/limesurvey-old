<?php

/**
 * Mno EventOrder Interface
 */
class MnoSoaBaseEventOrder extends MnoSoaBaseEntity {
  protected static $_mno_entity_name = "eventOrders";
  protected $_create_rest_entity_name = "event_orders";
  protected $_create_http_operation = "POST";
  protected $_update_rest_entity_name = "event_orders";
  protected $_update_http_operation = "POST";
  protected $_receive_rest_entity_name = "event_orders";
  protected $_receive_http_operation = "GET";
  protected $_delete_rest_entity_name = "event_orders";
  protected $_delete_http_operation = "DELETE";    
  
  protected $_id;
  protected $_code;
  protected $_status;
  protected $_event_id;
  protected $_person_id;
  protected $_cost;
  protected $_fee;
  protected $_attendees;

  protected function pushEventOrder() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoEventOrder class!');
  }
  
  protected function pullEventOrder() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoEventOrder class!');
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoEventOrder class!');
  }
  
  public function getLocalEntityIdentifier() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoEventOrder class!');
  }
  
  protected function build() {
    MnoSoaLogger::debug("start");
    
    $this->pushEventOrder();

    if ($this->_code != null) { $msg['eventOrder']->code = $this->_code; }
    if ($this->_status != null) { $msg['eventOrder']->status = $this->_status; }
    if ($this->_description != null) { $msg['eventOrder']->description = $this->_description; }
    if ($this->_event_id != null) { $msg['eventOrder']->event->id = $this->_event_id; }
    if ($this->_person_id != null) { $msg['eventOrder']->person->id = $this->_person_id; }
    if ($this->_cost != null) { $msg['eventOrder']->cost = $this->_cost; }
    if ($this->_fee != null) { $msg['eventOrder']->fee = $this->_fee; }
    if ($this->_attendees != null) { $msg['eventOrder']->attendees = $this->_attendees; }

    $result = json_encode($msg['eventOrder']);

    MnoSoaLogger::debug("result = $result");

    return $msg['eventOrder'];
  }
  
  protected function persist($mno_entity) {
    MnoSoaLogger::debug("start");
    
    if (!empty($mno_entity->eventOrder)) {
      $mno_entity = $mno_entity->eventOrder;
    }
    
    if (!empty($mno_entity->id)) {
      $this->_id = $mno_entity->id;
      $this->set_if_array_key_has_value($this->_code, 'code', $mno_entity);
      $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);
      $this->set_if_array_key_has_value($this->_description, 'description', $mno_entity);
      $this->set_if_array_key_has_value($this->_event_id, 'id', $mno_entity->event);
      $this->set_if_array_key_has_value($this->_person_id, 'id', $mno_entity->person);
      $this->set_if_array_key_has_value($this->_cost, 'cost', $mno_entity);
      $this->set_if_array_key_has_value($this->_fee, 'fee', $mno_entity);

      if (!empty($mno_entity->ticketClasses)) {
        $this->set_if_array_key_has_value($this->_attendees, 'attendees', $mno_entity);
      }

      MnoSoaLogger::debug("id = " . $this->_id);

      $status = $this->pullId();
      MnoSoaLogger::debug("after pullId");
      
      if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID') || $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
        $this->saveLocalEntity(false, $status);

        // Map event order ID
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
          $local_entity_id = $this->getLocalEntityIdentifier();
          $mno_entity_id = $this->_id;
          MnoSoaDB::addIdMapEntry($local_entity_id, static::getLocalEntityName(), $mno_entity_id, static::getMnoEntityName());
        }
      }
    }
    MnoSoaLogger::debug("end");
  }
}

?>