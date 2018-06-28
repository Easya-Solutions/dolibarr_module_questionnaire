<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/advtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/html.formadvtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/advthirdparties.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

// Translations
$langs->load("mails");
$langs->load("companies");
if (!empty($conf->categorie->enabled))
{
	$langs->load("categories");
}
$langs->load('questionnaire@questionnaire');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1)
{
	$page = 0;
}	 // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder)
	$sortorder = "ASC";
if (!$sortfield)
	$sortfield = "email";

$ref = GETPOST('ref');
$title = GETPOST('title');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');
$search_nom = GETPOST("search_nom");
$search_prenom = GETPOST("search_prenom");
$search_email = GETPOST("search_email");
$template_id = GETPOST('template_id', 'int');
$type_element = GETPOST('type_element');

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha'))
{
	$search_nom = '';
	$search_prenom = '';
	$search_email = '';
}
$array_query = array();
$advTarget = new AdvanceTargetingMailing($db);

if (empty($template_id))
{
	$advTarget->fk_element = $id;
	$advTarget->type_element = 'questionnaire';
	$result = $advTarget->fetch_by_element();
	
}
else
{
	$result = $advTarget->fetch($template_id);
}

if ($result < 0)
{
	setEventMessage($advTarget->error, 'errors');
}
else
{
	if (!empty($advTarget->id))
	{
		$array_query = json_decode($advTarget->filtervalue, true);
	}
}

$object = new Questionnaire($db);

if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);


if (empty($object->fk_statut))
{
	header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
	exit;
}



/*
 * Actions
 */
switch ($action) {
	case 'settitle':
		$object->title = $title;
		$object->save();

		header('Location: '.dol_buildpath('/questionnaire/advselecttarget.php', 1).'?id='.$object->id);
		exit;
		break;
}





llxHeader();

$form = new Form($db);
$formadvtargetemaling = new FormAdvTargetEmailing($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'invitation', $langs->trans("questionnaire"), 0, $picto, 1);

_getBanner($object, $action, false);



if ((float) DOL_VERSION < 8)
{
	if(file_exists(DOL_DOCUMENT_ROOT.'/custom/questionnaire/tpl/advtarget.tpl.php'))
	include DOL_DOCUMENT_ROOT.'/custom/questionnaire/tpl/advtarget.tpl.php';
	else include DOL_DOCUMENT_ROOT.'/questionnaire/tpl/advtarget.tpl.php';
		
}
else
{
	include DOL_DOCUMENT_ROOT.'/core/tpl/advtarget.tpl.php';
}

