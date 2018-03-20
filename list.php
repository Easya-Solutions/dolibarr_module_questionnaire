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
//print_barre_liste($langs->trans("QuestionnaireArea"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, dol_buildpath('/questionnaire/img/questionnaire.png'), 1, '', '', $limit);

//$type = GETPOST('type');
//if (empty($user->rights->questionnaire->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
//$sql = 'SELECT t.rowid, t.ref, t.title, t.date_creation, t.tms, \'\' AS action';
$sql = 'SELECT t.rowid, t.title, t.origin, t.originid, t.fk_statut, \'\' AS action';
$sql.= ' FROM '.MAIN_DB_PREFIX.'quest_questionnaire t ';
$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('questionnaire', 1).')';

if($action === 'to_answer') {
	$sql = 'SELECT DISTINCT i.rowid as id_invitation, q.rowid, q.title, i.date_limite_reponse
			FROM '.MAIN_DB_PREFIX.'quest_questionnaire q
			INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation i ON (i.fk_questionnaire = q.rowid)
			INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation_user i_usr ON (i_usr.fk_invitation = i.rowid)
			WHERE i_usr.fk_user = '.$user->id.'
			AND i_usr.fk_statut = 0
			AND i.date_limite_reponse >= "'.date('Y-m-d').'"';
}

$resql = $db->query($sql);
$TData=array();
if(!empty($resql) && $db->num_rows($resql) > 0) {
	while($res = $db->fetch_object($resql)) {
		$obj_linked = _showLinkedObject($res->origin, $res->originid, false, false);
		unset($res->originid); // Pour n'afficher qu'une seule colonne
		$res->origin = $obj_linked;
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
				'id_invitation'
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
				'rowid'=>'_getQuestionnaireLink(@rowid@, "'.$action.'"'.($action === 'to_answer' ? ', @id_invitation@' : '').')'
				,'fk_statut'=>'_getLibStatus(@rowid@, @fk_statut@)'
				,'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
		)
));


$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');

function _getQuestionnaireLink($fk_questionnaire, $action, $fk_invitation='')
{
	global $db;
	
	$q = new Questionnaire($db);
	$more_param = '';
	if($action === 'to_answer') $more_param.= '&action=answer';
	if(!empty($fk_invitation)) $more_param.= '&fk_invitation='.$fk_invitation;
	if ($q->fetch($fk_questionnaire) > 0) return $q->getNomUrl(0, $more_param);
	
	return '';
}

function _getLibStatus($fk_questionnaire, $fk_statut)
{
	global $db;
	
	$q = new Questionnaire($db);
	if ($q->fetch($fk_questionnaire) > 0) return $q->LibStatut($fk_statut, 1);
	
	return '';
}
