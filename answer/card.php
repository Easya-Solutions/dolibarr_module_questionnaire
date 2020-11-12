<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');


$langs->load('questionnaire@questionnaire');

$action = GETPOST('action','alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref','alpha');

$page=GETPOST('page','none');
if(empty($page))$page=1;


$object = new InvitationUser($db);
if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);


$formfile = new FormFile($db);
$upload_dir=DOL_DATA_ROOT.'/questionnaire';
if(!is_dir($upload_dir))mkdir($upload_dir);
	
$permissioncreate=1;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';



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
_getBanner($object, $action, false, true, true);

print '<hr>';

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
	print '</div>';
}
print '<br/>';
print '<div class="fichecenter"><div class="fichehalfleft">';
print '<a name="builddoc"></a>'; // ancre
/*
 * Documents generes
 */
$filename = dol_sanitizeFileName($object->ref);
$filedir = $upload_dir."/".dol_sanitizeFileName($object->ref);
$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id."&page=".GETPOST('page','none');
$genallowed = 1;
$delallowed = 1;

print $formfile->showdocuments('questionnaire', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang, '', $object);

print '</div>';
print '</div>';

llxFooter();

?>
<script>
$(document).ready(function(){
	$("#allQuestions :input").attr("disabled", true);
});	

</script>