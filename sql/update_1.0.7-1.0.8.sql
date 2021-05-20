UPDATE llx_quest_invitation_user SET fk_statut = 0 WHERE fk_statut IS NULL;
ALTER TABLE llx_quest_invitation_user MODIFY COLUMN fk_statut int NOT NULL DEFAULT 0;
