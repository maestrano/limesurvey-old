ALTER TABLE  `lime_labelsets` ADD  `mno_uid` VARCHAR( 255 ) NULL DEFAULT NULL ;
ALTER TABLE  `lime_labels` ADD  `mno_uid` VARCHAR( 255 ) NULL DEFAULT NULL ;
INSERT INTO `lime_labelsets` (label_name, languages, mno_uid) VALUES ('Organizations', 'en', 'ORGANIZATIONS');
INSERT INTO `lime_labelsets` (label_name, languages, mno_uid) VALUES ('Persons', 'en', 'PERSONS');
INSERT INTO `lime_labelsets` (label_name, languages, mno_uid) VALUES ('Users', 'en', 'USERS');
INSERT INTO `lime_labelsets` (label_name, languages, mno_uid) VALUES ('Events', 'en', 'EVENTS');
ALTER TABLE `lime_labels` MODIFY code VARCHAR(12);
ALTER TABLE `lime_answers` MODIFY code VARCHAR(12);
