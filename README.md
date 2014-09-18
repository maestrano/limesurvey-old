limesurvey Maestrano
====================

## Maestrano integration

### Organizations

- Organizations are saved as a Labelset under the default key 'Organizations'. This allows to create Answers using the list of Organizations.

### Persons

- Persons are saved as a Labelset under the default key 'Persons'. This allows to create Answers using the list of Persons. The the person label code references the Organization label code to allow mapping.

### Survey integration
#### Organization selection:
1 - Create a 'List with comment' question with code 'ORGANIZATION'
2 - Select the Ansers to this question as the LabelSet 'ORGANIZATION'

#### Person selection:
1 - Create a 'List with comment' question with code 'PERSON'
2 - Select the Ansers to this question as the LabelSet 'PERSON'
3 - To enable the Person dropdown filtering based on selected Organization, edit the question and in the 'Help' section add the following code:
```
<script type="text/javascript" charset="utf-8">
selectFilterByCode({PERSON.qid},{ORGANIZATION.qid});
</script>
```

#### Notes:
If a survey contains questions with the codes 'PERSON' and 'ORGANIZATION" stated above, responses are synchronized as Person Notes:
 - tag: the question code
 - value: the answer to the question
 - description: detail of the survey name, question and answer

### FACCI Customisation
The page /index.php/facci/create is a custom form mapping a meeting summary as Person activities and notes.


### For developers
#### Create a patch
This command creates a diff patch from 2 commits ignoring Maestrano configuration
```
$ git diff commit1 commit2 | filterdiff -p 1 -x "maestrano/app/config/*" > limesurvey.diff
```
