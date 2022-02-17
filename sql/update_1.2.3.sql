ALTER TABLE llx_quest_questionnaire MODIFY COLUMN import_key INT(11) NULL;
ALTER TABLE llx_quest_questionnaire MODIFY COLUMN originid INT(11) NULL;
ALTER TABLE llx_quest_questionnaire MODIFY COLUMN answer_deadline INT(11) NULL;

ALTER TABLE llx_quest_invitation_user MODIFY COLUMN fk_usergroup INT(11) NULL;
ALTER TABLE llx_quest_invitation_user MODIFY COLUMN sent INT(11) NULL;

ALTER TABLE llx_quest_question MODIFY COLUMN originid INT(11) NULL;
