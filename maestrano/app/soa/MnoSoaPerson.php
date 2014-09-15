<?php

/**
 * Mno Person Class
 */
class MnoSoaPerson extends MnoSoaBasePerson
{
    protected static $_local_entity_name = "PARTICIPANT";

    protected function pushId() {
        $this->_id = $this->_local_entity->id;
    }

    protected function pushName() {
        $this->_name->familyName = $this->_local_entity->lastname;
        $this->_name->givenNames = $this->_local_entity->firstname;
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
        $this->_notes = $this->_local_entity->notes;
    }

    protected function pullNotes() {
        // DO NOTHING
    }

    protected function pushTasks() {
        $this->_tasks = $this->_local_entity->tasks;
    }

    protected function pullTasks() {
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
        $this->_role->organization->id = $this->_local_entity->organization;
    }
    
    // @OVERRIDE
    protected function pullRole() {
        if(isset($this->_role) && isset($this->_role->organization)){
          $this->_local_entity->organization = $this->_role->organization->id;
        }
    }
        
    protected function saveLocalEntity($push_to_maestrano, $status) {
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $this->_local_entity->participant_id = $this->_id;
            $this->_local_entity->mno_uid = $this->_id;
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
        $this->saveAsLabel();
        return $this->saveAsParticipant();
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

    public function saveAsParticipant()
    {
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

    public function saveAsLabel() {
        // Save the Person as a Label under Labelset 'PERSONS'
        // The Label code is a combination of the person's organization code and a counter
        // (eg: 'O10P4' for Organization n10 and Person n4)
        if(is_null($this->_role) || is_null($this->_role->organization) || is_null($this->_role->organization->id)) {
          return null;
        }

        $orgLabel = Label::model()->findByAttributes(array('mno_uid' => $this->_role->organization->id));
        $pplLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'PERSONS'));
        $count = Label::model()->count('lid = ' . $pplLabelSet->lid);
        $lbl = new Label;
        $lbl->lid = $pplLabelSet->lid;
        $lbl->sortorder = $count;
        $lbl->code = $orgLabel->code . 'P' . $count;
        $lbl->title = $this->_local_entity->firstname . ' ' . $this->_local_entity->lastname;
        $lbl->language = 'en';
        $lbl->mno_uid = $this->_local_entity->mno_uid;
        $lbl->save();

        return $lbl;
    }
}

?>