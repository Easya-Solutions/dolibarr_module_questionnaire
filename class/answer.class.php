<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class Answer extends SeedObject {
	
	public $table_element = 'quest_answer';
	
	public $element = 'answer';
	
	public function __construct($db)
	{
		global $conf;
		
		$this->db = $db;
		
		$this->fields=array(
				'fk_question'=>array('type'=>'integer','index'=>true)
				,'fk_choix'=>array('type'=>'integer')
				,'fk_choix_col'=>array('type'=>'integer')
				,'fk_invitation_user'=>array('type'=>'integer')
				,'value'=>array('type'=>'text') // for types string or textarea etc...
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
	
	public function delete(User &$user) {
		return parent::deleteCommon($user);
	}
	
}