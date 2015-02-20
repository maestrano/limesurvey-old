<?php

/**
 * Mno Organization Class
 */
class MnoSoaOrganization extends MnoSoaBaseOrganization
{
  protected static $_local_entity_name = "ORGANIZATION";
  
  protected function pushName() {
    $this->_name = $this->_local_entity->name;
  }
  
  protected function pullName() {
    $this->_local_entity->name = $this->pull_set_or_delete_value($this->_name);
  }
  
  protected function pushIndustry() {
  }
  
  protected function pullIndustry() {
  }
  
  protected function pushAnnualRevenue() {
  }
  
  protected function pullAnnualRevenue() {
  }
  
  protected function pushCapital() {
  }
  
  protected function pullCapital() {
  }
  
  protected function pushNumberOfEmployees() {
  }
  
  protected function pullNumberOfEmployees() {
  }
  
  protected function pushAddresses() {
  }
  
  protected function pullAddresses() {
  }
  
  protected function pushEmails() {
  }
  
  protected function pullEmails() {
  }
  
  protected function pushTelephones() {
  }
  
  protected function pullTelephones() {
  }
  
  protected function pushWebsites() {
  }
  
  protected function pullWebsites() {
  }
  
  protected function pushEntity() {
  }
  
  protected function pullEntity() {
  }
  
  protected function saveLocalEntity($push_to_maestrano, $status) {
    $label = $this->saveAsLabel();
  }
  
  public function getLocalEntityIdentifier() {
    return (isset($this->_id)) ? $this->_id : $this->_local_entity->mno_uid;
  }
  
  public static function getLocalEntityByLocalIdentifier($local_id) {
    return MnoSurveyHelper::getLocalEntityByLocalIdentifier($local_id);
  }
  
  public static function createLocalEntity() {
    $obj = (object) array();
    $obj->languages = 'en';
    return $obj;
  }

  public function saveAsLabel() {
    MnoSoaLogger::debug(__FUNCTION__ . " start for Organization");
    return MnoSurveyHelper::saveAsLabel('ORGANIZATIONS', $this->getLocalEntityIdentifier(), null, $this->_local_entity->name, 'OR');
  }
}

?>