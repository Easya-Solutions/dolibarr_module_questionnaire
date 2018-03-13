<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/questionnaire.lib.php
 *	\ingroup	questionnaire
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function questionnaireAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("questionnaire@questionnaire");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/questionnaire/admin/questionnaire_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/questionnaire/admin/questionnaire_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@questionnaire:/questionnaire/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@questionnaire:/questionnaire/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'questionnaire');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	Questionnaire	$object		Object company shown
 * @return 	array				Array of tabs
 */
function questionnaire_prepare_head(Questionnaire $object)
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("questionnaireCard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@questionnaire:/questionnaire/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@questionnaire:/questionnaire/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'questionnaire');
	
	return $head;
}

function getFormConfirmquestionnaire(&$PDOdb, &$form, &$object, $action)
{
    global $langs,$conf,$user;

    $formconfirm = '';

    if ($action == 'validate' && !empty($user->rights->questionnaire->write))
    {
        $text = $langs->trans('ConfirmValidatequestionnaire', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Validatequestionnaire'), $text, 'confirm_validate', '', 0, 1);
    }
    elseif ($action == 'delete' && !empty($user->rights->questionnaire->write))
    {
        $text = $langs->trans('ConfirmDeletequestionnaire');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Deletequestionnaire'), $text, 'confirm_delete', '', 0, 1);
    }
    elseif ($action == 'clone' && !empty($user->rights->questionnaire->write))
    {
        $text = $langs->trans('ConfirmClonequestionnaire', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Clonequestionnaire'), $text, 'confirm_clone', '', 0, 1);
    }

    return $formconfirm;
}

function draw_question(&$q) {
	
	global $db, $bg_color;
	
	if(!isset($bg_color)) $bg_color = 0;
	
	$bgcol_questionnaire = array(0=>'rgb(248,248,248)', 1=>'rgb(255,255,255)');
	
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	dol_include_once('/questionnaire/class/choice.class.php');
	
	$question_est_une_grille = strpos($q->type, 'grille') !== false;
	
	$form = new Form($db);
	
	//$res = '<div style="background-color:'.$bgcol_questionnaire[$bg_color].';" class="element" type="question" id="question'.$q->id.'">';
	$res = '<div class="element" type="question" id="question'.$q->id.'">';
	$res.= '<div class="refid">Question : '.$q->TTypes[$q->type].'<br />';
	$res.= '<input size="100" placeholder="Question" type="text" name="label" class="field" id="label" name="label" value="'.$q->label.'"/>';
	$res.= '<input type="checkbox" title="Réponse obligatoire ?" class="field" name="compulsory_answer"';
	$res.= (int)$q->compulsory_answer > 0 ? 'checked="checked"' : '';
	$res.= '/>';
	$res.= '&nbsp;<a id="del_element_'.$q->id.'" name="del_element_'.$q->id.'" href="#" onclick="return false;">'.img_picto('Supprimer question', 'delete_all@questionnaire').'</a>';
	$res.= '<br /><br /></div>';
	
	// Pas de choix pour les types string et textarea
	
	if($q->type !== 'string' && $q->type !== 'textarea' && $q->type !== 'date' && $q->type !== 'hour' && $q->type !== 'linearscale') {
			// Liste des choix (lignes)
			$style_div_lines = ' width: 300px; ';
			if($question_est_une_grille) $style_div_lines.= ' float: left; ';
			$res.= '<div class="refid" style="'.$style_div_lines.'" id="allChoicesLeft_q'.$q->id.'" name="allChoicesLeft_q'.$q->id.'">';
			$res.= 'Lignes<br /><br />';
			$q->loadChoices();
			if(!empty($q->choices)) {
				foreach($q->choices as &$choice) {
					if($choice->type === 'line') $res.= draw_choice($choice);
				}
			}
			$res.= '<button class="butAction" id="butAddChoiceLine_q'.$q->id.'" name="butAddChoiceLine_q'.$q->id.'">Ajouter une ligne</button>';
			$res.= '</div>';
			
			// Liste des choix (colonnes => Uniquement pour les grilles)
			if($question_est_une_grille) {
				$res.= '<div style="float: left;" class="refid" id="allChoicesRight_q'.$q->id.'">';
				$res.= 'Colonnes<br /><br />';
				if(!empty($q->choices)) {
					foreach($q->choices as &$choice) {
						if($choice->type === 'column') $res.= draw_choice($choice);
					}
				}
				$res.= '<button class="butAction" id="butAddChoiceColumn_q'.$q->id.'" name="butAddChoiceColumn_q'.$q->id.'">Ajouter un colonne</button>';
				$res.= '</div>';
			}
			
	} elseif($q->type === 'linearscale') {
		
		$res.= '<div style="'.$style_div_lines.'" id="allChoicesLeft_q'.$q->id.'" name="allChoicesLeft_q'.$q->id.'">';
		
		if(empty($q->choices)) $q->loadChoices();
		$res.= draw_choice($q->choices[0], 'linearscale', 'De');
		$res.= draw_choice($q->choices[1], 'linearscale', 'à');
		$res.= draw_choice($q->choices[2], 'linearscale', 'Pas');
		$res.= '</div>';
		
	}
	
	$res.= '<div style="clear: both;"></div><br /><br /></div>';
	
	$bg_color = !$bg_color;
	
	return $res;
	
}

function draw_choice(&$choice, $type='', $title='') {
	
	if(empty($type)) return draw_standard_choice($choice);
	elseif($type === 'linearscale') return draw_linearscale_choice($choice, $title);
	
}

function draw_standard_choice(&$choice) {
	
	$res.= '<div class="element" type="choice" id="choice'.$choice->id.'">';
	$res.= '<input placeholder="Libellé choix" type="text" name="label" class="field" value="'.$choice->label.'" />&nbsp;';
	$res.= '<a id="del_element_'.$choice->id.'" name="del_element_'.$choice->id.'" href="#" onclick="return false;">'.img_delete().'</a>';
	$res.= '<br /><br /></div>';
	
	return $res;
	
}

function draw_linearscale_choice(&$choice, $title) {
	
	$res.= '<div style="float:left;" class="element" type="choice" id="choice'.$choice->id.'">';
	$res.= $title.'&nbsp;&nbsp;<input type="number" style="width:50px;" name="label" class="field" value="'.$choice->label.'" />&nbsp;&nbsp;</div>';
	
	return $res;
	
}

function draw_question_for_user(&$q) {
	
	if(empty($q->choices)) $q->loadChoices();
	if(!empty($q->choices) || $q->type === 'string' || $q->type === 'textarea' || $q->type === 'date' || $q->type === 'hour' || $q->type === 'linearscale'/*Pas de choix pour ces types là*/) {
		$res = '<div class="element" type="question" id="question'.$q->id.'">';
		$res.= '<div class="refid">'.$q->label.'</div>';
		
		switch($q->type) {
			
			case 'string':
				$res.= draw_string_for_user($q);
				break;
				
			case 'textarea':
				$res.= draw_textarea_for_user($q);
				break;
				
			case 'select':
				$res.= draw_select_for_user($q);
				break;
				
			case 'listradio':
				$res.= draw_listradio_for_user($q);
				break;
				
			case 'grilleradio':
				$res.= draw_grilleradio_for_user($q);
				break;
				
			case 'listcheckbox':
				$res.= draw_listcheckbox_for_user($q);
				break;
				
			case 'grillecheckbox':
				$res.= draw_grillecheckbox_for_user($q);
				break;
				
			case 'date':
				$res.= draw_date_for_user($q);
				break;
			
			case 'hour':
				$res.= draw_hour_for_user($q);
				break;
			
			case 'linearscale':
				$res.= draw_linearscale_for_user($q);
				break;
				
		}
		
		$res.= '</div>';
	}
	return $res;
}

function draw_string_for_user(&$q) {
	
	return '<input type="text" name="TAnswer['.$q->id.']" value="'.$q->answers[0]->value.'" />';
	
}

function draw_textarea_for_user(&$q) {
	
	return '<textarea rows="7" cols="50" type="text" name="TAnswer['.$q->id.']" id="rep_q'.$q->id.'">'.$q->answers[0]->value.'</textarea>';
	
}

function draw_select_for_user(&$q) {
	
	global $form;
	
	$tab = array();
	foreach($q->choices as &$choix) {
		$tab[$choix->id] = $choix->label;
	}
	return $form->selectarray('TAnswer['.$q->id.'][]', $tab, $q->answers[0]->fk_choix);
	
}

function draw_listradio_for_user(&$q) {
	
	$res = '<br />';
	//var_dump($q->choices);exit;
	foreach($q->choices as &$choix) {
		$res.= '<input type="radio" ';
		if(!empty($q->answers)) {
			if($choix->id == $q->answers[0]->fk_choix) {
				$res.= 'checked';
			}
		}
		$res.= ' name="TAnswer['.$q->id.'][]" value="'.$choix->id.'">&nbsp;'.$choix->label.'<br />';
	}
	
	return $res;
	
}

function draw_listcheckbox_for_user(&$q) {
	
	$res = '<br />';
	foreach($q->choices as &$choix) {
		$res.= '<input type="checkbox" ';
		if(!empty($q->answers)) {
			foreach($q->answers as &$answer) {
				if($choix->id == $answer->fk_choix) {
					$res.= 'checked';
					break;
				}
			}
		}
		$res.= ' name="TAnswer['.$q->id.'][]" value="'.$choix->id.'" />&nbsp;'.$choix->label.'<br />';
	}
	
	return $res;
	
}

function draw_grilleradio_for_user(&$q) {
	
	$res = '<br /><table class="noborder"><tr><td></td>';
	foreach($q->choices as &$choix_col) {
		if($choix_col->type === 'column') $res.= '<td>'.$choix_col->label.'</td>';
	}
	$res.= '</tr>';
	
	$first_line=true;
	foreach($q->choices as &$choix_line) {
		
		if($choix_line->type === 'line') $res.= '<tr><td>'.$choix_line->label.'</td>';
		else continue;
		
		foreach($q->choices as &$choix_col) {
			if($choix_col->type === 'column')  {
				$res.= '<td><input type="radio" ';
				if(!empty($q->answers)) {
					foreach($q->answers as &$answer) {
						if($answer->fk_choix == $choix_line->id && $answer->fk_choix_col == $choix_col->id) {
							$res.= 'checked';
							break;
						}
					}
				}
				$res.= ' name="TAnswer['.$q->id.']['.$choix_line->id.']" value="'.$choix_line->id.'_'.$choix_col->id.'"/></td>';
			}
			else continue;
		}
		
		$first_line=false;
		$res.= '</tr>';
		
	}
	$res.= '</table>';
	
	return $res;
	
}

function draw_grillecheckbox_for_user(&$q) {
	
	$res = '<br /><table class="noborder"><tr><td></td>';
	foreach($q->choices as &$choix_col) {
		if($choix_col->type === 'column') $res.= '<td>'.$choix_col->label.'</td>';
	}
	$res.= '</tr>';
	
	$first_line=true;
	foreach($q->choices as &$choix_line) {
		
		if($choix_line->type === 'line') $res.= '<tr><td>'.$choix_line->label.'</td>';
		else continue;
		
		foreach($q->choices as &$choix_col) {
			if($choix_col->type === 'column') {
				$res.= '<td><input type="checkbox" ';
				if(!empty($q->answers)) {
					foreach($q->answers as &$answer) {
						if($answer->fk_choix == $choix_line->id && $answer->fk_choix_col == $choix_col->id) {
							$res.= 'checked';
							break;
						}
					}
				}
				$res.= ' name="TAnswer['.$q->id.']['.$choix_line->id.'_'.$choix_col->id.']" value="'.$choix_line->id.'_'.$choix_col->id.'"/></td>';
			}
			else continue;
		}
		
		$first_line=false;
		$res.= '</tr>';
		
	}
	$res.= '</table>';
	
	return $res;
	
}

function draw_date_for_user(&$q) {
	
	global $form;
	
	return '<br />'.$form->select_date($q->answers[0]->value, 'date_q'.$q->id, 0, 0, 0, "", 1, 0, 1);
	
}

function draw_hour_for_user(&$q) {
	
	global $form;
	
	return $form->select_duration('time_q'.$q->id, $q->answers[0]->value, 0, 'text', 0, 1);
	
}

function draw_linearscale_for_user(&$q) {
	if(empty($q->choices)) $q->loadChoices();
	return '<br /><div class="slidecontainer"><input type="range" class="slider-color" id="linearscal_q'.$q->id.'" name="linearscal_q'.$q->id.'" min="'.$q->choices[0]->label.'" max="'.$q->choices[1]->label.'" step="'.$q->choices[2]->label.'" value="'.$q->answers[0]->value.'"/><br />
			<span>Valeur :&nbsp;</span><span style="font-weight:bold;color:red" id="val_linearscal_q'.$q->id.'">'.$q->answers[0]->value.'</span></div>';
}

function setField($type_object, $fk_object, $field, $value) {
	
	global $db;
	
	$type_object = ucfirst($type_object);
	$obj = new $type_object($db);
	$obj->load($fk_object);
	$obj->{$field} = $value;
	return $obj->save();
	
}
