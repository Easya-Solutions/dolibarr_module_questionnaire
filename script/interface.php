<?php

require('../config.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

$get = GETPOST('get');
$put = GETPOST('put');

$fk_questionnaire = GETPOST('fk_questionnaire');
$fk_question = GETPOST('fk_question');
$is_section = GETPOST('is_section');
$type_object = GETPOST('type_object');
$type_choice = GETPOST('type_choice');
$fk_object = GETPOST('fk_object');
$field = GETPOST('field');
$value = GETPOST('value');

_get($get);
_put($put);

function _get($case, $obj=null) {
	
	global $type_choice;
	
	switch($case) {
		case 'new_question':
			print json_encode(draw_question($obj));
			break;
		
		case 'new_choice':print json_encode(draw_choice($obj));
			break;
	}
	
}

function _put($case) {
	
	global $db, $fk_questionnaire, $type_object, $fk_object, $field, $value, $fk_question, $type_choice;
	
	switch($case) {
		
		case 'add-question':
			$q = new Question($db);
			$q->fk_questionnaire = $fk_questionnaire;
			$q->save();
			_get('new_question', $q);
			break;
		
		case 'add-choice':
			$choice = new Choice($db);
			$choice->fk_question = $fk_question;
			$choice->type = $type_choice;
			$choice->save();
			_get('new_choice', $choice);
			break;
		
		case 'del-object':
			$obj = new $type_object($db);
			$obj->load($fk_object);
			$res = $obj->delete();
			print json_encode($res);
			break;
			
		case 'set-field':
			$res = setField($type_object, $fk_object, $field, $value);
			print json_encode($res);
			break;
			
	}
	
}