<?php

/**
 * Mno Person Class
 */
class MnoSoaPerson extends MnoSoaBasePerson
{
    protected static $_local_entity_name = "PARTICIPANT";
    
    protected function pushName() {
        // DO NOTHING
    }
    
    protected function pullName() {
        $this->_local_entity->lastname = $this->pull_set_or_delete_value($this->_name->familyName);
        $this->_local_entity->firstname = $this->pull_set_or_delete_value($this->_name->givenNames);
    }
    
    protected function pushBirthDate() {
        // DO NOTHING
    }
    
    protected function pullBirthDate() {
        // DO NOTHING
    }
    
    protected function pushGender() {
        // DO NOTHING
    }
    
    protected function pullGender() {
        // DO NOTHING
    }
    
    protected function pushAddresses() {
        // DO NOTHING
    }
    
    protected function pullAddresses() {
	// DO NOTHING
    }
    
    protected function pushEmails() {
        // DO NOTHING
    }
    
    protected function pullEmails() {
        $this->_local_entity->email = $this->pull_set_or_delete_value($this->_email->emailAddress);
    }
    
    
    protected function pushTelephones() {
        // DO NOTHING
    }
    
    protected function pullTelephones() {
        // DO NOTHING
    }
    
    protected function pushWebsites() {
        // DO NOTHING
    }
    
    protected function pullWebsites() {
        // DO NOTHING
    }

    protected function pushNotes() {
        // DO NOTHING
    }

    protected function pullNotes() {
        // DO NOTHING
    }

    protected function pushEntity() {
        // DO NOTHING
    }
    
    protected function pullEntity() {
        // DO NOTHING
    }
    
    // @OVERRIDE
    protected function pushRole() {
        // DO NOTHING
    }
    
    // @OVERRIDE
    protected function pullRole() {
        // DO NOTHING
    }
        
    protected function saveLocalEntity($push_to_maestrano, $status) {
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $this->_local_entity->participant_id = $this->_id;
            $this->insertLocalEntity();
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->updateLocalEntity();
        }
    }
    
    public function getLocalEntityIdentifier() 
    {
        return $this->_local_entity->participant_id;
    }
    
    public function insertLocalEntity()
    {
        // Save the Person as a Label under Labelset 'PERSONS'
        // The Label code is a combination of the person's organization code and a counter
        // (eg: 'O10P4' for Organization n10 and Person n4)
        $orgLabel = Label::model()->findByAttributes(array('mno_uid' => $this->_role->organization->id));
        $pplLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'PERSONS'));
        $count = Label::model()->count('lid = ' . $pplLabelSet->lid);
        $lbl = new Label;
        $lbl->lid = $pplLabelSet->lid;
        $lbl->sortorder = $count;
        $lbl->code = $orgLabel->code . 'P' . $count;
        $lbl->title = $this->_local_entity->firstname . ' ' . $this->_local_entity->lastname;
        $lbl->language = sanitize_languagecodeS($pplLabelSet->languages);
        $lbl->mno_uid = $this->_id;
        $lbl->save();

        // Save in participants table
        $table_prefix = Yii::app()->db->tablePrefix;
        $query = "INSERT INTO ".$table_prefix."participants
                  (participant_id,firstname,lastname,email,language,blacklisted,owner_uid) 
                  VALUES 
                  (:participant_id,:firstname,:lastname,:email,:language,:blacklisted,:owner_uid)";
        $result=Yii::app()->db->createCommand($query)
                    ->bindValue(":participant_id", $this->_local_entity->participant_id)
                    ->bindValue(":firstname", $this->_local_entity->firstname)
                    ->bindValue(":lastname", $this->_local_entity->lastname)
                    ->bindValue(":email", $this->_local_entity->email)
                    ->bindValue(":language", $this->_local_entity->language)
                    ->bindValue(":blacklisted", $this->_local_entity->blacklisted)
                    ->bindValue(":owner_uid", $this->_local_entity->owner_uid)
                    ->query();
        return getLastInsertID('{{participants}}');
    }
    
    public function updateLocalEntity()
    {
        // Save the Person as a Label under its Organization Labelset
        $lset = Label::model()->findByAttributes(array('mno_uid' => $this->_id));
        $lset->title = $this->_local_entity->firstname . ' ' . $this->_local_entity->lastname;
        $lset->save();

        // Save in participants table
        $table_prefix = Yii::app()->db->tablePrefix;
        $query = "UPDATE ".$table_prefix."participants 
                  SET   firstname=:firstname,
                        lastname=:lastname,
                        email=:email,
                        language=:language,
                        blacklisted=:blacklisted,
                        owner_uid=:owner_uid 
                  WHERE participant_id=:participant_id";        
        $result=Yii::app()->db->createCommand($query)
                    ->bindValue(":participant_id", $this->_local_entity->participant_id)
                    ->bindValue(":firstname", $this->_local_entity->firstname)
                    ->bindValue(":lastname", $this->_local_entity->lastname)
                    ->bindValue(":email", $this->_local_entity->email)
                    ->bindValue(":language", $this->_local_entity->language)
                    ->bindValue(":blacklisted", $this->_local_entity->blacklisted)
                    ->bindValue(":owner_uid", $this->_local_entity->owner_uid)
                    ->query();
        return true;
    }

    public function getLocalOrganizationIdentifier()
    {
        return null;
    }
    
    protected function setLocalOrganizationIdentifier($local_org_id)
    {
        // DO NOTHING
    }
    
    public static function getLocalEntityByLocalIdentifier($local_id) {
        $table_prefix = Yii::app()->db->tablePrefix;
        $query = "SELECT participant_id,firstname,lastname,email,language,blacklisted,owner_uid
                    FROM ".$table_prefix."participants
                    WHERE participant_id=:participant_id";        
        $result=Yii::app()->db->createCommand($query)
                    ->bindValue(":participant_id", $local_id)
                    ->queryRow();
        if (count($result) == 0) { return null; }
        return (object) $result;
    }
    
    public static function createLocalEntity() {
        $obj = (object) array();
        $obj->language = 'en';
        $obj->blacklisted = 'N';
        $obj->owner_uid = 1;
        return $obj;
    }

    public static function updateFromSurveyAttributes($data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        // Populate attributes and push to connec!
        $organizationUid = MnoSoaPerson::extractMnoUid($data, 'ORGANIZATIONS');
        $personUid = MnoSoaPerson::extractMnoUid($data, 'PERSONS');

        if(!is_null($organizationUid) && !is_null($personUid)) {
          MnoSoaLogger::debug(__FUNCTION__ . " uodating notes for person uid " . $personUid);
          $mno_person = new MnoSoaPerson();
          $mno_person->_id = $personUid;
          $mno_person->_notes = array();

          foreach ($data as $key=>$value) {
            if(preg_match_all("/(\d+)X(\d+)X(\d+).*/", $key, $matches)) {
              $val = (is_null($value) ? NULL : $value['value']);
              if(is_null($val)) {
                continue;
              }

              $question_id = $matches[3][0];
              MnoSoaLogger::debug(__FUNCTION__ . " finding question " . $question_id);

              $question = Questions::model()->findByAttributes(array('qid' => $question_id));
              $answer = Answers::model()->findByAttributes(array('code' => $val));
              if(!is_null($question)) {
                MnoSoaLogger::debug(__FUNCTION__ . " adding note: " . $question->question . " => " . ($answer ? $answer->answer : $val));
                $mno_person->_notes[$key] = array('description' => $question->question . " => " . ($answer ? $answer->answer : $val));
              }
            }
          }

          $mno_person->send($mno_person->_local_entity);
        }
 
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }

    private static function extractMnoUid($data, $labelSetName) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        foreach ($data as $key=>$value) {
          $val = (is_null($value) ? NULL : $value['value']);
          MnoSoaLogger::debug(__FUNCTION__ . " response: " . $key . " => " . $val);

          // Find answers to survey questions
          if(preg_match_all("/(\d+)X(\d+)X(\d+).*/", $key, $matches)) {
            // Does the answer match a Label Organzation or Person
            $label = Label::model()->findByAttributes(array('code' => $val));
            MnoSoaLogger::debug(__FUNCTION__ . " found matching label " . $label->code);
            if(!is_null($label)) {
              $isOrganizationLset = Labelsets::model()->count('lid = ' . $label->lid . ' AND mno_uid = \'' . $labelSetName . '\'');
              if($isOrganizationLset > 0) {
                return $label->mno_uid;
              }
            }
          }
        }
        MnoSoaLogger::debug(__FUNCTION__ . " end");

        return null;
    }
}

?>