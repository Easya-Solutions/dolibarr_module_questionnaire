<?php
/* Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/questionnaire/lib/functions_questionnaire.lib.php
 *	\brief      Ensemble de fonctions de substitutions pour le module Questionnaire
 * 	\ingroup	questionnaire
 */

function questionnaire_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters)
{
	global $conf;

	$mode = $parameters['mode'];
	if (($mode == 'formemail' || $mode == 'formemailwithlines')) {
		$langs->load('questionnaire@questionnaire');

		$only_key = !empty($parameters['onlykey']) || !empty($object->context['onlykey']);

		// Other
		$substitutionarray['__CHECK_READ__'] = $only_key ? '__CHECK_READ__' : $substitutionarray['__CHECK_READ__'];

		// Questionnaire
		$substitutionarray['__QUESTIONNAIRE_REF__'] = $only_key ? '__QUESTIONNAIRE_REF__' : (isset($object->context['questionnaire_ref']) ? $object->context['questionnaire_ref'] : $langs->trans('QuestionnaireSubstitutionQuestionnaireRef'));
		$substitutionarray['__QUESTIONNAIRE_TITLE__'] = $only_key ? '__QUESTIONNAIRE_TITLE__' : (isset($object->context['questionnaire_title']) ? $object->context['questionnaire_title'] : $langs->trans('QuestionnaireSubstitutionQuestionnaireTitle'));
		$substitutionarray['__QUESTIONNAIRE_STATUS__'] = $only_key ? '__QUESTIONNAIRE_STATUS__' : (isset($object->context['questionnaire_status']) ? $object->context['questionnaire_status'] : $langs->trans('QuestionnaireSubstitutionQuestionnaireStatus'));

		// Questionnaire invitation
		$substitutionarray['__QUESTIONNAIRE_INVITATION_REF__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_REF__' : (isset($object->context['questionnaire_invitation_ref']) ? $object->context['questionnaire_invitation_ref'] : $langs->trans('QuestionnaireSubstitutionInvitationRef'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_TOKEN__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_TOKEN__' : (isset($object->context['questionnaire_invitation_token']) ? $object->context['questionnaire_invitation_token'] : $langs->trans('QuestionnaireSubstitutionInvitationToken'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_EMAIL__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_EMAIL__' : (isset($object->context['questionnaire_invitation_email']) ? $object->context['questionnaire_invitation_email'] : $langs->trans('QuestionnaireSubstitutionInvitationEmail'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_CONTACT_NAME__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_CONTACT_NAME__' : (isset($object->context['questionnaire_invitation_contact_name']) ? $object->context['questionnaire_invitation_contact_name'] : $langs->trans('QuestionnaireSubstitutionInvitationContactName'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_CONTACT_NAME_CIVILITY__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_CONTACT_NAME_CIVILITY__' : (isset($object->context['questionnaire_invitation_contact_name_civility']) ? $object->context['questionnaire_invitation_contact_name_civility'] : $langs->trans('QuestionnaireSubstitutionInvitationContactCivilityName'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME__' : (isset($object->context['questionnaire_invitation_contact_full_name']) ? $object->context['questionnaire_invitation_contact_full_name'] : $langs->trans('QuestionnaireSubstitutionInvitationContactFullName'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME_CIVILITY__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_CONTACT_FULL_NAME_CIVILITY__' : (isset($object->context['questionnaire_invitation_contact_full_name_civility']) ? $object->context['questionnaire_invitation_contact_full_name_civility'] : $langs->trans('QuestionnaireSubstitutionInvitationContactCivilityFullName'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_COMPANY__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_COMPANY__' : (isset($object->context['questionnaire_invitation_company']) ? $object->context['questionnaire_invitation_company'] : $langs->trans('QuestionnaireSubstitutionInvitationCompany'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_SENT_STATUS__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_SENT_STATUS__' : (isset($object->context['questionnaire_invitation_sent_status']) ? $object->context['questionnaire_invitation_sent_status'] : $langs->trans('QuestionnaireSubstitutionInvitationSentStatus'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_DATE_ANSWER_DEADLINE__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_DATE_ANSWER_DEADLINE__' : (isset($object->context['questionnaire_invitation_date_answer_deadline']) ? $object->context['questionnaire_invitation_date_answer_deadline'] : $langs->trans('QuestionnaireSubstitutionInvitationDateAnswerDeadline'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_DATE_VALIDATION__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_DATE_VALIDATION__' : (isset($object->context['questionnaire_invitation_date_validation']) ? $object->context['questionnaire_invitation_date_validation'] : $langs->trans('QuestionnaireSubstitutionInvitationDateValidation'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_DATE_SENT__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_DATE_SENT__' : (isset($object->context['questionnaire_invitation_date_sent']) ? $object->context['questionnaire_invitation_date_sent'] : $langs->trans('QuestionnaireSubstitutionInvitationDateSent'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_DATE_SENT_REMIND__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_DATE_SENT_REMIND__' : (isset($object->context['questionnaire_invitation_date_sent_remind']) ? $object->context['questionnaire_invitation_date_sent_remind'] : $langs->trans('QuestionnaireSubstitutionInvitationDateSentRemind'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_STATUS__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_STATUS__' : (isset($object->context['questionnaire_invitation_status']) ? $object->context['questionnaire_invitation_status'] : $langs->trans('QuestionnaireSubstitutionInvitationStatus'));
		$substitutionarray['__QUESTIONNAIRE_INVITATION_ANSWER_LINK__'] = $only_key ? '__QUESTIONNAIRE_INVITATION_ANSWER_LINK__' : (isset($object->context['questionnaire_invitation_answer_link']) ? $object->context['questionnaire_invitation_answer_link'] : $langs->trans('QuestionnaireSubstitutionInvitationAnswerLink'));
    }
}