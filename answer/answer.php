<?php

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/contact/class/contact.class.php');

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_invitation_user = GETPOST('fk_invitation_user');
$title = GETPOST('title');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');



$mode = 'view';
if ($action == 'create' || $action == 'edit')
	$mode = 'edit';

$object = new Questionnaire($db);
$form = new Form($db);

if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);

if (empty($object->fk_statut))
{
	header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
	exit;
}

if (GETPOST('cancel', 'alpha'))
{
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
	$massaction = '';
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
if ($massaction == 'reopen')
{
	foreach ($arrayofselected as $inv_selected)
	{
		$invitation_user = new InvitationUser($db);
		$invitation_user->load($inv_selected);
		$invitation_user->reopen();
	}
}
else
if ($action == 'reopen')
{
	$invitation_user = new InvitationUser($db);
	$invitation_user->load($fk_invitation_user);
	$invitation_user->reopen();
}
elseif ($action == 'settitle')
{
	$object->title = $title;

	$object->save();

	header('Location: '.dol_buildpath('/questionnaire/answer/answer.php', 1).'?id='.$object->id);
	exit;
}
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

llxHeader();
$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'answer', $langs->trans("questionnaire"), 0, $picto, 1);

_getBanner($object, $action, false);

$TBS = new TTemplateTBS();
$TBS->TBS->protect = false;
$TBS->TBS->noerr = true;

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

if ($mode == 'edit')
	echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
print $TBS->render('../tpl/answer.tpl.php'
		, array() // Block
		, array(
		'object' => $object
		, 'view' => array(
			'mode' => $mode
			, 'action' => $action
			, 'urlinvitation' => dol_buildpath('/questionnaire/invitation.php', 1)
			, 'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			, 'showRef' => $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
			, 'showTitle' => $object->title
			, 'showStatus' => $object->getLibStatut(1)
			, 'list_answers' => _getListAnswers($object)
			, 'user_answers' => _seeAnswersUser($object, $fk_invitation_user)
			, 'massaction' => printMassActionButton()
		)
		, 'langs' => $langs
		, 'user' => $user
		, 'conf' => $conf
		, 'form' => array(
		)
		, 'Questionnaire' => array(
			'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
			, 'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
			, 'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
		)
		)
);

function _getListAnswers(&$object)
{

	global $db, $langs, $hookmanager, $user, $form, $conf;

	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

	$r = new TListviewTBS('invitation_list', dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));

	// On regarde s'il existe une réponse à au moins une question du questionnaire sur lequel on se trouve
	// Subquery pour chercher s'il existe une réponse validée
	$sql = 'SELECT DISTINCT iu.fk_element as id_element, iu.rowid as fk_invitation_user, "" as link_answer,COALESCE(NULLIF(iu.type_element,""), "External") as type_element, iu.fk_element,  iu.email, iu.fk_statut as fk_statut,  "" as action
			FROM '.MAIN_DB_PREFIX.'quest_invitation_user iu  
			WHERE iu.fk_questionnaire = '.$object->id.'
			AND (fk_element > 0 OR email != "")';

	//echo $sql;exit;
	$resql = $db->query($sql);
	$TData = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
		{
			$TData[] = $res;
		}
	}

	$res = $r->renderArray($db, $TData, array(
		'limit' => array(
			'page' => 1
			, 'nbLine' => 500
		)
		, 'translate' => array(
		)
		, 'link' => array(
		)
		, 'hide' => array(
			'id_element',
			'fk_invitation_user',
			'type_element'
		)
		, 'type' => array()
		, 'liste' => array(
			'titre' => $langs->trans('TitleConformiteNormeList')
			, 'image' => img_picto('', 'title.png', '', 0)
			, 'picto_precedent' => img_picto('', 'previous.png', '', 0)
			, 'picto_suivant' => img_picto('', 'next.png', '', 0)
			, 'order_down' => img_picto('', '1downarrow.png', '', 0)
			, 'order_up' => img_picto('', '1uparrow.png', '', 0)
			, 'noheader' => FALSE
			, 'messageNothing' => $langs->transnoentities('noElement')
			, 'picto_search' => img_picto('', 'search.png', '', 0)
		)
		, 'title' => array(
			'fk_element' => $langs->trans('Element')
			,'type_element' => $langs->trans('Type')
			, 'fk_statut' => $langs->trans('questionnaireAnswerStatus')
			, 'link_answer' => $langs->trans('QuestionnaireSeeAnswerLink')
			, 'email' => $langs->trans('Email')
			, 'action' => $langs->trans('Action').'&nbsp;&nbsp;&nbsp;'.$form->showCheckAddButtons('checkforselect', 1)
		)
		, 'orderBy' => array('cn.rowid' => 'DESC')
		, 'eval' => array(
			'link_answer' => '_getLinkAnswersUser(@fk_invitation_user@)'
			, 'fk_element' => '_getNomUrl(@fk_element@, Externe, @type_element@)'
			, 'fk_statut' => '_libStatut(@fk_statut@, 1)'
			, 'action' => '_actionLink(@fk_invitation_user@)'
		)
	));


	$parameters = array('sql' => $sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object);	// Note that $action and $object may have been modified by hook
	$res .= $hookmanager->resPrint;

	return $res;
}

function _getLinkAnswersUser($fk_user)
{

	global $id, $i_rep;

	$i_rep++;

	return '<a href="'.dol_buildpath('/questionnaire/answer/card.php',1).'?id='.$fk_user.'">REP'.(str_pad($i_rep, 4, 0, STR_PAD_LEFT)).'</a>';
}

function _getNomUrl($fk_element, $email, $type_element)
{

	global $db;
	$type_element= ucfirst($type_element);
	if($type_element == 'Thirdparty')$type_element='Societe';

	if(class_exists($type_element))$u = new $type_element($db);

	if (!empty($fk_element) && method_exists($u, 'getNomUrl')){
		$u->fetch($fk_element);
		$res = $u->getNomUrl(1);	
	}else
		$res = $email;
	return $res;
}

function _seeAnswersUser(&$object, $fk_invituser)
{

	global $db, $langs;

	
	$invUser = new InvitationUser($db);
	$invUser->load($fk_invituser);
	

	$class = ucfirst($invUser->type_element);
	if($invUser->type_element == 'thirdparty'){
		$invUser->type_element='societe';
		$class='Societe';
	}
	if (!empty($invUser->getFk_element()) && !empty($class))
	{
		require_once DOL_DOCUMENT_ROOT.'/'.$invUser->type_element.'/class/'.$invUser->type_element.'.class.php';
		
		$u = new $class($db);
		$u->fetch($invUser->getFk_element());
		
		$res = $langs->trans('questionnaireUserAnswersOf', $u->getNomUrl(1));
	}
	else
	{
		$res = $langs->trans('questionnaireUserAnswersOf', $invUser->email);
	}
	if (empty($object->questions))
		$object->loadQuestions();
	$res .= '<div id="allQuestions">';
	if (!empty($object->questions))
	{
		foreach ($object->questions as &$q)
		{
			if (empty($q->answers))
				$q->loadAnswers($fk_invituser);
		//	$res .= draw_answer($q).'<br />';
		}
	}
	$res .= '</div>';


	if ($invUser->fk_statut ==1)
	{
		$res .= '<form name="answerQuestionnaire" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.GETPOST('id').'">';
		$res .= '<input type="HIDDEN" name="fk_invitation_user" value="'.$fk_invituser.'"/>';
		$res .= '<input type="HIDDEN" name="action" value="reopen"/>';

		$res .= '<div class="center"><input class="butAction" name="reopenbt" type="SUBMIT" value="Rouvrir"/>';
		$res .= '</form>';
	}
	return $res;
}

function _libStatut($status, $mode)
{

	global $db, $langs, $id, $questionnaire_status_forced_key;

	if ($status == 1)
		$questionnaire_status_forced_key = 'answerValidate';
	else
		$questionnaire_status_forced_key = '';

	// Juste pour utiliser la fonction LibStatus
	$q = new Questionnaire($db);
	$q->fetch($id);

	return $q->LibStatut($status, 6);
}

function _actionLink($fk_invit)
{
	global $object, $massactionbutton, $massaction, $arrayofselected;

	if (1)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($fk_invit, $arrayofselected))
			$selected = 1;
		$link .= '&nbsp;&nbsp;&nbsp;<input id="cb'.$fk_invit.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$fk_invit.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	return $link;
}

function printMassActionButton()
{
	global $formcore, $langs, $form, $massaction, $toselect;


	$ret = $formcore->begin_form($_SERVER['PHP_SELF'], 'form_massaction');

	$ret .= '<input hidden name="id" type="text" value="'.GETPOST('id').'"/>';

	$arrayofmassactions = array(
		'reopen' => $langs->trans("Reopen"),
	);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$ret .= '<td class="nobordernopadding center valignmiddle">'.$massactionbutton.'</td>';
	return $ret;
}

llxFooter();
