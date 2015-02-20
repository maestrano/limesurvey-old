<?php

/**
 * Mno EventOrder Class
 */
class MnoSoaEventOrder extends MnoSoaBaseEventOrder {
  protected static $_local_entity_name = "EVENT_ORDER";

  protected function pushEventOrder() {
    $this->_status = $this->_local_entity->status;
    $this->_event_id = $this->_local_entity->event_id;
    $this->_person_id = $this->_local_entity->person_id;
    $this->_cost = $this->_local_entity->cost;
    $this->_fee = $this->_local_entity->fee;
    $this->_attendees = $this->_local_entity->attendees;
  }

  protected function pullEventOrder() {
  }

  public static function getLocalEntityByLocalIdentifier($local_id) {
    return MnoSurveyHelper::getLocalEntityByLocalIdentifier($local_id);
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    // Event Orders not saved locally
  }

  public function getLocalEntityIdentifier() {
    return 0;
  }
}

?>
