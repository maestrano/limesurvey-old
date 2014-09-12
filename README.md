limesurvey
==========

## Maestrano integration

### Organizations

- Organizations are saved as a Labelset under the default key 'Organizations'. This allows to create Answers using the list of Organizations.

### Persons

- Persons are saved as a Labelset under the default key 'Persons'. This allows to create Answers using the list of Persons.
- Persons are saved as Labelsets with the key being their Organization name. This allows to create Answers using the list of Organization's contacts.

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