<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');


$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$page=GETPOST('page');
if(empty($page))$page=1;


$object = new InvitationUser($db);
$object->load($id);
$title = $langs->trans("Module104961Name");

$TArrayOfCss = array('/questionnaire/css/questionnaire.css');

if ((float) DOL_VERSION == 6.0)
{
	$TArrayOfCss[] = '/theme/common/fontawesome/css/font-awesome.css';
}

llxHeader('', $title, '', '', 0, 0, array(), $TArrayOfCss);

$head = answer_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'card', $langs->trans("answer"), 0, $picto, 1);
$object->picto = 'questionnaire@questionnaire';
_getBanner($object, $action, false, false, true);



$questionnaire = new Questionnaire($db);
$questionnaire->load($object->fk_questionnaire);
if (empty($object->questions))
	$questionnaire->loadQuestions($page);
if (!empty($questionnaire->questions))
{
	print draw_pagination($page, $questionnaire);
	print '<div id="allQuestions">';
	foreach ($questionnaire->questions as &$q)
	{
		if(empty($q->answers)) $q->loadAnswers($object->id);
		print draw_question_for_user($q);
	}
	print '</div>';
}



llxFooter();

?>
<script>
$(document).ready(function(){
	$("#allQuestions :input").attr("disabled", true);
});	

</script>