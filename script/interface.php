<?php

require('../config.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/core/class/html.form.class.php');

$get = GETPOST('get');
$put = GETPOST('put');

$fk_questionnaire = GETPOST('fk_questionnaire');
$fk_question = GETPOST('fk_question');
$fk_choix = GETPOST('fk_choix', 'int');
$is_section = GETPOST('is_section');
$type_question = GETPOST('type_question');
$type_object = GETPOST('type_object');
$type_choice = GETPOST('type_choice');
$fk_object = GETPOST('fk_object');
$field = GETPOST('field');
$value = GETPOST('value');
$origin = GETPOST('origin');
$links = GETPOST('links');
$group = GETPOST('group');
$rang = GETPOST('rang');

$form = new Form($db);
_get($get);
_put($put);

function _get($case, $obj=null) {
	
    global $type_choice, $origin, $fk_questionnaire, $fk_question, $fk_choix,$db;
	
	switch($case) {
		case 'new_question':
			if(empty($obj)) {
				$obj = new Question($db);
				$obj->load($fk_question);
				
				if($obj->type == 'separator' ||  $obj->type == 'page')print json_encode(draw_question_for_admin($obj));
				else print json_encode(draw_question($obj));
				
				break;
			}else {
				if($obj->type == 'separator' ||  $obj->type == 'page')print json_encode(draw_question_for_admin($obj));
				else print json_encode(drawMandatory($obj,0).draw_question($obj).draw_action_element($obj).draw_add_element_line());
				break;
			}
			
		
		case 'new_choice':
			print json_encode(draw_choice($obj));
			break;
			
		case 'select-originid':
			print json_encode(_getIdsObject($origin, true));
			break;
			
		case 'next-questions':
		    print json_encode(_getNextQuestions($fk_questionnaire, $fk_question, $fk_choix));
		    break;
		
		case 'back_to_question':
			
			if(empty($obj) && !empty($fk_question)) {
				$fk_question = str_replace('question','',$fk_question);
				$fk_question = str_replace(' ','',$fk_question);

				$obj = new Question($db);
				$obj->load($fk_question);
				print json_encode(draw_question_for_admin($obj));
			}
			
			
			break;
	}
	
}

function _put($case) {
	
    global $db, $fk_questionnaire, $type_object, $fk_object, $field, $value, $fk_question, $type_choice, $type_question, $fk_choix, $links, $group, $rang;
	
	switch($case) {
		
		case 'add-question':
			
			$q = add_question($fk_questionnaire, $type_question, $rang);
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
			
		case 'link-question':
		    print json_encode(_link_question_to_choice($fk_questionnaire, $fk_question, $fk_choix));
		    break;
		    
		case 'select-choice':
		    print json_encode(_select_links($group));
		    break;
		
		case 'set_compulsory':
			$q = new Question($db);
			$q->load($fk_question);
			if($q->compulsory_answer == 0)$q->compulsory_answer = 1;
			elseif($q->compulsory_answer == 1)$q->compulsory_answer = 0;
			$q->save();
			
		    _get('back_to_question');
		    break;
	}
	
}

function add_question($fk_questionnaire, $type_question, $rang=0) {
	
	global $db;
	
	$q = new Question($db);
	$q->fk_questionnaire = $fk_questionnaire;
	$q->type = $type_question;
	$q->rang = $rang;
	if($q->type =='page' || $q->type=='separator')$q->label=$q->TTypes[$q->type];
	$q->incrementAllRank();
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
	$res = $obj->delete($user);
	
	if($type_object == "choice"){
	    $ql = new Questionlink($db);
	    $r =$ql->loadLink(0, $fk_object);
	    if ($r > 0) $ql->delete($user);
	}
	
	return  $res;
	
}

function _getNextQuestions($fk_questionnaire, $fk_question, $fk_choix){
    
    global $db;
    
    $sql = 'SELECT t.rowid, t.label FROM '.MAIN_DB_PREFIX.'quest_question as t';
    $sql.= ' WHERE t.fk_questionnaire = ' . $fk_questionnaire . ' AND t.rowid > '.$fk_question;
    $res = $db->query($sql);
    
    $ql = new Questionlink($db);
    $r = $ql->loadLink(0, $fk_choix);
    
    if($res){
        if($db->num_rows($res)) {
            
            $ret = '<select class="select_question" data-questionnaire="'.$fk_questionnaire.'">';
            $ret.= '<option value=""></option>';
            while ($obj = $db->fetch_object($res)) $ret .= '<option value="'.$obj->rowid.'"'.(($r > 0 && $ql->question_label == $obj->label) ? ' selected' : '').'>'.$obj->label.'</option>';
            $ret.= '</select>';
            
            return $ret;
        } else {
            return "no question after this one";
        }
    }
    
}

function _link_question_to_choice($fk_questionnaire, $fk_question, $fk_choix) {
    
    global $db, $user;
    
    $ql = new Questionlink($db);
    $r = $ql->loadLink(0, $fk_choix);
    
    $ql->fk_questionnaire = $fk_questionnaire;
    $ql->fk_question = $fk_question;
    $ql->fk_choix = $fk_choix;
   
    $ret = !empty($fk_question) ? $ql->save() : $ql->delete($user);
    $ql->loadLink($fk_question, $fk_choix);
    if ($ret > 0) return array("success" => true, "label" => $ql->question_label);
    else return array("success" => false);
}

function _select_links($group) {
    
    global $db;
    $ret = array();
    
    $ql = new Questionlink($db);
    
    foreach ($group as $fk_choix){
        $ret['choix'.$fk_choix]['enable'] = "";
        $ret['choix'.$fk_choix]['disable'] = array();
        if(!empty($fk_choix)){
            $r = $ql->loadLink(0, $fk_choix);
            if ($r > 0) $ret['choix'.$fk_choix]['enable'] = $ql->fk_question;
        }
    }
    
    foreach ($ret as $fk_choix => $val) {
        
        if(!empty($val['enable']))
        {
            foreach ($group as $choix){
                
                if('choix'.$choix !== $fk_choix) array_push($ret['choix'.$choix]['disable'], $val['enable']);
            }
        }
    }
    return $ret;
}
