<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');

$object = new Questionnaire($db);
$form = new Form($db);

if (!empty($id)) $object->load($id);
elseif (!empty($ref)) $object->loadBy($ref, 'ref');

$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

llxHeader();
$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'invitation', $langs->trans("questionnaire"), 0, $picto, 1);

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/invitation.tpl.php'
		,array() // Block
		,array(
				'object'=>$object
				,'view' => array(
						'mode' => $mode
						,'action' => 'save'
						,'urlcard' => dol_buildpath('/questionnaire/card.php', 1)
						,'urllist' => dol_buildpath('/questionnaire/list.php', 1)
						,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
						,'showTitle' => $formcore->texte('', 'title', $object->title, 80, 255)
						,'showStatus' => $object->getLibStatut(1)
				)
				,'langs' => $langs
				,'user' => $user
				,'conf' => $conf
				,'Questionnaire' => array(
						'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
						,'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
						,'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
				)
		)
		);