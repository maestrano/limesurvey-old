<?php

/**
 * Mno Person Interface
 */
class MnoSoaBasePerson extends MnoSoaBaseEntity
{
    protected static $_mno_entity_name = "PERSONS";
    protected $_create_rest_entity_name = "persons";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "persons";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "persons";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "persons";
    protected $_delete_http_operation = "DELETE";    
    
    protected $_id;
    protected $_name;
    protected $_birth_date;
    protected $_gender;
    protected $_address;
    protected $_email;
    protected $_telephone;
    protected $_website;
    protected $_entity;
    protected $_role;  

    /**************************************************************************
     *                    ABSTRACT DATA MAPPING METHODS                       *
     **************************************************************************/
       
    protected function pushName() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullName() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushBirthDate() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullBirthDate() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushGender() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullGender() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushAddresses() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullAddresses() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushEmails() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullEmails() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushTelephones() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullTelephones() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushWebsites() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullWebsites() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushEntity() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullEntity() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    /**************************************************************************
     *                       ABSTRACT GET/SET METHODS                         *
     **************************************************************************/
    
    public function getLocalEntityIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public static function getLocalEntityByLocalIdentifier($local_id) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public static function createLocalEntity() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public function getLocalOrganizationIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function setLocalOrganizationIdentifier($local_org_id) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    /**************************************************************************
     *                       COMMON INHERITED METHODS                         *
     **************************************************************************/
    
    protected function getMnoOrganizationByMap($local_org_id) {
        $organization_class = static::getRelatedOrganizationClass();
        return MnoSoaDB::getMnoIdByLocalId($local_org_id, $organization_class::getLocalEntityName(), $organization_class::getMnoEntityName());
    }
    
    public static function getRelatedOrganizationClass() {
        return static::$_related_organization_class;
    }
    
    protected function pushRole() {    
        $local_org_id = $this->getLocalOrganizationIdentifier();
        $org_class_name = static::getRelatedOrganizationClass();
        
        if (empty($local_org_id)) {
            $this->_role = null;
            return;
        }
        
        $mno_org_id = MnoSoaDB::getMnoIdByLocalId($local_org_id, $org_class_name::getLocalEntityName(), $org_class_name::getMnoEntityName());

        if ($this->isValidIdentifier($mno_org_id)) {    
            MnoSoaLogger::debug("is valid identifier");
            $this->_role->organization->id = $mno_org_id->_id;
        } else if ($this->isDeletedIdentifier($mno_org_id)) {
            MnoSoaLogger::debug(__FUNCTION__ . " deleted identifier");
            // do not update
            return;
        } else {
            MnoSoaLogger::debug("before contacts find by id=" . json_encode($local_org_id));
            $org_contact = $org_class_name::getLocalEntityByLocalIdentifier($local_org_id);
            MnoSoaLogger::debug("after contacts find by id=" . json_encode($local_org_id));

            $organization = new $org_class_name;		
            $status = $organization->send($org_contact);
            MnoSoaLogger::debug("after mno soa organization send");

            if ($status) {
                $mno_org_id = MnoSoaDB::getMnoIdByLocalId($local_org_id, $org_class_name::getLocalEntityName(), $org_class_name::getMnoEntityName());

                if ($this->isValidIdentifier($mno_org_id)) {
                    $this->_role->organization->id = $mno_org_id->_id;
                }
            }
        }
    }
    
    protected function pullRole() {
        $mno_org_id = $this->_role->organization->id;
        
        if (empty($mno_org_id)) {
            return;
        }
        
        $org_class_name = static::getRelatedOrganizationClass();
        if (empty($org_class_name)) {
            return;
        }
        $local_org_id = MnoSoaDB::getLocalIdByMnoId($mno_org_id, $org_class_name::getLocalEntityName(), $org_class_name::getMnoEntityName());
        
        if ($this->isValidIdentifier($local_org_id)) {
            $this->setLocalOrganizationIdentifier($local_org_id);
            MnoSoaLogger::debug(__FUNCTION__ . " $local_org_id = " . json_encode($local_org_id));
        } else if ($this->isDeletedIdentifier($local_org_id)) {
            // do not update
            return;
        } else {
            $notification->entity = $org_class_name::getMnoEntityName();
            $notification->id = $this->_role->organization->id;
            
            $organization = new $org_class_name();		
            $status = $organization->receiveNotification($notification);
            
            if ($status) {
                $this->setLocalOrganizationIdentifier($organization->getLocalEntityIdentifier());
            }
        }
    }
    
    /**
    * Build a Maestrano organization message
    * 
    * @return Organization the organization json object
    */
    protected function build() {
        MnoSoaLogger::debug(__FUNCTION__ . " start build function");
        $this->pushId();
        MnoSoaLogger::debug(__FUNCTION__ . " after Id");
        $this->pushName();
        MnoSoaLogger::debug(__FUNCTION__ . " after Name");
        $this->pushBirthDate();
        MnoSoaLogger::debug(__FUNCTION__ . " after Birth Date");
        $this->pushGender();
        MnoSoaLogger::debug(__FUNCTION__ . " after Annual Revenue");
        $this->pushAddresses();
        MnoSoaLogger::debug(__FUNCTION__ . " after Addresses");
        $this->pushEmails();
        MnoSoaLogger::debug(__FUNCTION__ . " after Emails");
        $this->pushTelephones();
        MnoSoaLogger::debug(__FUNCTION__ . " after Telephones");
        $this->pushWebsites();
        MnoSoaLogger::debug(__FUNCTION__ . " after Websites");
        $this->pushEntity();
        MnoSoaLogger::debug(__FUNCTION__ . " after Entity");
        $this->pushRole();
        MnoSoaLogger::debug(__FUNCTION__ . " after Role");
        
        if ($this->_name != null) { $msg['person']->name = $this->_name; }
        if ($this->_birth_date != null) { $msg['person']->birthDate = $this->_birth_date; }
        if ($this->_gender != null) { $msg['person']->gender = $this->_gender; }
        if ($this->_address != null) { $msg['person']->contacts->address = $this->_address; }
        if ($this->_email != null) { $msg['person']->contacts->email = $this->_email; }
        if ($this->_telephone != null) { $msg['person']->contacts->telephone = $this->_telephone; }
        if ($this->_website != null) { $msg['person']->contacts->website = $this->_website; }
        if ($this->_entity != null) { $msg['person']->entity = $this->_entity; }
        if ($this->_role != null) { $msg['person']->role = $this->_role; }
	
        MnoSoaLogger::debug(__FUNCTION__ . " after creating message array");
        
        return $msg['person'];
    }
    
    protected function persist($mno_entity) {
        MnoSoaLogger::debug(__CLASS__ . " " . __FUNCTION__ . " mno_entity = " . json_encode($mno_entity));
        
        if (!empty($mno_entity->person)) {
            $mno_entity = $mno_entity->person;
        }
        
        if (empty($mno_entity->id)) {
            return false;
        }
        
        $this->_id = $mno_entity->id;
        $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);
        $this->set_if_array_key_has_value($this->_birth_date, 'birthDate', $mno_entity);
        $this->set_if_array_key_has_value($this->_gender, 'gender', $mno_entity);

        if (!empty($mno_entity->contacts)) {
            $this->set_if_array_key_has_value($this->_address, 'address', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_email, 'email', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_telephone, 'telephone', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_website, 'website', $mno_entity->contacts);
        }

        $this->set_if_array_key_has_value($this->_entity, 'entity', $mno_entity);
        $this->set_if_array_key_has_value($this->_role, 'role', $mno_entity);

        MnoSoaLogger::debug(__FUNCTION__ . " persist person id = " . $this->_id);

        $status = $this->pullId();
        $is_new_id = $status == constant('MnoSoaBaseEntity::STATUS_NEW_ID');
        $is_existing_id = $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');

        if (!$is_new_id && !$is_existing_id) {
            return true;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " is_new_id = " . $is_new_id);
        MnoSoaLogger::debug(__FUNCTION__ . " is_existing_id = " . $is_existing_id);

        MnoSoaLogger::debug(__FUNCTION__ . " start pull functions");
        $this->pullName();
        MnoSoaLogger::debug(__FUNCTION__ . " after name");
        $this->pullBirthDate();
        MnoSoaLogger::debug(__FUNCTION__ . " after birth date");
        $this->pullGender();
        MnoSoaLogger::debug(__FUNCTION__ . " after gender");
        $this->pullAddresses();
        MnoSoaLogger::debug(__FUNCTION__ . " after addresses");
        $this->pullEmails();
        MnoSoaLogger::debug(__FUNCTION__ . " after emails");
        $this->pullTelephones();
        MnoSoaLogger::debug(__FUNCTION__ . " after telephones");
        $this->pullWebsites();
        MnoSoaLogger::debug(__FUNCTION__ . " after websites");
        $this->pullEntity();
        MnoSoaLogger::debug(__FUNCTION__ . " after entity");
        $this->pullRole();
        MnoSoaLogger::debug(__FUNCTION__ . " after role");

        $this->saveLocalEntity(false, $status);

        $local_entity_id = $this->getLocalEntityIdentifier();
        $mno_entity_id = $this->_id;

        if ($is_new_id && !empty($local_entity_id) && !empty($mno_entity_id)) {
            MnoSoaDB::addIdMapEntry($local_entity_id, static::getLocalEntityName(), $mno_entity_id, static::getMnoEntityName());
        }
        MnoSoaLogger::debug(__FUNCTION__ . " end persist");
        
        return true;
    }
}

?>