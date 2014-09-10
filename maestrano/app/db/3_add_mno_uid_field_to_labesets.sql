ALTER TABLE  `lime_labelsets` ADD  `mno_uid` VARCHAR( 255 ) NULL DEFAULT NULL ;
ALTER TABLE  `lime_labels` ADD  `mno_uid` VARCHAR( 255 ) NULL DEFAULT NULL ;
INSERT INTO `lime_labelsets` (label_name, languages, mno_uid) VALUES ('Organizations', 'en', '0');