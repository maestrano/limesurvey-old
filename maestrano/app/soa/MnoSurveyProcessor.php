<?php

/**
 * Survey processing.
 * Extract relevant data and send to Connec!.
 */
class MnoSurveyProcessor {
    public static function updateFromSurveyAttributes($survey_id, $data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start with data " . json_encode($data));

        // Find or Create an Organization based on user selection
        $organization_label = MnoSurveyProcessor::extractSelectedOrganization($survey_id, $data);
        if(is_null($organization_label)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Organization not created");
          return null;
        }
        $mno_organization_id = $organization_label->mno_uid;

        // Find or Create a Person based on user selection
        $mno_person = MnoSurveyProcessor::extractSelectedPerson($survey_id, $data, $organization_label);
        if(is_null($mno_person)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Person not created");
          return null;
        }
        $mno_person_id = $mno_person->participant_id;

        // Find survey description
        $survey_settings = Surveys_languagesettings::model()->findByAttributes(array('surveyls_survey_id' => $survey_id, 'surveyls_language' => 'en'));
        $survey_description = '';
        if(!is_null($survey_settings)) {
          $survey_description = $survey_settings->surveyls_title;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " updating notes for person uid " . $mno_person_id);
        $local_entity = (object) array();
        $local_entity->participant_id = $mno_person_id;
        $local_entity->id = $mno_person_id;
        $local_entity->notes = array();

        // Map each survey answer to a note
        $ignored_questions = array("ORGANIZATIONS", "PERSONS");
        foreach ($data as $key=>$value) {
          if(preg_match_all("/^(\d+)X(\d+)X(\d+).*$/", $key, $matches)) {
            $val = (is_null($value) ? NULL : $value['value']);
            if(is_null($val)) { continue; }

            // Delete 'comment' from question key ('xxxx-xxxxcomment' becomes 'xxxx-xxxx')
            $key = str_replace("comment", "", $key);

            $question_id = $matches[3][0];
            MnoSoaLogger::debug(__FUNCTION__ . " finding question " . $question_id . " - key " . $key);

            $question = Questions::model()->findByAttributes(array('qid' => $question_id));
            if (in_array($question->title, $ignored_questions)) {
              continue;
            }

            MnoSoaLogger::debug(__FUNCTION__ . " mapping question " . json_encode($question));

            $answer = Answers::model()->findByAttributes(array('code' => $val));
            $answer_value = $answer ? $answer->answer : $val;
            if(!is_null($question) && !is_null($answer_value) && $answer_value != '') {
              $note_id = "$mno_person_id-$key";
              // Create a new Note section or append comment to value
              if(is_null($local_entity->notes[$note_id])) {
                $local_entity->notes[$note_id] = array('description' => "$survey_description - $question->question => $answer_value",
                                                       'tag' => $question->title, 'value' => $answer_value);
              } else {
                $local_entity->notes[$note_id]['value'] .= " - $answer_value";
                $local_entity->notes[$note_id]['description'] .= " - $answer_value";
              }
            }
          }
        }

        // Push updated Person to Connec!
        $mno_person = new MnoSoaPerson();
        $mno_person->send($local_entity);

        // Push an EventOrder
        $event_order_data = MnoSurveyProcessor::extractEventOrder($survey_id, $data, $mno_person_id);
        if(!is_null($event_order_data)) {
          $mno_event_order = new MnoSoaEventOrder();
          $mno_event_order->send($event_order_data);
        }
 
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }

    private static function extractSelectedOrganization($survey_id, $data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        
        // Find if an ORGANIZATION question exists in the survey
        $orgQuestion = MnoSurveyProcessor::getQuestion($survey_id, 'ORGANIZATIONS');
        if(is_null($orgQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map organization, skipping.");
          return null;
        }

        // Find selected Organization or create one
        $selectedOrganization = MnoSurveyProcessor::getResponse($data, $orgQuestion->qid);
        return MnoSurveyProcessor::findOrCreateOrganization($selectedOrganization);
    }

    public static function findOrCreateOrganization($selectedOrganization) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        if(is_null($selectedOrganization) || $selectedOrganization == '') {
          MnoSoaLogger::debug(__FUNCTION__ . " user did not specify an organization, skipping.");
          return null;
        }

        // If the selected Organization matches a Label, it already exists
        $label = Label::model()->findByAttributes(array('code' => $selectedOrganization));
        if(isset($label)) {
          MnoSoaLogger::debug(__FUNCTION__ . " fetching organization with mno_uid: " . $label->mno_uid);
          $organization_label = MnoSoaOrganization::getLocalEntityByLocalIdentifier($label->mno_uid);
        }
        else {
          MnoSoaLogger::debug(__FUNCTION__ . " create a new organization: " . $selectedOrganization);
          $mno_organization = new MnoSoaOrganization();
          $local_entity = (object) array();
          $local_entity->name = $selectedOrganization;
          $mno_uid = $mno_organization->send($local_entity);
          $mno_organization->mno_uid = $mno_uid;
          $local_entity->mno_uid = $mno_uid;
          $local_entity->id = $mno_uid;

          MnoSoaLogger::debug(__FUNCTION__ . " created organization: " . $mno_uid);
          
          // Save Organization as a new Label
          $newlabel = $mno_organization->saveAsLabel();
          $organization_label = MnoSoaOrganization::getLocalEntityByLocalIdentifier($mno_uid);
        }

        MnoSoaLogger::debug(__FUNCTION__ . " end - created new Organization " . json_encode($mno_organization));

        return $organization_label;
    }

    private static function extractSelectedPerson($survey_id, $data, $organization_label) {
        MnoSoaLogger::debug(__FUNCTION__ . " start for organization_label: " . json_encode($organization_label));
        
        // Find if a PERSON question exists in the survey
        $personQuestion = MnoSurveyProcessor::getQuestion($survey_id, 'PERSONS');
        if(is_null($personQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map person, skipping.");
          return null;
        }
        
        // Find selected Organization or create one
        $selectedPerson = MnoSurveyProcessor::getResponse($data, $personQuestion->qid);
        MnoSoaLogger::debug(__FUNCTION__ . " selected person response: " . $selectedPerson);
        return MnoSurveyProcessor::findOrCreatePerson($selectedPerson, $organization_label);
    }

    public static function findOrCreatePerson($selectedPerson, $organization_label) {
        MnoSoaLogger::debug(__FUNCTION__ . " start selectedPerson: $selectedPerson, organization_label: " . json_encode($organization_label));
        if(is_null($selectedPerson) || trim($selectedPerson) == false) {
          MnoSoaLogger::debug(__FUNCTION__ . " user did not specify a person, skipping.");
          return null;
        }

        // If the selected person matches a Label, it already exists
        $label = Label::model()->findByAttributes(array('code' => $selectedPerson));
        if(isset($label)) {
          MnoSoaLogger::debug(__FUNCTION__ . " fetching person with mno_uid: " . $label->mno_uid);
          $person_label = MnoSoaPerson::getLocalEntityByLocalIdentifier($label->mno_uid);
        }
        else {
          MnoSoaLogger::debug(__FUNCTION__ . " creating a new person: " . $selectedPerson);
          $mno_person = new MnoSoaPerson();
          $local_entity = (object) array();
          
          // Split person name
          if (strpos($selectedPerson,' ') !== false) {
            $names = explode(" ", $selectedPerson);
            $local_entity->lastname = $names[1];
            $local_entity->firstname = $names[0];
          } else {
            $local_entity->lastname = $selectedPerson;
          }
          
          $local_entity->organization = $organization_label->mno_uid;
          $local_entity->blacklisted = 'N';
          $local_entity->language = 'en';
          $local_entity->owner_uid = 1;

          $mno_uid = $mno_person->send($local_entity);
          $mno_person->participant_id = $mno_uid;
          $mno_person->mno_uid = $mno_uid;
          $local_entity->participant_id = $mno_uid;
          $local_entity->mno_uid = $mno_uid;
          $local_entity->_id = $mno_uid;

          MnoSoaLogger::debug(__FUNCTION__ . " created person: " . $mno_uid);

          // Save Person as a new Label
          $mno_person->insertLocalEntity();
          $person_label = MnoSoaPerson::getLocalEntityByLocalIdentifier($mno_uid);
        }

        MnoSoaLogger::debug(__FUNCTION__ . " end - returning: " . json_encode($mno_person));

        return $person_label;
    }

    private static function extractEventOrder($survey_id, $data, $mno_person_id) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        // Extract the Event ID
        $event_code = $data['EVENT'];
        if($event_code === '') {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map event, skipping.");
          return null;
        }

        $event_label = Label::model()->findByAttributes(array('code' => $event_code));
        $event_id = $event_label['mno_uid'];

        // Extract number of tickets
        $tickets = 0;
        $ticketQuestion = MnoSurveyProcessor::getQuestion($survey_id, 'TICKET_AMOUNT');
        if(is_null($ticketQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map ticket, skipping.");
        } else {
          $selectedTicket = MnoSurveyProcessor::getResponse($data, $ticketQuestion->qid);
          $tickets = intval($selectedTicket);
        }

        // Create the EventOrder object
        $event_order_hash = (object) array();
        $event_order_hash->status = 'PLACED';
        $event_order_hash->event_id = $event_id;
        $event_order_hash->person_id = $mno_person_id;
        $event_order_hash->attendees = array();
        array_push($event_order_hash->attendees, array('status' => 'ATTENDING', 'quantity' => $tickets, 'person' => array('id' => $mno_person_id)));

        return $event_order_hash;
    }

    private static function getQuestion($survey_id, $title) {
      $question = Questions::model()->findByAttributes(array('title' => $title, 'sid' => $survey_id));
      if(is_null($question)) { return null; }
      
      MnoSoaLogger::debug(__FUNCTION__ . " $title question id: " . $question->qid);
      return $question;
    }

    // Find the user response to a question by Question ID
    private static function getResponse($data, $questionId) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        // Return the selected response or alternatively try to find a 'comment' response
        $response = null;
        foreach ($data as $key=>$value) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey question key " . $key . " => " . $value);

          // Survey answer key has format: [SURVEY_ID]X[QUESTION_GROUP]X[QUESTION_ID] (eg: 397449X1X7)
          if(preg_match_all("/^(\d+)X(\d+)X(\d+)$/", $key, $matches)) {
            MnoSoaLogger::debug(__FUNCTION__ . " comparing " . $matches[3][0] . " and " . $questionId);
            if($matches[3][0] == strval($questionId)) {
              $response = $value['value'];
              MnoSoaLogger::debug(__FUNCTION__ . " found response: " . $response);
            }
          }

          // Survey answer comment key has format: [SURVEY_ID]X[QUESTION_GROUP]X[QUESTION_ID]comment (eg: 397449X1X7comment)
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
}

?>