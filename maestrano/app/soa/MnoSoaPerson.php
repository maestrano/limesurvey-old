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
        insertLocalEntityAsLabel();

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

    public function insertLocalEntityAsLabel()
    {
        $this->saveAsLabel();
        return getLastInsertID($lbl->tableName());
    }

    public static function updateFromSurveyAttributes($data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        // Populate attributes and push to connec!
        $organizationUid = MnoSoaPerson::findOrCreateOrganization($data);
        if(is_null($organizationUid)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Organization not created");
          return null;
        }

        $personUid = MnoSoaPerson::findOrCreatePerson($data, $organizationUid);
        if(is_null($personUid)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Person not created");
          return null;
        }

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

          $mno_person->send($mno_person->_local_entity);
        }
 
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }

    private static function findOrCreateOrganization($data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        // Find if ORGANIZATION field is specified
        $orgQuestion = Questions::model()->findByAttributes(array('title' => 'ORGANIZATION'));
        if(is_null($orgQuestion)) {
          return null;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " organization question id: " . $orgQuestion->qid);
        // Find selected Organization or create one
        $selectedOrganization = MnoSoaPerson::getResponse($data, $orgQuestion->qid);
        if(is_null($selectedOrganization) || $selectedOrganization == '') {
          return null;
        }

        // If the selected Organization matches a Label, it already exists
        $label = Label::model()->findByAttributes(array('code' => $selectedOrganization));
        if(isset($label)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Organization already exists");
          return $label->mno_uid;
        }
        else {
          $mno_organization = new MnoSoaOrganization();
          $mno_organization->setName($selectedOrganization);
          $mno_uid = $mno_organization->send($mno_organization->_local_entity);
          $mno_organization->_local_entity->mno_uid = $mno_uid;
          $newlabel = $mno_organization->saveAsLabel();

          // Save Organization as new possible Answer
          $answer = new Answers();
          $answer->sortorder = $newlabel->sortorder;
          $answer->code = $newlabel->code;
          $answer->answer = $newlabel->title;
          $answer->assessment_value = $newlabel->assessment_value;
          $answer->language = 'en';
          $answer->save();

          MnoSoaLogger::debug(__FUNCTION__ . " end - Created new Organization");
          return $mno_uid;
        }
    }

    private static function findOrCreatePerson($data, $organizationUid) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        // Find if ORGANIZATION field is specified
        $personQuestion = Questions::model()->findByAttributes(array('title' => 'PERSON'));
        if(is_null($personQuestion)) {
          return null;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " person question id: " . $personQuestion->qid);
        // Find selected Organization or create one
        $selectedPerson = MnoSoaPerson::getResponse($data, $personQuestion->qid);
        if(is_null($selectedPerson) || $selectedPerson == '') {
          return null;
        }

        // If the selected person matches a Label, it already exists
        $label = Label::model()->findByAttributes(array('code' => $selectedPerson));
        if(isset($label)) {
          return $label->mno_uid;
        }
        else {
          $mno_person = new MnoSoaPerson();
          $mno_person->_role->organization->id = $organizationUid;
          $mno_person->_name->familyName = $selectedPerson;
          $mno_person->_name->givenNames = $selectedPerson;
          $mno_uid = $mno_person->send($mno_person->_local_entity);
          $mno_person->_local_entity->mno_uid = $mno_uid;
          $mno_person->_local_entity->firstname = $selectedPerson;
          $newlabel = $mno_person->saveAsLabel();

          // Save Person as new possible Answer
          $answer = new Answers();
          $answer->sortorder = $newlabel->sortorder;
          $answer->code = $newlabel->code;
          $answer->answer = $newlabel->title;
          $answer->assessment_value = $newlabel->assessment_value;
          $answer->language = 'en';
          $answer->save();

          return $mno_uid;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " end");

        return null;
    }

    private static function getResponse($data, $questionId) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        // Return the selected response or alternatively try to find a 'comment' response
        $response = null;
        foreach ($data as $key=>$value) {
          MnoSoaLogger::debug(__FUNCTION__ . " key " . $key);
          if(preg_match_all("/^(\d+)X(\d+)X(\d+)$/", $key, $matches)) {
            MnoSoaLogger::debug(__FUNCTION__ . " comp " . $matches[3][0] . " and " . $questionId);
            if($matches[3][0] == strval($questionId)) {
              $response = $value['value'];
              MnoSoaLogger::debug(__FUNCTION__ . " found response: " . $response);
            }
          }

          if(preg_match_all("/^(\d+)X(\d+)X(\d+)comment$/", $key, $matches)) {
            if($matches[3][0] == strval($questionId)) {
              if(is_null($response) || $response == '') {
                $response = $value['value'];
                MnoSoaLogger::debug(__FUNCTION__ . " found response comment: " . $response);
              }
            }
          }
        }

        MnoSoaLogger::debug(__FUNCTION__ . " end returning " . $response);

        return $response;
    }

    public function saveAsLabel() {
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

        return $lbl;
    }
}

?>