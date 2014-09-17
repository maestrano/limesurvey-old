<?php

/**
 * Survey processing.
 * Extract relevant data and send to ESB.
 */
class MnoSurveyProcessor
{
    public static function updateFromSurveyAttributes($survey_id, $data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        // Find or Create an Organization based on user selection
        $mno_organization_id = MnoSurveyProcessor::extractSelectedOrganization($survey_id, $data);
        if(is_null($mno_organization_id)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Organization not created");
          return null;
        }

        // Find or Create a Person based on user selection
        $mno_person_id = MnoSurveyProcessor::extractSelectedPerson($survey_id, $data, $mno_organization_id);
        if(is_null($mno_person_id)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Person not created");
          return null;
        }

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
        $ignored_questions = array("ORGANIZATION", "PERSON");
        foreach ($data as $key=>$value) {
          if(preg_match_all("/(\d+)X(\d+)X(\d+).*/", $key, $matches)) {
            $val = (is_null($value) ? NULL : $value['value']);
            if(is_null($val)) {
              continue;
            }

            // Delete 'comment' from question key ('xxxx-xxxxcomment' becomes 'xxxx-xxxx')
            $key = str_replace("comment", "", $key);

            $question_id = $matches[3][0];
            MnoSoaLogger::debug(__FUNCTION__ . " finding question " . $question_id);

            $question = Questions::model()->findByAttributes(array('qid' => $question_id));
            if (in_array($question->title, $ignored_questions)) {
              continue;
            }

            $answer = Answers::model()->findByAttributes(array('code' => $val));
            $answer_value = $answer ? $answer->answer : $val;
            if(!is_null($question) && !is_null($answer_value) && $answer_value != '') {
              $note_id = "$mno_person_id-$key";
              if(is_null($local_entity->notes[$note_id])) {
                $local_entity->notes[$note_id] = array('description' => "$survey_description - $question->question => $answer_value", 'tag' => $question->title);
              } else {
                $local_entity->notes[$note_id]['description'] .= " - $answer_value";
              }
            }
          }
        }

        $mno_person = new MnoSoaPerson();
        $mno_person->send($local_entity);
 
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }

    private static function extractSelectedOrganization($survey_id, $data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        
        // Find if an ORGANIZATION question exists in the survey
        $orgQuestion = Questions::model()->findByAttributes(array('title' => 'ORGANIZATION', 'sid' => $survey_id));
        if(is_null($orgQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map organization, skipping.");
          return null;
        }
        MnoSoaLogger::debug(__FUNCTION__ . " organization question id: " . $orgQuestion->qid);
        
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
          $mno_organization = MnoSoaOrganization::getLocalEntityByLocalIdentifier($label->mno_uid);
          MnoSoaLogger::debug(__FUNCTION__ . " end - returning: " . $mno_organization->mno_uid);
          return $mno_organization->mno_uid;
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

          MnoSoaLogger::debug(__FUNCTION__ . " end - created new Organization");
          return $mno_uid;
        }
    }

    private static function extractSelectedPerson($survey_id, $data, $mno_organization_id) {
        MnoSoaLogger::debug(__FUNCTION__ . " start for person: $mno_organization_id");
        
        // Find if a PERSON question exists in the survey
        $personQuestion = Questions::model()->findByAttributes(array('title' => 'PERSON', 'sid' => $survey_id));
        if(is_null($personQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map person, skipping.");
          return null;
        }
        MnoSoaLogger::debug(__FUNCTION__ . " person question id: " . $personQuestion->qid);
        
        // Find selected Organization or create one
        $selectedPerson = MnoSurveyProcessor::getResponse($data, $personQuestion->qid);
        return MnoSurveyProcessor::findOrCreatePerson($selectedPerson, $mno_organization_id);
    }

    public static function findOrCreatePerson($selectedPerson, $mno_organization_id) {
        MnoSoaLogger::debug(__FUNCTION__ . " start for person: $mno_organization_id, person: $selectedPerson");
        if(is_null($selectedPerson) || $selectedPerson == '') {
          MnoSoaLogger::debug(__FUNCTION__ . " user did not specify a person, skipping.");
          return null;
        }

        // If the selected person matches a Label, it already exists
        $label = Label::model()->findByAttributes(array('code' => $selectedPerson));
        if(isset($label)) {
          MnoSoaLogger::debug(__FUNCTION__ . " fetching person with mno_uid: " . $label->mno_uid);
          $mno_person = MnoSoaPerson::getLocalEntityByLocalIdentifier($label->mno_uid);
          MnoSoaLogger::debug(__FUNCTION__ . " end - returning: " . $mno_person->participant_id);
          return $mno_person->participant_id;
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
          
          $local_entity->organization = $mno_organization_id;
          $local_entity->blacklisted = 'N';
          $local_entity->language = 'en';
          $local_entity->owner_uid = 1;
          $mno_uid = $mno_person->send($local_entity);
          $mno_person->mno_uid = $mno_uid;
          $local_entity->participant_id = $mno_uid;
          $local_entity->mno_uid = $mno_uid;
          $local_entity->_id = $mno_uid;

          MnoSoaLogger::debug(__FUNCTION__ . " created person: " . $mno_uid);

          // Save Person as a new Label
          $newlabel = $mno_person->saveAsLabel();
          $mno_person->saveAsParticipant();

          return $mno_uid;
        }
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
}

?>