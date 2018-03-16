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
				'label'=>array('type'=>'string')
				,'type'=>array('type'=>'string')
				,'is_section'=>array('type'=>'integer') // groupement de questions (titre), pas de choix donc pas de réponse
				,'fk_questionnaire'=>array('type'=>'integer','index'=>true)
				,'compulsory_answer'=>array('type'=>'integer') // Rép. obligatoire ou non
				,'fk_object_linked'=>array('type'=>'integer')
				,'type_object_linked'=>array('type'=>'integer')
		);
		
		$this->TTypes = array(
				'string' => $langs->trans('questionnaireTypeString')
				,'textarea' => $langs->trans('questionnaireTypeTextArea')
				,'select' => $langs->trans('questionnaireTypeSelect')
				,'listradio' => $langs->trans('questionnaireTypeRadio')
				,'listcheckbox' => $langs->trans('questionnaireTypeCheckbox')
				,'grilleradio' => $langs->trans('questionnaireTypeGrilleRadio')
				,'grillecheckbox' => $langs->trans('questionnaireTypeGrilleCheckbox')
				,'date' => $langs->trans('questionnaireTypeDate')
				,'hour' => $langs->trans('questionnaireTypeHour')
				,'linearscale' => $langs->trans('questionnaireTypeLinearScale')
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
	
	public function save() {
		
		global $user;
		
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
			}
			
		} else return 0;
		
		return 1;
		
	}
	
	function loadAnswers($fk_user=null) {
		
		global $db;
		
		dol_include_once('/questionnaire/class/answer.class.php');
		
		$answer = new Answer($db);
		
		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.$answer->table_element.'
				WHERE fk_question = '.$this->id;
		if(!empty($fk_user)) $sql.= ' AND fk_user = '.$fk_user;
		
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
	
	public function delete()
	{
		global $user;
		
		if(empty($this->choices)) $this->loadChoices();
		if(!empty($this->choices)) {
			foreach($this->choices as &$choice) $choice->delete();
		}
		
		if(empty($this->answers)) $this->loadAnswers();
		if(!empty($this->answers)) {
			foreach($this->answers as &$answer) $answer->delete();
		}
		
		return parent::deleteCommon($user);
	}
	
}