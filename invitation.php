<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_invitation = GETPOST('fk_invitation');
$groups = GETPOST('usergroups');
$users = GETPOST('users');
$date_limite_year = GETPOST('date_limiteyear');
$date_limite_month = GETPOST('date_limitemonth');
$date_limite_day = GETPOST('date_limiteday');

$mode = 'view';
if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Questionnaire($db);
$form = new Form($db);

if (!empty($id)) $object->load($id);
elseif (!empty($ref)) $object->load('', $ref);

if(empty($object->fk_statut)) {
	header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
	exit;
}

$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook)) {
	
	$error = 0;
	switch ($action) {
		
		case 'edit':
			
			$invitation = new Invitation($db);
			$invitation->load($fk_invitation);
			$invitation->loadInvitationsUser();
			$invitations_usergroups=$invitations_users=array();
			if(!empty($invitation->invitations_user)) {
				foreach($invitation->invitations_user as &$inv_usr) {
					if(!empty($inv_usr->fk_user)) $invitations_users[] = $inv_usr->fk_user;
					if(!empty($inv_usr->fk_usergroup)) $invitations_usergroups[] = $inv_usr->fk_usergroup;
				}
			}
			break;
			
		case 'save':
			
			// Enregistrement des données dans les tables invitation et invitation_user
			$invitation = new Invitation($db);
			$invitation->load($fk_invitation);
			$invitation->fk_questionnaire = $object->id;
			$invitation->date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
			$invitation->save();
			$invitation->delAllInvitationsUser();
			$invitation->addInvitationsUser($groups, $users);
			
			$mode = 'view';
			break;
			
		case 'delete_invitation':
			
			$invitation = new Invitation($db);
			$invitation->load($fk_invitation);
			$invitation->delete();
			
			break;
			
	}
	
}

llxHeader();
$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'invitation', $langs->trans("questionnaire"), 0, $picto, 1);

_getBanner($object, $action, false);

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
						,'urlinvitation' => dol_buildpath('/questionnaire/invitation.php', 1)
						,'urllist' => dol_buildpath('/questionnaire/list.php', 1)
						,'showRef' => $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
						,'showTitle' => $object->title
						,'showStatus' => $object->getLibStatut(1)
						,'list_invitations' => _getListInvitations($object)
				)
				,'langs' => $langs
				,'user' => $user
				,'conf' => $conf
				,'form' => array(
						'select_usergroups' => $form->multiselectarray('usergroups', _getUserGroups(), $invitations_usergroups, '', 0, '', 0, '100%')
						,'select_users' => $form->multiselectarray('users', _getUsers(), $invitations_users, '', 0, '', 0, '100%')
						,'date_limite' => $form->select_date($action === 'create' ? dol_now() : $invitation->date_limite_reponse, 'date_limite', 0, 0, 0, '', 1, 0, 1)
						,'fk_invitation' => $fk_invitation
				)
				,'Questionnaire' => array(
						'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
						,'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
						,'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
				)
		)
		);

function _getListInvitations(&$object) {
	
	global $db, $langs, $hookmanager, $user;
	
	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;
	
	$r = new TListviewTBS('invitation_list', dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));
	
	$sql = 'SELECT t.rowid, t.date_limite_reponse, \'\' AS action';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'quest_invitation t ';
	$sql.= ' WHERE fk_questionnaire = '.$object->id;
	
	$resql = $db->query($sql);
	$TData=array();
	if(!empty($resql) && $db->num_rows($resql) > 0) {
		while($res = $db->fetch_object($resql)) {
			$TData[] = $res;
		}
	}
	
	$res = $r->renderArray($db, $TData, array(
			'limit'=>array(
					'page'=>1
					,'nbLine'=>'20'
			)
			,'translate'=>array(
					
			)
			,'link'=>array(
					'action' => '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation=@rowid@&action=edit">'.img_edit().'</a><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation=@rowid@&action=delete_invitation">'.img_delete().'</a>'
			)
			//,'hide'=>$THide
			,'type'=>array()
			,'liste'=>array(
					'titre'=>$langs->trans('TitleConformiteNormeList')
					,'image'=>img_picto('','title.png', '', 0)
					,'picto_precedent'=>img_picto('','previous.png', '', 0)
					,'picto_suivant'=>img_picto('','next.png', '', 0)
					,'order_down'=>img_picto('','1downarrow.png', '', 0)
					,'order_up'=>img_picto('','1uparrow.png', '', 0)
					,'noheader'=>FALSE
					,'messageNothing'=>$langs->transnoentities('noElement')
					,'picto_search'=>img_picto('','search.png', '', 0)
			)
			,'title'=>array(
					'rowid'=>$langs->trans('Ref')
					,'date_limite_reponse'=>$langs->trans('questionnaire_date_limite_reponse')
					,'fk_statut'=>$langs->trans('Status')
			)
			,'orderBy'=> array('cn.rowid' => 'DESC')
			,'eval'=>array(
					'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
			)
	));
	
	
	$parameters=array('sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
	$res.= $hookmanager->resPrint;
	
	return $res;
	
}

function _getUsers() {
	
	global $db;
	
	$sql = 'SELECT rowid, lastname, firstname
			FROM '.MAIN_DB_PREFIX.'user
			WHERE statut = 1';
	
	$resql = $db->query($sql);
	$TRes = array();
	if(!empty($resql) && $db->num_rows($resql) > 0) {
		while($res = $db->fetch_object($resql)) $TRes[$res->rowid] = $res->lastname.' '.$res->firstname;
	}
	
	return $TRes;
	
}

function _getUserGroups() {

	global $db;
	
	$sql = 'SELECT rowid, nom
			FROM '.MAIN_DB_PREFIX.'usergroup';
	
	$resql = $db->query($sql);
	$TRes = array();
	if(!empty($resql) && $db->num_rows($resql) > 0) {
		while($res = $db->fetch_object($resql)) $TRes[$res->rowid] = $res->nom;
	}
	
	return $TRes;
	
}
