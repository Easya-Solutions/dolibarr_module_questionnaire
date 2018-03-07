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
	
	$form = new Form($db);
	
	$res = '<div style="background-color:'.$bgcol_questionnaire[$bg_color].';" class="element" type="question" id="question'.$q->id.'">';
	$res.= '<div class="refid">Q.&nbsp;';
	$res.= '<input placeholder="Question" type="text" name="label" class="field" id="label" name="label" value="'.$q->label.'"/>';
	$res.= '<a id="del_element_'.$q->id.'" name="del_element_'.$q->id.'" href="#" onclick="return false;">'.img_delete().'</a>';
	$res.= '<br /><br /></div>';
	
	// Liste des choix
	$q->loadChoices();
	if(!empty($q->choices)) {
		foreach($q->choices as &$choice)  $res.= draw_choice($choice);
	}
	$choice = new Choice($db);
	$res.= $form->selectarray('select_choice_q'.$q->id, $choice->TTypes, '', 1);
	
	$res.= '<button class="butAction" id="butAddChoice_q'.$q->id.'" name="butAddChoice_q'.$q->id.'">Ajouter un choix</button>';
	$res.= '<br /><br /><br /></div>';
	
	$bg_color = !$bg_color;
	
	return $res;
	
}

function draw_choice(&$choice) {
	
	$res.= '<div class="element" type="choice" id="choice'.$choice->id.'">';
	$res.= '<a id="del_element_'.$choice->id.'" name="del_element_'.$choice->id.'" href="#" onclick="return false;">'.img_delete().'</a>';
	$res.= '<input placeholder="Libellé choix" type="text" name="label" class="field" value="'.$choice->label.'" />&nbsp;('.$choice->type.')';
	$res.= '<br /><br /></div>';
	
	return $res;
}

function setField($type_object, $fk_object, $field, $value) {
	
	global $db;
	
	$type_object = ucfirst($type_object);
	$obj = new $type_object($db);
	$obj->load($fk_object);
	$obj->{$field} = $value;
	return $obj->save();
	
}
