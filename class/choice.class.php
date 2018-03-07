<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class Choice extends SeedObject {
	
	public $table_element = 'choice';
	
	public $element = 'choice';
	
	public function __construct($db)
	{
		global $conf, $langs;
		
		$this->db = $db;
		
		$this->fields=array(
				'label'=>array('type'=>'string')
				,'type'=>array('type'=>'string')
				,'fk_question'=>array('type'=>'integer','index'=>true)
		);
		
		$this->init();
		
		$this->entity = $conf->entity;
		
		$langs->load('questionnaire@questionnaire');
		
		$this->TTypes = array(
				'string' => $langs->trans('questionnaireTypeString')
				,'textarea' => $langs->trans('questionnaireTypeTextArea')
				,'select' => $langs->trans('questionnaireTypeSelect')
				,'listradio' => $langs->trans('questionnaireTypeRadio')
				,'listcheckbox' => $langs->trans('questionnaireTypeCheckbox')
				,'grilleradioline' => $langs->trans('questionnaireTypeGrilleRadioLine')
				,'grilleradiocol' => $langs->trans('questionnaireTypeGrilleRadioCol')
				,'grillecheckboxline' => $langs->trans('questionnaireTypeGrilleCheckboxLine')
				,'grillecheckboxcol' => $langs->trans('questionnaireTypeGrilleCheckboxCol')
				,'date' => $langs->trans('questionnaireTypeDate')
				,'hour' => $langs->trans('questionnaireTypeHour')
				,'linearscale' => $langs->trans('questionnaireTypeLinearScale')
		);
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
	
	public function delete()
	{
		global $user;
		
		return parent::deleteCommon($user);
	}
	
}