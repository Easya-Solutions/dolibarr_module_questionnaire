<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class Question extends SeedObject {
	
	public $table_element = 'quest_question';
	
	public $element = 'question';
	
	public function __construct(&$db)
	{
		global $conf, $langs;
		
		$this->db = $db;
		
		$this->fields=array(
				'label'=>array('type'=>'text')
				,'type'=>array('type'=>'string')
				,'is_section'=>array('type'=>'integer') // groupement de questions (titre), pas de choix donc pas de réponse
				,'fk_questionnaire'=>array('type'=>'integer','index'=>true)
				,'compulsory_answer'=>array('type'=>'integer') // Rép. obligatoire ou non
				,'originid'=>array('type'=>'integer')
				,'origin'=>array('type'=>'string')
				,'rang'=>array('type'=>'integer')

		);
		
		$this->TTypes = array(
				'string' => $langs->trans('questionnaireTypeString')
				,'textarea' => $langs->trans('questionnaireTypeTextArea')
				,'select' => $langs->trans('questionnaireTypeSelect')
				,'listradio' => $langs->trans('questionnaireTypeRadio')
				,'listcheckbox' => $langs->trans('questionnaireTypeCheckbox')
				,'grilleradio' => $langs->trans('questionnaireTypeGrilleRadio')
				,'grillecheckbox' => $langs->trans('questionnaireTypeGrilleCheckbox')
				,'grillestring' => $langs->trans('questionnaireTypeGrilleString')
				,'date' => $langs->trans('questionnaireTypeDate')
				,'hour' => $langs->trans('questionnaireTypeHour')
				,'linearscale' => $langs->trans('questionnaireTypeLinearScale')
				,'page' => $langs->trans('questionnaireTypePage')
				,'title' => $langs->trans('questionnaireTypeTitle')
				,'separator' => $langs->trans('questionnaireTypeSeparator')
				,'paragraph' => $langs->trans('questionnaireTypeParagraph')
		);
		
		$this->init();
		
		$this->entity = $conf->entity;
	}
	
	public function load($id, $ref=null, $loadChild = true)
	{
		global $db;
		
		$res = parent::fetchCommon($id, $ref);
		
		if ($loadChild) $this->fetchObjectLinked();
		
		return $res;
	}
	
	public function save($dontdelete=0) {
		
		global $user;
		if(empty($dontdelete))$this->deleteLinkedAnswers($user);
		return $this->id>0 ? $this->updateCommon($user) : $this->createCommon($user);
		
	}
	
	function loadChoices() {
		
		global $db;
		
		dol_include_once('/questionnaire/class/choice.class.php');
		
		$choice = new Choice($db);
		
		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.$choice->table_element.'
				WHERE fk_question = '.$this->id;
		$resql = $db->query($sql);
		if(!empty($resql) && $db->num_rows($resql) > 0) {
			$this->choices = array();
			
			while($res = $db->fetch_object($resql)) {
				$choice = new Choice($db);
				$choice->load($res->rowid);
				$this->choices[] = $choice;
				if($choice->type == 'line')$this->choices_line[]=$choice;
				if($choice->type == 'column')$this->choices_column[]=$choice;
			}
			
		} else return 0;
		
		return 1;
		
	}
	
	function getGrilleTitle(){
		if(!empty($this->choices)) {
				foreach($this->choices as &$choice) {
					if($choice->type == 'titleline') return $choice->label;
				}
			}
			return '';
	}
	
	function loadAnswers($fk_invitation_user=null) {
		
		global $db;
		
		dol_include_once('/questionnaire/class/answer.class.php');
		
		$answer = new Answer($db);
		
		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.$answer->table_element.'
				WHERE fk_question = '.$this->id;
		if(!empty($fk_invitation_user)) $sql.= ' AND fk_invitation_user = '.$fk_invitation_user;
		
		$resql = $db->query($sql);
		if(!empty($resql) && $db->num_rows($resql) > 0) {
			$this->answers = array();
			
			while($res = $db->fetch_object($resql)) {
				$answer = new Answer($db);
				$answer->load($res->rowid);
				$this->answers[] = $answer;
			}
		} else return 0;
		
		return 1;
		
	}
	
	public function delete(User &$user)
	{
		
		if(empty($this->choices)) $this->loadChoices();
		if(!empty($this->choices)) {
			foreach($this->choices as &$choice) $choice->delete($user);
		}
		$this->decrementAllRank();
		$this->deleteLinkedAnswers($user);
		
		return parent::deleteCommon($user);
	}
	
	function deleteAllAnswersUser($fk_invitation_user) {
		
		global $db, $user;
		
		dol_include_once('/questionnaire/class/answer.class.php');
		
		$obj = new Answer($db);
		
		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.$obj->table_element.'
				WHERE fk_question = '.$this->id.'
				AND fk_invitation_user = '.$fk_invitation_user;
		$resql = $db->query($sql);
		if(!empty($resql) && $db->num_rows($resql) > 0) {
			while($res = $db->fetch_object($resql)) {
				$obj = new Answer($db);
				$obj->load($res->rowid);
				$obj->delete($user);
			}
		}
		
	}
	
	function deleteLinkedAnswers($user){
		if(empty($this->answers)) $this->loadAnswers();
		if(!empty($this->answers)) {
			foreach($this->answers as &$answer) $answer->delete($user);
		}
	}
	
	function incrementAllRank(){
		global $db;
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET rang = (rang+1) WHERE fk_questionnaire = $this->fk_questionnaire AND rang >= $this->rang";
		$db->query($sql);
		
	}
	function decrementAllRank(){
		global $db;
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET rang = (rang-1) WHERE fk_questionnaire = $this->fk_questionnaire AND rang >= $this->rang";
		$db->query($sql);
		
	}
	
}
