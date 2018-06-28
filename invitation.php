<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/user/class/usergroup.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/contact/class/contact.class.php');

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_invitation = GETPOST('fk_invitation');
$groups = GETPOST('usergroups');
$users = GETPOST('users');
$emails = GETPOST('emails');
$date_limite_year = GETPOST('date_limiteyear');
$date_limite_month = GETPOST('date_limitemonth');
$date_limite_day = GETPOST('date_limiteday');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$title=GETPOST('title');
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

$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

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

//var_dump($massaction,$arrayofselected);exit;

if (!empty($massaction) && $massaction == 'send' && !empty($arrayofselected))
{
	$langs->load('mails');
	$invuser = new InvitationUser($db);

	foreach ($arrayofselected as $inv_selected)
	{

		
		$invuser->load($inv_selected);
		
		$subject = $langs->transnoentitiesnoconv('MailSubjQuest',$object->ref);
		
		$content = prepareMailContent($invuser,$id);
		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		
		$mailfile = new CMailFile($subject, $invuser->email, $conf->email_from, $content);
		if (!$mailfile->sendfile())
		{
			setEventMessages($langs->transnoentities($langs->trans("ErrorFailedToSendMail", $conf->email_from,  $invuser->email).'. '.$mailfile->error), null, 'errors');
		}
		else
		{
			$invuser->sent = 1;
			$invuser->update($user);
			setEventMessages($langs->trans("MailSuccessfulySent", $conf->email_from,  $invuser->email), null, 'mesgs');
		}
	}
}elseif($massaction == 'delete' && !empty($arrayofselected)){

	foreach ($arrayofselected as $inv_selected)
	{
		$invitation = new InvitationUser($db);
		$invitation->load($inv_selected);
		$invitation->delete($user);
		$object->deleteAllAnswersUser($inv_selected);
		
	}
}
// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	$error = 0;
	switch ($action) {

		case 'edit':

			$invitation = new InvitationUser($db);
			$invitation->load($fk_invitation);
			$emails = $invitation->email;
			
			break;

		case 'save':

			// Enregistrement des données dans les tables invitation et invitation_user
			$invitation = new InvitationUser($db);
			$invitation->fk_questionnaire = $object->id;
			$invitation->date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
			if(!empty($fk_invitation)){
				$invitation->load($fk_invitation);
				$invitation->date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
				if(!empty($emails))$invitation->email = $emails;
				$invitation->save();
			}else {
				$invitation->addInvitationsUser($groups, $users, $emails,$object->id,strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day));
			}
			
			$mode = 'view';
			break;

	
		case 'settitle':
			$object->title = $title;
			$object->save();
			
			header('Location: '.dol_buildpath('/questionnaire/invitation.php', 1).'?id='.$object->id);
			exit;
			break;
	}
}

llxHeader();
$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'invitation', $langs->trans("questionnaire"), 0, $picto, 1);

_getBanner($object, $action, false);

$TBS = new TTemplateTBS();
$TBS->TBS->protect = false;
$TBS->TBS->noerr = true;

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);
if ($mode == 'edit')
	echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');
$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">'.$langs->trans("BackToList").'</a>';


//if()



print $TBS->render('tpl/invitation.tpl.php'
		, array() // Block
		, array(
		'object' => $object
		, 'view' => array(
			'mode' => $mode
			,'act'=>$action
			, 'action' => 'save'
			, 'urlinvitation' => dol_buildpath('/questionnaire/invitation.php', 1)
			, 'urladvselecttarget' => dol_buildpath('/questionnaire/advselecttarget.php', 1)
			, 'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			, 'showRef' => $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
			, 'showTitle' => $object->title
			, 'showStatus' => $object->getLibStatut(1)
			, 'list_invitations' => _getListInvitations($object)
			, 'massaction' => printMassActionButton()
			,'fk_user' => $invitation->fk_element
		)
		, 'langs' => $langs
		, 'user' => $user
		, 'conf' => $conf
		, 'form' => array(
			'select_usergroups' => $form->multiselectarray('usergroups', _getUserGroups(), $invitations_usergroups, '', 0, '', 0, '100%')
			, 'select_users' => $form->multiselectarray('users', _getUsers(), $invitations_users, '', 0, '', 0, '100%')
			, 'date_limite' => $form->select_date($action === 'create' ? dol_now() : $invitation->date_limite_reponse, 'date_limite', 0, 0, 0, '', 1, 0, 1)
			, 'fk_invitation' => $fk_invitation
			, 'emails' => $emails
		)
		, 'Questionnaire' => array(
			'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
			, 'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
			, 'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
		)
		)
);

function _getListInvitations(&$object)
{

	global $db, $langs, $hookmanager, $user, $form, $formcore;

	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

	$r = new TListviewTBS('invitation_list', dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));

	$sql = 'SELECT invu.fk_usergroup,COALESCE(NULLIF(invu.type_element,""), "External") as type_element, invu.fk_element, invu.email, invu.date_limite_reponse, invu.sent, invu.rowid as id_user, \'\' AS action';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'quest_invitation_user invu ';
	$sql .= ' WHERE fk_questionnaire = '.$object->id;
	$sql .= ' AND (invu.fk_element > 0 OR invu.email != "") ';
	$resql = $db->query($sql);
	
	$TData = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
		{
			$TData[] = $res;
		}
	}

//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
	

	$res = $r->renderArray($db, $TData, array(
		'limit' => array(
			'page' => 1
			, 'nbLine' => 500
		)
		, 'translate' => array(
		)
		, 'link' => array(
		)
		, 'hide' => array('id_user','type_element')
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
			 'date_limite_reponse' => $langs->trans('questionnaire_date_limite_reponse')
			, 'sent' => $langs->trans('Status')
			, 'email' => $langs->trans('Email')
			, 'fk_element' => $langs->trans('Element')
			, 'action' => $langs->trans('Action').'&nbsp;&nbsp;&nbsp;'.$form->showCheckAddButtons('checkforselect', 1)
			, 'fk_usergroup' => $langs->trans('Group')
		)
		, 'orderBy' => array('cn.rowid' => 'DESC')
		, 'eval' => array(
			'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
			, 'fk_element' => '_getNomUrl(@fk_element@,Externe,@type_element@)'
			, 'sent' => '_libStatut(@sent@)'
			, 'action' => '_actionLink(@id_user@)'
			, 'fk_usergroup' => '_getNomUrlGrp(@fk_usergroup@)'
		)
	));


	$parameters = array('sql' => $sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object);	// Note that $action and $object may have been modified by hook
	$res .= $hookmanager->resPrint;
	
	$res .= $formcore->end_form();
	return $res;
}

function _getUsers()
{

	global $db;

	$sql = 'SELECT rowid, lastname, firstname
			FROM '.MAIN_DB_PREFIX.'user
			WHERE statut = 1';

	$resql = $db->query($sql);
	$TRes = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
			$TRes[$res->rowid] = $res->lastname.' '.$res->firstname;
	}

	return $TRes;
}

function _getUserGroups()
{

	global $db;

	$sql = 'SELECT rowid, nom
			FROM '.MAIN_DB_PREFIX.'usergroup';

	$resql = $db->query($sql);
	$TRes = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
			$TRes[$res->rowid] = $res->nom;
	}

	return $TRes;
}

function _getNomUrl($fk_element, $email,$type_element)
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
function _getNomUrlGrp($fk_usergroup)
{

	global $db;

	$u = new UserGroup($db);
	$u->fetch($fk_usergroup);
	if (!empty($fk_usergroup))
		$res = $u->nom;
	else
		$res = 'Non';
	return $res;
}

function _libStatut($status)
{

	return InvitationUser::LibStatut($status, 6);
}

function _actionLink(  $fk_invit)
{
	global $object, $massactionbutton, $massaction, $arrayofselected;
	$link = '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation='.$fk_invit.'&action=edit">'.img_edit().'</a>'; //<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation=@rowid@&action=delete_invitation">'.img_delete().'</a>'

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
	
//	if ($massaction == 'predelete')
//	{
//
//		$ret .=  $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500,1);
//	}

	$arrayofmassactions = array(
		'send' => $langs->trans("SendByMail"),
    'delete'=>$langs->trans("Delete"),
	);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$ret .= '<td class="nobordernopadding center valignmiddle">'.$massactionbutton.'</td>';
	return $ret;
}

llxFooter();
