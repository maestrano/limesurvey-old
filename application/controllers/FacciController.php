<?php

/**
 * Maestrano Facci Customisation
**/
class FacciController extends CController {

  public function actionCreate() {
    $token = $_GET['token'];
    if($token != '076823aa27bc') {
      $this->redirect($this->createUrl("/"));
    } else {
      $aData = array();
      
      // Fetch the organizations
      $orgLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'ORGANIZATIONS'));
      $organizations = Label::model()->findAll(array('order' => 'title asc', 'condition' => "lid=$orgLabelSet->lid"));
      $aData['organizations'] = $organizations;

      // Fetch the persons
      $personLabelSet = Labelsets::model()->findByAttributes(array('mno_uid' => 'PERSONS'));
      $persons = Label::model()->findAll(array('order' => 'title asc', 'condition' => "lid=$personLabelSet->lid"));
      $aData['persons'] = $persons;

      // Fetch the users
      $users = User::model()->findAllBySql("select * from {{users}} where mno_uid is not null");
      $aData['users'] = $users;

      // Prepare flashmessage
      if(!empty(Yii::app()->session['flashmessage']) && Yii::app()->session['flashmessage'] != '') {
        $aData['flashmessage'] = Yii::app()->session['flashmessage'];
        unset(Yii::app()->session['flashmessage']);
      }

      $this->render('/facci/new_meeting_summary',$aData);
    }
  }

  public function actionSave() {
    MnoSoaLogger::debug(__FUNCTION__ . " start");

    $meeting_date = $_POST['meeting_date'];
    $customer_type = $_POST['customer_type'];
    $organization_mno_uid = $_POST['organization'];
    $new_organization = $_POST['new_organization'];
    $person_mno_uid = $_POST['person'];
    $new_person_title = $_POST['new_person_title'];
    $new_person_first_name = $_POST['new_person_first_name'];
    $new_person_last_name = $_POST['new_person_last_name'];
    $description = $_POST['description'];
    $actions = $_POST['actions'];
    $action_descriptions = $_POST['action_descriptions'];
    $action_assignees = $_POST['action_assignees'];
    $action_due_dates = $_POST['action_due_dates'];
    $action_others = $_POST['action_others'];

    // Extract Person and Organization
    if(is_null($organization_mno_uid) || $organization_mno_uid == '') {
      $organization_label = MnoSurveyProcessor::findOrCreateOrganization($new_organization);
    } else {
      $organization_label = MnoSoaOrganization::getLocalEntityByLocalIdentifier($organization_mno_uid);
    }

    if(is_null($person_mno_uid) || $person_mno_uid == '') {
      $person_label = MnoSurveyProcessor::findOrCreatePerson("$new_person_first_name $new_person_last_name", $organization_label);
    } else {
      $person_label = MnoSoaPerson::getLocalEntityByLocalIdentifier($person_mno_uid);
    }

    if(!is_null($organization_label) && !is_null($person_label)) {
      // Build Person object to be sent
      $mno_person = new MnoSoaPerson();
      $local_entity = (object) array();
      $local_entity->id = $person_label->participant_id;
      $local_entity->notes = array();
      $local_entity->tasks = array();

      // Generate meeting summary and save it as a person note
      $meeting_summary = "Meeting date: $meeting_date\n";
      $meeting_summary .= "Company: $organization_label->title\n";
      $meeting_summary .= "Person: $person_label->firstname $person_label->lastname\n";
      $meeting_summary .= "Type: $customer_type\n";
      
      for ($i = 1; $i <= 10; $i++) {
        $topic = $_POST["topic$i"];
        if(!is_null($topic) && $topic!='') {
          $meeting_summary .= "Topic $i: $topic\n";
        }
      }

      $meeting_summary .= "Description: $description\n";

      $note_id = uniqid();
      $local_entity->notes[$note_id] = array('tag' => 'Meeting Summary', 'description' => $meeting_summary);

      // Map activities to Person
      foreach ($actions as $index => $action_name) {
        if(isset($action_name) && $action_name != '') {
          $action_other = $action_others[$index];
          $action_description = $action_descriptions[$index];
          $action_assignee = $action_assignees[$index];
          $action_due_date = $action_due_dates[$index];
          $action_name_combined = ((is_null($action_name) || $action_name == '') ? $action_other : $action_name) . " - " . $action_description;

          $task_id = uniqid();
          $start_date = time();
          $due_date = strtotime($action_due_date);
          $assigned_to = array($action_assignee => "ACTIVE");

          MnoSoaLogger::debug(__FUNCTION__ . " creating task: name=$action_name_combined, id=$task_id, start_date=$start_date, due_date=$due_date, assigned_to=$assigned_to");
          $task = array(
            "id" => $task_id,
            "name" => $action_name_combined,
            "description" => $description,
            "status" => "Not Started",
            "startDate" => $start_date,
            "dueDate" => $due_date,
            "assignedTo" => $assigned_to
          );
          $local_entity->tasks[$task_id] = $task;
        }
      }

      $mno_person->send($local_entity);

      Yii::app()->session['flashmessage'] = 'The meeting summary has been saved';
    } else {
      Yii::app()->session['flashmessage'] = 'You must specify an Organization and a Person';
    }

    MnoSoaLogger::debug(__FUNCTION__ . " end");

    $this->redirect($this->createUrl("/facci/create", array('token' => '076823aa27bc')));
  }

}
