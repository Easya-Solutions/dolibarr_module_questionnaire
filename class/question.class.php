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
	
	public $table_element = 'question';
	
	public $element = 'question';
	
	public function __construct(&$db)
	{
		global $conf;
		
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
		
		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.'choice
				WHERE fk_question = '.$this->id;
		$resql = $db->query($sql);
		if(!empty($resql) && $db->num_rows($resql) > 0) {
			$this->questions = array();
			dol_include_once('/questionnaire/class/question.class.php');
			while($res = $db->fetch_object($resql)) {
				$choice = new Choice($db);
				$choice->load($res->rowid);
				$this->choices[] = $choice;
			}
			
		} else return 0;
		
		return 1;
		
	}
	
}