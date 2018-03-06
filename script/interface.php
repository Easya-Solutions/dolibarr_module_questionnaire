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
$fk_object = GETPOST('fk_object');
$field = GETPOST('field');
$value = GETPOST('value');

_get($get);
_put($put);

function _get($case, $q=null) {
	
	switch($case) {
		case 'new_question':
			print json_encode(draw_question($q));
			break;
	}
	
}

function _put($case) {
	
	global $db, $fk_questionnaire, $type_object, $fk_object, $field, $value;
	
	switch($case) {
		
		case 'add-question':
			$q = new Question($db);
			$q->fk_questionnaire = $fk_questionnaire;
			$q->save();
			_get('new_question', $q);
			break;
		
		case 'set-field':
			$type_object= ucfirst($type_object);
			$choice = new $type_object($db);
			$choice->load($fk_object);
			$choice->{$field} = $value;
			$res = $choice->save();
			print json_encode('1');
			break;
			
	}
	
}