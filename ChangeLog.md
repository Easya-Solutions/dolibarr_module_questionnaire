# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

## Version 1.2

- FIX : Seed Object signature *2021-05-21* - 1.2.1
  
- NEW : Ajout d'une nouvelle option dans le module "Nombre de jour par défaut pour le délais de réponse" *2021-05-21* - 1.2.0 - OpenDsi
- NEW : Modification des libellé des statuts des invitations au questionnaire et aujout du statut "Non Répondu" et "Répondu partiellement" *2021-05-21* - 1.2.0 - OpenDsi
- NEW : Les statuts sont maintenant dans l'ordre "En attente de réponse", "En cours", "Répondu", "Répondu partiellement", "Non Répondu" " *2021-05-21* - 1.2.0 - OpenDsi
- NEW : Enregistrement de la date de rappel *2021-05-21* - 1.2.0 - OpenDsi
- NEW : Ajout d'une action d'annulation d'une invitation *2021-05-21* - 1.2.0 - OpenDsi
  
  *Questionnaire :*
  
  QUESTIONNAIRE_REF
  QUESTIONNAIRE_TITLE
  QUESTIONNAIRE_STATUS
  
  *Questionnaire invitation :*
  
  QUESTIONNAIRE_INVITATION_REF
  QUESTIONNAIRE_INVITATION_TOKEN
  QUESTIONNAIRE_INVITATION_EMAIL
  QUESTIONNAIRE_INVITATION_CONTACT_NAME
  QUESTIONNAIRE_INVITATION_CONTACT_NAME_CIVILITY
  QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME
  QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME_CIVILITY
  QUESTIONNAIRE_INVITATION_COMPANY
  QUESTIONNAIRE_INVITATION_SENT_STATUS
  QUESTIONNAIRE_INVITATION_DATE_ANSWER_DEADLINE
  QUESTIONNAIRE_INVITATION_DATE_VALIDATION
  QUESTIONNAIRE_INVITATION_DATE_SENT
  QUESTIONNAIRE_INVITATION_DATE_SENT_REMIND
  QUESTIONNAIRE_INVITATION_STATUS
  QUESTIONNAIRE_INVITATION_ANSWER_LINK  

- NEW : Envoie des mails avec le support des substitutions au standard Dolibarr *2021-05-21* - 1.2.0 - OpenDsi
- NEW : Ajout de l'option "Nombre de jour par défaut pour le délais de réponse" sur la fiche du questionnaire écrasant celle par défaut *2021-05-21* - 1.2.0 - OpenDsi
- FIX : Corrections du dossier de generation des PDF au standard Dolibarr " *2021-05-21* - 1.2.0 - OpenDsi
- FIX : Correction d'affichage de toutes les questions en mode edition meme les choix multiple sans reponses *2021-05-21* - 1.2.0 - OpenDsi
- FIX : Correction de la copie du lien vers le formulaire de reponse au questionnaire *2021-05-21* - 1.2.0 - OpenDsi

## Version 1.0

- FIX : Dom id  *21/05/2021* - 1.0.6
- FIX : Compatibility V13 - add token renowal *18/05/2021* 1.0.5
- FIX : Onglet réponse n'affiche rien [2021-01-12]
- FIX : Titre et texte non affichés en mode view [2021-01-12]
- FIX : Remove unused Box
