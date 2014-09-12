<?php

/**
 * Survey processing
 */
class MnoSurveyProcessor
{
    public static function updateFromSurveyAttributes($data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");

        // Find or Create an Organization based on user selection
        $mno_organization_id = MnoSurveyProcessor::findOrCreateOrganization($data);
        if(is_null($mno_organization_id)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Organization not created");
          return null;
        }

        // Find or Create a Person based on user selection
        $mno_person_id = MnoSurveyProcessor::findOrCreatePerson($data, $mno_organization_id);
        if(is_null($mno_person_id)) {
          MnoSoaLogger::debug(__FUNCTION__ . " end - Person not created");
          return null;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " updating notes for person uid " . $mno_person_id);
        $local_entity = (object) array();
        $local_entity->participant_id = $mno_person_id;
        $local_entity->notes = array();
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
            $answer_value = $answer ? $answer->answer : $val;
            if(!is_null($question) && !is_null($answer_value) && $answer_value != '') {
              MnoSoaLogger::debug(__FUNCTION__ . " adding note: " . $question->question . " => " . $answer_value);
              $local_entity->notes[$key] = array('description' => $question->question . " => " . $answer_value);
            }
          }
        }

        $mno_person = new MnoSoaPerson();
        $mno_person->send($local_entity);
 
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }

    private static function findOrCreateOrganization($data) {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        // Find if an ORGANIZATION question exists in the survey
        $orgQuestion = Questions::model()->findByAttributes(array('title' => 'ORGANIZATION'));
        if(is_null($orgQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map organization, skipping.");
          return null;
        }
        MnoSoaLogger::debug(__FUNCTION__ . " organization question id: " . $orgQuestion->qid);
        // Find selected Organization or create one
        $selectedOrganization = MnoSurveyProcessor::getResponse($data, $orgQuestion->qid);
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

          MnoSoaDB::addIdMapEntry($mno_uid, MnoSoaOrganization::getLocalEntityName(), $mno_uid, 'ORGANIZATIONS');
          MnoSoaLogger::debug(__FUNCTION__ . " created organization: " . $mno_uid);
          
          // Save Organization as a new Label
          $newlabel = $mno_organization->saveAsLabel();

          // Save Organization as new possible Answer to this survey
          MnoSoaLogger::debug(__FUNCTION__ . " saving organization as a new possible answer");
          $answer = new Answers();
          $answer->qid = $orgQuestion->qid;
          $answer->sortorder = $newlabel->sortorder;
          $answer->code = $newlabel->code;
          $answer->answer = $newlabel->title;
          $answer->assessment_value = $newlabel->assessment_value;
          $answer->language = 'en';
          $answer->save();
          MnoSoaLogger::debug(__FUNCTION__ . " created new answer");

          MnoSoaLogger::debug(__FUNCTION__ . " end - created new Organization");
          return $mno_uid;
        }
    }

    private static function findOrCreatePerson($data, $mno_organization_id) {
        MnoSoaLogger::debug(__FUNCTION__ . " start for organization: " . $mno_organization_id);
        // Find if a PERSON question exists in the survey
        $personQuestion = Questions::model()->findByAttributes(array('title' => 'PERSON'));
        if(is_null($personQuestion)) {
          MnoSoaLogger::debug(__FUNCTION__ . " survey does not map person, skipping.");
          return null;
        }

        MnoSoaLogger::debug(__FUNCTION__ . " person question id: " . $personQuestion->qid);
        // Find selected Organization or create one
        $selectedPerson = MnoSurveyProcessor::getResponse($data, $personQuestion->qid);
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
            $local_entity->lastname = $names[0];
            $local_entity->firstname = $names[1];
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

          MnoSoaDB::addIdMapEntry($mno_uid, MnoSoaPerson::getLocalEntityName(), $mno_uid, 'PERSONS');
          MnoSoaLogger::debug(__FUNCTION__ . " created person: " . $mno_uid);

          // Save Person as a new Label
          $newlabel = $mno_person->saveAsLabel();
          $mno_person->saveAsParticipant();

          // Save Person as new possible Answer to this survey
          MnoSoaLogger::debug(__FUNCTION__ . " saving person as a new possible answer");
          $answer = new Answers();
          $answer->qid = $personQuestion->qid;
          $answer->sortorder = $newlabel->sortorder;
          $answer->code = $newlabel->code;
          $answer->answer = $newlabel->title;
          $answer->assessment_value = $newlabel->assessment_value;
          $answer->language = 'en';
          $answer->save();
          MnoSoaLogger::debug(__FUNCTION__ . " created new answer: " . $answer->code);

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
}

?>