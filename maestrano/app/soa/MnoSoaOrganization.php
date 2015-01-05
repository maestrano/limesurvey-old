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
    MnoSoaLogger::debug(__FUNCTION__ . " find Organization Label by mno_uid $local_id");

    $table_prefix = Yii::app()->db->tablePrefix;
    $query = "  SELECT mno_uid, code, title, language, sortorder
                FROM ".$table_prefix."labels
                WHERE mno_uid=:mno_uid";        
    $result = Yii::app()->db->createCommand($query)
      ->bindValue(":mno_uid", $local_id)
      ->queryRow();
    
    if (count($result) == 0) {
      MnoSoaLogger::debug(__FUNCTION__ . " no matching Label found");
      return null;
    }

    MnoSoaLogger::debug(__FUNCTION__ . " returning " . json_encode($result));
    return (object) $result;
  }
  
  public static function createLocalEntity()
  {
    $obj = (object) array();
    $obj->languages = 'en';
    return $obj;
  }

  public function saveAsLabel() {
    MnoSoaLogger::debug(__FUNCTION__ . " start");
    // Save the Organziation as a Label under Labelset 'ORGANIZATIONS'
    $orgLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'ORGANIZATIONS'));
    if(is_null($orgLabelSet)) {
      MnoSoaLogger::error(__FUNCTION__ . " Labelset with mno_uid 'ORGANIZATIONS' is missing");
      return null;
    }

    // Find or create label
    $local_id = $this->getLocalEntityIdentifier();
    $lbl = Label::model()->findByAttributes(array('mno_uid' => $local_id));
    MnoSoaLogger::error(__FUNCTION__ . " finding existing Label for mno_uid: $local_id");
    if(is_null($lbl)) {
      MnoSoaLogger::debug(__FUNCTION__ . " creating new Label");
      $next_index = 1;
      $label_result = Label::model()->findAll(array('condition'=>'lid=:lid', 'order'=>'sortorder DESC', 'params'=>array(':lid'=>$orgLabelSet->lid)));
      if(count($label_result) != 0) {
        $next_index = ((int) $label_result[0]['sortorder']) + 1;
      }
      $lbl = new Label;
      $lbl->mno_uid = $local_id;
      $lbl->lid = $orgLabelSet->lid;
      $lbl->sortorder = $next_index;
      # Convert index to hexadecimal code used to map people to this organization in Labels
      $lbl->code = strtoupper(base_convert((string)($next_index + 360), 10, 36));
      $lbl->language = 'en';
    }
    $lbl->title = $this->_local_entity->name;
    $lbl->save();

    // Add the Organization as a new answer to all 'Organization' questions
    MnoSoaLogger::debug(__FUNCTION__ . " saving organization as a new possible answer");
    $questions = Questions::model()->findAllByAttributes(array('title' => 'ORGANIZATION'));
    foreach ($questions as $question) {
      $qid = $question->attributes['qid'];

      // Find or create answer
      $answer = Answers::model()->findByAttributes(array('qid' => $qid, 'code' => $lbl->code));
      if(is_null($answer)) {
        $answer = new Answers();
        $answer->qid = $qid;
        $answer->sortorder = $lbl->sortorder;
        $answer->code = $lbl->code;
        $answer->assessment_value = $lbl->assessment_value;
        $answer->language = 'en';
      }
      $answer->answer = $lbl->title;
      $answer->save();
    }

    MnoSoaLogger::debug(__FUNCTION__ . " end");

    return $lbl;
  }
}

?>