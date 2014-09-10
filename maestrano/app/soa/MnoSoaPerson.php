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
        // Save the Person as a Label under its Organization Labelset
        $orgLset = Labelsets::model()->findByAttributes(array('mno_uid' => $this->_role->organization->id));
        $count = Label::model()->count('lid = ' . $orgLset->lid);
        $lbl = new Label;
        $lbl->lid = $orgLset->lid;
        $lbl->sortorder = $count;
        $lbl->code = 'L0' . $count;
        $lbl->title = $this->_local_entity->firstname . ' ' . $this->_local_entity->lastname;
        $lbl->language = sanitize_languagecodeS($orgLset->languages);
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
        $lset->label_name = $this->_local_entity->firstname . ' ' . $this->_local_entity->lastname;
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
    
    public static function getLocalEntityByLocalIdentifier($local_id)
    {
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
    
    public static function createLocalEntity()
    {
        $obj = (object) array();
        $obj->language = 'en';
        $obj->blacklisted = 'N';
        $obj->owner_uid = 1;
        return $obj;
    }
    
    public function getLocalOrganizationIdentifier()
    {
        return null;
    }
    
    protected function setLocalOrganizationIdentifier($local_org_id)
    {
        // DO NOTHING
    }
}

?>