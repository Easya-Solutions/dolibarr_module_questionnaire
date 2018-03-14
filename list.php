<?php

require 'config.php';
dol_include_once('/questionnaire/class/questionnaire.class.php');

//if(empty($user->rights->questionnaire->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('questionnaire@questionnaire');

$PDOdb = new TPDOdb;
$object = new Questionnaire($db);

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

llxHeader('',$langs->trans('questionnaireList'),'','');

print load_fiche_titre($langs->trans("QuestionnaireArea"),'',dol_buildpath('/questionnaire/img/questionnaire.png', 1), 1);
//print_barre_liste($langs->trans("QuestionnaireArea"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, dol_buildpath('/questionnaire/img/questionnaire.png'), 1, '', '', $limit);

//$type = GETPOST('type');
//if (empty($user->rights->questionnaire->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
//$sql = 'SELECT t.rowid, t.ref, t.title, t.date_creation, t.tms, \'\' AS action';
$sql = 'SELECT t.rowid, t.title, t.fk_statut, \'\' AS action';
$sql.= ' FROM '.MAIN_DB_PREFIX.'quest_questionnaire t ';
$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('questionnaire', 1).')';

$resql = $db->query($sql);
$TData=array();
if(!empty($resql) && $db->num_rows($resql) > 0) {
	while($res = $db->fetch_object($resql)) {
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
				,'title'=>$langs->trans('Title')
				,'fk_statut'=>$langs->trans('Status')
		)
		,'orderBy'=> array('cn.rowid' => 'DESC')
		,'eval'=>array(
				'rowid'=>'_getQuestionnaireNomUrl(@rowid@)'
				,'fk_statut'=>'_getLibStatus(@rowid@, @fk_statut@)'
		)
));


$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');

function _getQuestionnaireNomUrl($fk_questionnaire)
{
	global $db;
	
	$q = new Questionnaire($db);
	if ($q->fetch($fk_questionnaire) > 0) return $q->getNomUrl();
	
	return '';
}

function _getLibStatus($fk_questionnaire, $fk_statut)
{
	global $db;
	
	$q = new Questionnaire($db);
	if ($q->fetch($fk_questionnaire) > 0) return $q->LibStatut($fk_statut, 1);
	
	return '';
}
