<?php

/**
 * Mno Organization Class
 */
class MnoSoaOrganization extends MnoSoaBaseOrganization
{
    protected static $_local_entity_name = "ORGANIZATION";
    
    protected function pushName() {
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
        // Save the Organization both as a Labelset with a list of People underneath
        // and as a Label under the 'Organizations' Labelset
        $lset = new Labelsets;
        $lset->label_name = $this->_local_entity->name;
        $lset->languages = sanitize_languagecodeS($this->_local_entity->languages);
        $lset->mno_uid = $this->_local_entity->mno_uid;
        $lset->save();

        $orgLset = Labelsets::model()->findByAttributes(array('mno_uid' => 0));
        $count = Label::model()->count('lid = ' . $orgLset->lid);
        $lbl = new Label;
        $lbl->lid = $orgLset->lid;
        $lbl->sortorder = $count;
        $lbl->code = 'L0' . $count;
        $lbl->title = $this->_local_entity->name;
        $lbl->language = sanitize_languagecodeS($this->_local_entity->languages);
        $lbl->mno_uid = $this->_local_entity->mno_uid;
        $lbl->save();

        return getLastInsertID($lset->tableName());
    }
    
    public function updateLocalEntity()
    {
        $lset = Labelsets::model()->findByAttributes(array('mno_uid' => $this->_id));
        $lset->label_name = $this->_local_entity->name;
        $lset->save();

        $lset = Label::model()->findByAttributes(array('mno_uid' => $this->_id));
        $lset->label_name = $this->_local_entity->name;
        $lset->save();

        return true;
    }
    
    public static function getLocalEntityByLocalIdentifier($local_id)
    {
        $table_prefix = Yii::app()->db->tablePrefix;
        $query = "SELECT mno_uid,label_name,languages
                    FROM ".$table_prefix."labelsets
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
}

?>