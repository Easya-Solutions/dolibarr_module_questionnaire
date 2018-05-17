<?php

require 'config.php';
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');

llxHeader();

$langs->load('questionnaire@questionnaire');

print_fiche_titre($langs->trans('QuestionnaireAreaIndex'), '', 'questionnaire@questionnaire', 0);


// Les 5 derniers questionanires créés
$sql = 'SELECT t.rowid, t.title, t.fk_statut';
$sql.= ' FROM '.MAIN_DB_PREFIX.'quest_questionnaire t ';
$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('questionnaire', 1).')';
if(empty($user->rights->questionnaire->readall)) $sql.= ' AND fk_user_author = '.$user->id;
$sql.= ' ORDER BY rowid DESC LIMIT 5';

print '<div class"fichecenter">';
print '<div class="fichethirdleft">';
print_titre($langs->trans('QuestionnaireLastCreated', !empty($user->rights->questionnaire->readall) ? $langs->trans('QuestionnaireLastCreatedMore') : ''));
_printArrayQuestionnaires($sql);
print '</div>';


// Les 5 derniers questionnaires à compléter. Peu importe les droits, on ne peut répondre qu'aux questionnaires auxquels on est invité à répondre
$sql = 'SELECT DISTINCT i.rowid as id_invitation, i_usr.rowid as id_usrinvit, q.rowid, q.title, i.date_limite_reponse
FROM '.MAIN_DB_PREFIX.'quest_questionnaire q
INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation i ON (i.fk_questionnaire = q.rowid)
INNER JOIN '.MAIN_DB_PREFIX.'quest_invitation_user i_usr ON (i_usr.fk_invitation = i.rowid)
WHERE i_usr.fk_user = '.$user->id.'
AND i_usr.fk_statut = 0
AND i.date_limite_reponse >= "'.date('Y-m-d').'"
ORDER BY i.rowid DESC
LIMIT 5';

print '<div class="fichetwothirdright">';
print '<div class="ficheaddleft">';
print_titre($langs->trans('QuestionnaireLastToAnswer'));
_printArrayQuestionnaires($sql, 'to_answer');
print '</div>';
print '</div>';
print '</div>';

function _printArrayQuestionnaires($sql, $list_name='questionnaire_list') {
	
	global $db, $langs;
	
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
	
	$r = new TListviewTBS($list_name, dol_buildpath('/questionnaire/tpl/questionnaire_list.tpl.php'));
	
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
					'rowid'=>'_getQuestionnaireLink(@rowid@, "'.$list_name.'"'.($list_name === 'to_answer' ? ', @id_invitation@' : '').', "&fk_userinvit=@id_usrinvit@")'
					,'fk_statut'=>'_getLibStatus(@rowid@, @fk_statut@)'
					,'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
			)
	));
	
}