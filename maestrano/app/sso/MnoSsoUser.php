<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    $user = User::model()->find('uid=:uid', array(':uid'=>$this->local_id));
    
    if ($user) {
      $identity = new UserIdentity($this->uid, '');
      $identity->authenticate('',true);
      Yii::app()->user->login($identity);
      
      Yii::app()->session['loginID'] = $user->uid;
      Yii::app()->session['user'] = $user->users_name;
      Yii::app()->session['full_name'] = $user->full_name;
      Yii::app()->session['htmleditormode'] = $user->htmleditormode;
      Yii::app()->session['templateeditormode'] = $user->templateeditormode;
      Yii::app()->session['questionselectormode'] = $user->questionselectormode;
      Yii::app()->session['dateformat'] = $user->dateformat;
      Yii::app()->session['session_hash'] = hash('sha256',getGlobalSetting('SessionName').$user->users_name.$user->uid);
      
      // Set rights
      Yii::app()->session['USER_RIGHT_SUPERADMIN'] = $user->superadmin;
      Yii::app()->session['USER_RIGHT_CREATE_SURVEY']     = ($user->create_survey || $user->superadmin);
      Yii::app()->session['USER_RIGHT_PARTICIPANT_PANEL'] = ($user->participant_panel || $user->superadmin);
      Yii::app()->session['USER_RIGHT_CONFIGURATOR']      = ($user->configurator || $user->superadmin);
      Yii::app()->session['USER_RIGHT_CREATE_USER']       = ($user->create_user || $user->superadmin);
      Yii::app()->session['USER_RIGHT_DELETE_USER']       = ($user->delete_user || $user->superadmin);
      Yii::app()->session['USER_RIGHT_MANAGE_TEMPLATE']   = ($user->manage_template || $user->superadmin);
      Yii::app()->session['USER_RIGHT_MANAGE_LABEL']      = ($user->manage_label || $user->superadmin);
      Yii::app()->session['USER_RIGHT_INITIALSUPERADMIN'] = $user->superadmin;
      
      
      return true;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      
      // Build user and save it
      $user = $this->buildLocalUser();
      $user->save();

      // Build a label using this user
      $this->saveAsLabel();
      
      $lid = $user->uid;
    }
    
    return $lid;
  }
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function buildLocalUser()
  {
    $is_admin = $this->getRoleIdToAssign();
    
    $user = new User;
    $user->users_name = $this->uid;
    $user->full_name = "$this->name $this->surname";
    $user->email = $this->email;
    $user->lang = 'auto';
    $user->password = $this->generatePassword();
    $user->create_survey = $is_admin;
    $user->create_user = $is_admin;
    $user->participant_panel = $is_admin;
    $user->delete_user = $is_admin;
    $user->superadmin = $is_admin;
    $user->configurator = $is_admin;
    $user->manage_template = $is_admin;
    $user->manage_label = $is_admin;
    
    return $user;
  }

  protected function saveAsLabel() {
    // Save the User as a Label under Labelset 'USERS'
    $usersLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'USERS'));
    $count = Label::model()->count('lid = ' . $usersLabelSet->lid);
    $lbl = new Label;
    $lbl->lid = $usersLabelSet->lid;
    $lbl->sortorder = $count;
    $lbl->code = 'U' . $count;
    $lbl->title = "$this->name $this->surname";
    $lbl->language = 'en';
    $lbl->mno_uid = $this->uid;
    $lbl->save();

    return $lbl;
  }
  
  /**
   * Return 1 if admin 0 otherwise
   *
   * @return 1 or 0 (integer boolean flag )
   */
  public function getRoleIdToAssign() {
    $role_id = 0; // User
    
    if ($this->app_owner) {
      $role_id = 1; // Admin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $role_id = 1;
        } else {
          $role_id = 0;
        }
      }
    }
    
    return $role_id;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $user = User::model()->find('mno_uid=:mno_uid', array(':mno_uid'=>$this->uid));
    
    if ($user) {
      return $user->uid;
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    $user = User::model()->find('email=:email', array(':email'=>$this->email));
    
    if ($user) {
      return $user->uid;
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       $user = User::model()->find('uid=:uid', array(':uid'=>$this->local_id));
       $user->users_name = $this->uid;
       $user->full_name = "$this->name $this->surname";
       $user->email = $this->email;
       return $user->save();
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      $user = User::model()->find('uid=:uid', array(':uid'=>$this->local_id));
      $user->mno_uid = $this->uid;
      return $user->save();
    }
    
    return false;
  }
}