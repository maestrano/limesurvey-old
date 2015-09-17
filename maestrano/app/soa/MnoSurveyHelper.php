<?php

class MnoSurveyHelper {

  // Returns a Label by Entity Connec! ID
  public static function getLocalEntityByLocalIdentifier($mno_uid) {
    MnoSoaLogger::debug(__FUNCTION__ . " find Label by mno_uid $mno_uid");

    $table_prefix = Yii::app()->db->tablePrefix;
    $query = "  SELECT mno_uid, code, title, language, sortorder
                FROM ".$table_prefix."labels
                WHERE mno_uid=:mno_uid";        
    $result = Yii::app()->db->createCommand($query)
      ->bindValue(":mno_uid", $mno_uid)
      ->queryRow();
    
    if (count($result) == 0) {
      MnoSoaLogger::debug(__FUNCTION__ . " no matching Label found");
      return null;
    }

    MnoSoaLogger::debug(__FUNCTION__ . " returning " . json_encode($result));
    return (object) $result;
  }
  
  // Save an ID / Value pair under specified LabelSet name
  public static function saveAsLabel($labelset_uid, $mno_uid, $code=null, $value=null, $prefix='') {
    MnoSoaLogger::debug(__FUNCTION__ . " start");
    // Save the Entity as a Label under specified labelset
    $labelSet = Labelsets::model()->findByAttributes(array('mno_uid' => $labelset_uid));
    if(is_null($labelSet)) {
      MnoSoaLogger::error(__FUNCTION__ . " Labelset with mno_uid $labelset_uid is missing");
      return null;
    }

    // Find or create label
    $lbl = Label::model()->findByAttributes(array('mno_uid' => $mno_uid));
    MnoSoaLogger::error(__FUNCTION__ . " finding existing Label for mno_uid: $mno_uid");
    if(is_null($lbl)) {
      MnoSoaLogger::debug(__FUNCTION__ . " creating new Label");
      $next_index = 1;
      $label_result = Label::model()->findAll(array('condition'=>'lid=:lid', 'order'=>'sortorder DESC', 'params'=>array(':lid'=>$labelSet->lid)));
      if(count($label_result) != 0) {
        $next_index = ((int) $label_result[0]['sortorder']) + 1;
      }

      # Generate Code if not set. Convert index to hexadecimal code
      if($code == null) { $code = $prefix . strtoupper(base_convert((string)($next_index + 360), 10, 36)); }

      $lbl = new Label;
      $lbl->mno_uid = $mno_uid;
      $lbl->lid = $labelSet->lid;
      $lbl->sortorder = $next_index;
      $lbl->code = $code;
      $lbl->language = 'en';
    }
    $lbl->title = $value;
    $lbl->save();

    // Add the new answer to all matching questions
    MnoSoaLogger::debug(__FUNCTION__ . " saving new possible answer");
    $questions = Questions::model()->findAllByAttributes(array('title' => $labelset_uid));
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
      $answer->answer = $value;
      $answer->save();
    }

    MnoSoaLogger::debug(__FUNCTION__ . " end");

    return $lbl;
  }
}

?>