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
    if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
      $this->_local_entity->mno_uid = $this->_id;
      $this->insertLocalEntity();
    } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
      $this->updateLocalEntity();
    }
  }
  
  public function getLocalEntityIdentifier() {
    return $this->_local_entity->mno_uid;
  }
  
  public function insertLocalEntity()
  {
    $label = $this->saveAsLabel();
    return getLastInsertID($label->tableName());
  }
  
  public function updateLocalEntity()
  {
    $label = Label::model()->findByAttributes(array('mno_uid' => $this->_id));
    $label->label_name = $this->_local_entity->name;
    $label->save();

    return true;
  }
  
  public static function getLocalEntityByLocalIdentifier($local_id)
  {
    $table_prefix = Yii::app()->db->tablePrefix;
    $query = "  SELECT mno_uid, code, title, language, sortorder
    FROM ".$table_prefix."labels
    WHERE mno_uid=:mno_uid";        
    $result=Yii::app()->db->createCommand($query)
    ->bindValue(":mno_uid", $local_id)
    ->queryRow();
    if (count($result) == 0) { return null; }
    return (object) $result;
  }
  
  public static function createLocalEntity()
  {
    $obj = (object) array();
    $obj->languages = 'en';
    return $obj;
  }

  public function saveAsLabel() {
        // Save the Organziation as a Label under Labelset 'ORGANIZATIONS'
    $orgLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'ORGANIZATIONS'));
    $count = Label::model()->count('lid = ' . $orgLabelSet->lid);
    $lbl = new Label;
    $lbl->lid = $orgLabelSet->lid;
    $lbl->sortorder = $count;
    $lbl->code = 'O' . $count;
    $lbl->title = $this->_local_entity->name;
    $lbl->language = 'en';
    $lbl->mno_uid = $this->_local_entity->mno_uid;
    $lbl->save();

    MnoSoaLogger::debug(__FUNCTION__ . " saving organization as a new possible answer");
    $questions = Questions::model()->findAllByAttributes(array('title' => 'ORGANIZATION'));
    foreach ($questions as $question) {
      $qid = $question->attributes['qid'];
      $answer = new Answers();
      $answer->qid = $qid;
      $answer->sortorder = $lbl->sortorder;
      $answer->code = $lbl->code;
      $answer->answer = $lbl->title;
      $answer->assessment_value = $lbl->assessment_value;
      $answer->language = 'en';
      $answer->save();
    }

    return $lbl;
  }
}

?>