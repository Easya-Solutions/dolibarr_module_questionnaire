<?php


define("NOSCANGETFORINJECTION", true);
define("NOSCANPOSTFORINJECTION", true);

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

if(empty($user->rights->questionnaire->read)) accessforbidden();

$langs->load('questionnaire@questionnaire');



$action = GETPOST('action','alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref','alpha');

$object = new Questionnaire($db);


$hookmanager->initHooks(array('questionnairecardconf'));


if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);


/*
 * Actions
 */
$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	if($action == 'save') {
		$object->setValues($_REQUEST); // Set standard attributes



		if ($error > 0)
		{
			$mode = 'edit';
            setEventMessage($langs->trans('Error'), 'errors');
		}
		else
		{
			$object->save(empty($object->ref));
			setEventMessage($langs->trans('Saved'));
			header('Location: '.dol_buildpath('/questionnaire/conf.php', 1).'?id='.$object->id);
			exit;
		}
	}
}

/**
 * View
 */
$title = $langs->trans("Module104961Name");

$TArrayOfCss = array('/questionnaire/css/questionnaire.css');

if ((float) DOL_VERSION == 6.0)
{
	$TArrayOfCss[] = '/theme/common/fontawesome/css/font-awesome.css';
}

llxHeader('', $title, '', '', 0, 0, array(), $TArrayOfCss);

$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'config', $langs->trans("questionnaire"), 0, $picto, 1);
_getBanner($object, $action, false, $shownav, $show_linkback);
dol_get_fiche_end();


print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="post" >';
print '<input type="hidden" name="action" value="save">';

print '<table>';
print '<tr class="oddeven">';
print '<td valign="top">';
print $form->textwithtooltip( $langs->trans('HtmlCodeDisplayAtEndOfSurvey') , $langs->trans('HtmlCodeDisplayAtEndOfSurveyHelp'),2,1,img_help(1,''));
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';

if(empty($object->after_answer_html) && !empty($conf->global->QUESTIONNAIRE_DEFAULT_AFTER_ANSWER_HTML)){
    $object->after_answer_html = $conf->global->QUESTIONNAIRE_DEFAULT_AFTER_ANSWER_HTML;
}

print '<textarea  name="after_answer_html"  rows="8" cols="65" >'.dol_htmlentities($object->after_answer_html).'</textarea>';


print '</form>';
print '</td></tr>';


print '</table>';

print '<div class="tabsAction">';

print '<div class="inline-block divButAction"><button class="butAction" name="save" value="true" >' . $langs->trans('Save') . '</button></div>';


print '</div>';



$invitationUser = new InvitationUser($db);

$substitution_questionnaire = $object->get_substitutionArray('questionnaire');
$substitution_invitation_user = $invitationUser->get_substitutionArray('invitation');
print '<div class="left" >';
print '<h5>'.$langs->trans('SubstitutionsForQuestionnaire').'</h5>';
print '<ul>';
foreach ($substitution_questionnaire as $key => $val){
    print '<li>'.$key.'</li>';
}
print '</ul>';

print '<h5>'.$langs->trans('SubstitutionsForInvitationUser').'</h5>';
print '<ul>';
foreach ($substitution_invitation_user as $key => $val){
    print '<li>'.$key.'</li>';
}
print '</ul>';
print '</div>';



print '</div>';

print '</form>';


llxFooter();
