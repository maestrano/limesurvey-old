<?php

class Facci extends Survey_Common_Action {

  public function create() {
    $clang = Yii::app()->lang;
    $aData = array();
    if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1) {
      // Fetch the organizations
      $orgLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'ORGANIZATIONS'));
      $organizations = Label::model()->findAllByAttributes(array('lid' => $orgLabelSet->lid));
      $aData['organizations'] = $organizations;

      // Fetch the persons
      $personLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'PERSONS'));
      $persons = Label::model()->findAllByAttributes(array('lid' => $personLabelSet->lid));
      $aData['persons'] = $persons;

      // Fetch the users
      $userLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'USERS'));
      $users = Label::model()->findAllByAttributes(array('lid' => $userLabelSet->lid));
      $aData['users'] = $users;

      $aViewUrls = 'new_meeting_summary';
    }

    $this->_renderWrappedTemplate('facci', $aViewUrls, $aData);
  }

  public function save() {
    MnoSoaLogger::debug(__FUNCTION__ . " start");

    $clang = Yii::app()->lang;
    $action = (isset($_POST['action'])) ? $_POST['action'] : '';
    
    $aData = array();
    if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1) {
      $meeting_date = $_POST['meeting_date'];
      $customer_type = $_POST['customer_type'];
      $organziation = $_POST['organziation'];
      $new_organziation = $_POST['new_organziation'];
      $person = $_POST['person'];
      $new_person_title = $_POST['new_person_title'];
      $new_person_first_name = $_POST['new_person_first_name'];
      $new_person_last_name = $_POST['new_person_last_name'];
      $topic1 = $_POST['topic1'];
      $topic2 = $_POST['topic2'];
      $topic3 = $_POST['topic3'];
      $actions = $_POST['actions'];
      $action_descriptions = $_POST['action_descriptions'];
      $action_assignees = $_POST['action_assignees'];
      $action_due_dates = $_POST['action_due_dates'];
      $action_others = $_POST['action_others'];

      // Extract Person and Organization
      if (is_null($organziation) || $organziation == '') {
        $organziation = MnoSurveyProcessor::findOrCreateOrganization($new_organziation);
      }

      if (is_null($person) || $person == '') {
        $person = MnoSurveyProcessor::findOrCreatePerson("$new_person_first_name $new_person_last_name", $organziation);
      }

      // Build Person object to send to connec!
      $mno_person = new MnoSoaPerson();
      $local_entity = (object) array();
      $local_entity->id = $person;
      $local_entity->tasks = array();

      // TODO: Post activities to connec!
      foreach ($actions as $index => $action_name) {
        $action_other = $action_others[$index];
        $action_description = $action_descriptions[$index];
        $action_assignee = $action_assignees[$index];
        $action_due_date = $action_due_dates[$index];

        $task_id = uniqid();
        $start_date = time();
        $due_date = strtotime($action_due_date);
        $assigned_to = array($action_assignee => "ACTIVE");

        MnoSoaLogger::debug(__FUNCTION__ . " creating task: action_name=$action_name, task_id=$task_id, start_date=$start_date, due_date=$due_date, assigned_to=$assigned_to");
        $task = array(
          "id" => $task_id,
          "name" => (is_null($action_name) || $action_name == '') ? $action_other : $action_name,
          "description" => $action_description,
          "status" => "Not Started",
          "startDate" => $start_date,
          "dueDate" => $due_date,
          "assignedTo" => $assigned_to
        );
        $local_entity->tasks[$task_id] = $task;
      }

      $mno_person->send($local_entity);

      $aViewUrls = 'new_meeting_summary';
    }

    MnoSoaLogger::debug(__FUNCTION__ . " end");

    $this->_renderWrappedTemplate('facci', $aViewUrls, $aData);
  }
}
