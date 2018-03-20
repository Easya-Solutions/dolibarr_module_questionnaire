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
$type_question = GETPOST('type_question');
$type_object = GETPOST('type_object');
$type_choice = GETPOST('type_choice');
$fk_object = GETPOST('fk_object');
$field = GETPOST('field');
$value = GETPOST('value');
$origin = GETPOST('origin');

_get($get);
_put($put);

function _get($case, $obj=null) {
	
	global $type_choice, $origin;
	
	switch($case) {
		case 'new_question':
			print json_encode(draw_question($obj));
			break;
		
		case 'new_choice':
			print json_encode(draw_choice($obj));
			break;
			
		case 'select-originid':
			print json_encode(_getIdsObject($origin, true));
			break;
	}
	
}

function _put($case) {
	
	global $db, $fk_questionnaire, $type_object, $fk_object, $field, $value, $fk_question, $type_choice, $type_question;
	
	switch($case) {
		
		case 'add-question':
			$q = add_question($fk_questionnaire, $type_question);
			if($type_question === 'linearscale') {
				$q->choices = array();
				$q->choices[] = add_choice($q->id, 'from');
				$q->choices[] = add_choice($q->id, 'to');
				$q->choices[] = add_choice($q->id, 'step'); //Pas entre les chiffres, pour l'instant on oublie, marche pas bien avec la fonction radio_js_bloc_number()
			}
			_get('new_question', $q);
			break;
		
		case 'add-choice':
			$choice = add_choice($fk_question, $type_choice);
			_get('new_choice', $choice);
			break;
		
		case 'del-object':
			$res = del_object($type_object, $fk_object);
			print json_encode($res);
			break;
			
		case 'set-field':
			$res = setField($type_object, $fk_object, $field, $value);
			print json_encode($res);
			break;
			
	}
	
}

function add_question($fk_questionnaire, $type_question) {
	
	global $db;
	
	$q = new Question($db);
	$q->fk_questionnaire = $fk_questionnaire;
	$q->type = $type_question;
	$q->save();
	
	return $q;
	
}

function add_choice($fk_question, $type_choice, $label='') {
	
	global $db;
	
	$choice = new Choice($db);
	$choice->fk_question = $fk_question;
	$choice->type = $type_choice;
	if(!empty($label)) $choice->label = $label;
	$choice->save();
	
	return $choice;
	
}

function del_object($type_object, $fk_object) {
	
	global $db, $user;
	
	$obj = new $type_object($db);
	$obj->load($fk_object);
	return  $obj->delete($user);
	
}
