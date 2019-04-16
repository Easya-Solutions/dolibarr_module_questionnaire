<?php

require 'config.php';
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

//if(empty($user->rights->questionnaire->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('questionnaire@questionnaire');

$PDOdb = new TPDOdb;
$object = new Questionnaire($db);
$action = GETPOST('action');
$status = GETPOST('status');

$hookmanager->initHooks(array('questionnairelist'));

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// do action from GETPOST ... 
}


/*
 * View
 */

llxHeader('',$langs->trans($action === 'to_answer' ? 'QuestionnaireToAnswerArea' : 'questionnaireList'),'','');

print load_fiche_titre($langs->trans($action === 'to_answer' ? 'QuestionnaireToAnswerArea' : 'questionnaireList'),'',dol_buildpath('/questionnaire/img/questionnaire.png', 1), 1);

$sql = 'SELECT t.rowid, t.title, t.fk_statut, \'\' AS action';
$sql.= ' FROM '.MAIN_DB_PREFIX.'quest_questionnaire t ';
$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('questionnaire', 1).')';
$sql.= ' ORDER BY t.ref DESC';

if(is_numeric($status)) $sql.=' AND t.fk_statut='.$status;
if(empty($user->rights->questionnaire->readall)) $sql.= ' AND fk_user_author = '.$user->id;

// Peu importe les droits, on ne peut répondre qu'aux questionnaires auxquels on est invité à répondre
if($action === 'to_answer') {
	$sql = 'SELECT DISTINCT  i_usr.rowid as id_usrinvit, q.rowid, q.title, i_usr.date_limite_reponse
			FROM '.MAIN_DB_PREFIX.'quest_questionnaire q
			INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation_user i_usr ON (i_usr.fk_questionnaire = q.rowid)
			WHERE i_usr.fk_element = '.$user->id.' AND type_element="user" 
			AND i_usr.fk_statut != 1
			AND i_usr.date_limite_reponse >= "'.date('Y-m-d').'"
			ORDER BY q.ref DESC';
}
$resql = $db->query($sql);
$TData=array();
if(!empty($resql) && $db->num_rows($resql) > 0) {
	while($res = $db->fetch_object($resql)) {
		/*$obj_linked = _showLinkedObject($res->origin, $res->originid, false, false);
		unset($res->originid); // Pour n'afficher qu'une seule colonne
		$res->origin = $obj_linked;*/
		$TData[] = $res;
	}
}

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_questionnaire', 'GET');

$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

$r = new TListviewTBS('questionnaire_list', dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));

print $r->renderArray($db, $TData, array(
		'limit'=>array(
				'page'=>1
				,'nbLine'=>'20'
		)
		,'translate'=>array(
				
		)
		,'link'=>array(
		)
		,'hide'=>array(
				'id_invitation','id_usrinvit'
		)
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
				,'title'=>$langs->trans('Title')
				,'fk_statut'=>$langs->trans('Status')
				,'date_limite_reponse'=>$langs->trans('questionnaire_date_limite_reponse')
				,'origin' => $langs->trans('LinkedObject')
		)
		,'orderBy'=> array('cn.rowid' => 'DESC')
		,'eval'=>array(
				'rowid'=>'_getQuestionnaireLink(@rowid@, "'.$action.'"'.($action === 'to_answer' ? ', @id_usrinvit@' : '').')'
				,'fk_statut'=>'_getLibStatus(@rowid@, @fk_statut@)'
				,'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
		)
));


$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
