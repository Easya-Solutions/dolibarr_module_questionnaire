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
$fk_user = GETPOST('fk_user');

$mode = 'view';
if ($action == 'create' || $action == 'edit') $mode = 'edit';

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
dol_fiche_head($head, 'answer', $langs->trans("questionnaire"), 0, $picto, 1);

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/answer.tpl.php'
		,array() // Block
		,array(
				'object'=>$object
				,'view' => array(
						'mode' => $mode
						,'action' => $action
						,'urlinvitation' => dol_buildpath('/questionnaire/invitation.php', 1)
						,'urllist' => dol_buildpath('/questionnaire/list.php', 1)
						,'showRef' => $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
						,'showTitle' => $object->title
						,'showStatus' => $object->getLibStatut(1)
						,'list_answers' => _getListAnswers($object)
						,'user_answers' => _seeAnswersUser($object, $fk_user)
				)
				,'langs' => $langs
				,'user' => $user
				,'conf' => $conf
				,'form' => array(
				)
				,'Questionnaire' => array(
						'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
						,'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
						,'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
				)
		)
		);

function _getListAnswers(&$object) {
	
	global $db, $langs, $hookmanager, $user;
	
	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;
	
	$r = new TListviewTBS('invitation_list', dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));
	
	// On regarde s'il existe une réponse à au moins une question du questionnaire sur lequel on se trouve
	// Subquery pour chercher s'il existe une réponse validée
	$sql = 'SELECT DISTINCT iu.fk_user, (SELECT iu2.fk_statut FROM '.MAIN_DB_PREFIX.'quest_invitation i2 INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation_user iu2 ON (i2.rowid = iu2.fk_invitation) WHERE i2.fk_questionnaire = i.fk_questionnaire AND iu2.fk_user = iu.fk_user ORDER BY iu2.rowid DESC LIMIT 1) as fk_statut
			FROM '.MAIN_DB_PREFIX.'quest_invitation i
			INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation_user iu  ON (iu.fk_invitation = i.rowid)
			WHERE i.fk_questionnaire = '.$object->id.'
			AND iu.fk_user > 0
			GROUP BY iu.fk_user';
	//echo $sql;exit;
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
					'fk_user'=>$langs->trans('User')
					,'fk_statut'=>$langs->trans('questionnaireAnswerStatus')
			)
			,'orderBy'=> array('cn.rowid' => 'DESC')
			,'eval'=>array(
					'fk_user' => '_getLinkAnswersUser("@fk_user@")'
					,'fk_statut' => '_libStatut("@fk_statut@", 1)'
			)
	));
	
	
	$parameters=array('sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
	$res.= $hookmanager->resPrint;
	
	return $res;
	
}

function _getLinkAnswersUser($fk_user) {
	
	global $db, $id;
	
	$sql = 'SELECT lastname, firstname
			FROM '.MAIN_DB_PREFIX.'user
			WHERE rowid = '.$fk_user;
	$resql = $db->query($sql);
	if(!empty($resql) && $db->num_rows($resql)) {
		$res = $db->fetch_object($resql);
		return '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=view_answer&fk_user='.$fk_user.'">'.$res->lastname.' '.$res->firstname.'</a>';
	}
		
	return 'NOUSER';
	
}

function _seeAnswersUser(&$object, $fk_user) {
	
	global $db, $langs;
	
	require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
	
	$u = new User($db);
	$u->fetch($fk_user);
	
	$res = $langs->trans('questionnaireUserAnswersOf', $u->getNomUrl(1)).'<br /><br />';
	
	if(empty($object->questions)) $object->loadQuestions();
	$res.= '<div id="allQuestions">';
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) {
			if(empty($q->answers)) $q->loadAnswers($fk_user);
			$res.= draw_question_for_user($q).'<br />';
		}
	}
	$res.= '</div>';
	
	return $res;
	
}

function _libStatut($status, $mode) {
	
	global $langs;
	$langs->load('questionnaire@questionnaire');
	
	if ($status==0) { $statustrans='statut0'; $keytrans='questionnaireStatusOpened'; $shortkeytrans='Draft'; }
	if ($status==1) { $statustrans='statut1'; $keytrans='questionnaireStatusValidated'; $shortkeytrans='Validate'; }
	
	
	if ($mode == 0) return img_picto($langs->trans($keytrans), $statustrans);
	elseif ($mode == 1) return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($keytrans);
	elseif ($mode == 2) return $langs->trans($keytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
	elseif ($mode == 3) return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($shortkeytrans);
	elseif ($mode == 4) return $langs->trans($shortkeytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
	
}