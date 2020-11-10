<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
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


$object = new InvitationUser($db);
$object->load($id);
$title=$langs->trans("Module104961Name");

$TArrayOfCss = array('/questionnaire/css/questionnaire.css');

if((float) DOL_VERSION == 6.0) {
$TArrayOfCss[] = '/theme/common/fontawesome/css/font-awesome.css';
}

llxHeader('',$title, '','',0,0, array(), $TArrayOfCss);

$head = answer_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'monitor', $langs->trans("answer"), 0, $picto, 1);
$object->picto='questionnaire@questionnaire';
_getBanner($object, $action, false, false, true);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

dol_print_object_info($object);

print '</div>';

dol_fiche_end();

llxFooter();
$db->close();
