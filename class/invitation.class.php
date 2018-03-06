<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class Invitation extends SeedObject {
	
	public $table_element = 'invitation';
	
	public $element = 'invitation';
	
	public function __construct($db)
	{
		global $conf,$langs;
		
		$this->db = $db;
		
		$this->fields=array(
				'token'=>array('type'=>'string')
				,'date_limite_reponse'=>array('type'=>'date')
				,'fk_user'=>array('type'=>'integer','index'=>true)
				,'fk_status'=>array('type'=>'integer','index'=>true) // Indique si l'utilisateur a enregistré ses données pour terminer plus tard, ou s'il a terminé et validé son questionnaire
		);
		
		$this->init();
		
		$this->entity = $conf->entity;
	}
	
	public function load($id, $ref, $loadChild = true)
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
	
}