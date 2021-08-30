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
dol_include_once('/questionnaire/class/invitation.class.php');
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

$ref = GETPOST('ref','alpha');
$title = GETPOST('title','alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');
$search_nom = GETPOST("search_nom",'alpha');
$search_prenom = GETPOST("search_prenom",'alpha');
$search_email = GETPOST("search_email",'alpha');
$template_id = GETPOST('template_id', 'int');
$type_element = GETPOST('type_element','int');
$date_limite_reponseyear = GETPOST('date_limite_reponseyear','alpha');
$date_limite_reponsemonth = GETPOST('date_limite_reponsemonth','alpha');
$date_limite_reponseday = GETPOST('date_limite_reponseday','alpha');



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
	if((float) DOL_VERSION >= 8)$result = $advTarget->fetch_by_element(0,$advTarget->type_element);
	else $result = $advTarget->fetch_by_mailing($id);

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
if ($action=='settitle') {
		$object->title = $title;
		$object->save();
		header('Location: '.dol_buildpath('/questionnaire/advselecttarget.php', 1).'?id='.$object->id);
		exit;
}


if ($action == 'loadfilter') {
	if (! empty($template_id)) {
		$result = $advTarget->fetch($template_id);
		if ($result < 0) {
			setEventMessage($advTarget->error, 'errors');
		} else {
			if (! empty($advTarget->id)) {
				$array_query = json_decode($advTarget->filtervalue, true);
			}
		}
	}
}


if ($action == 'add') {

	$user_contact_query = false;

	$array_query = array ();

	// Get extra fields

	foreach ( $_POST as $key => $value ) {
		// print '$key='.$key.' $value='.$value.'<BR>';
		if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_st_dt', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_st_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear', 'int'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_end_dt', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_end_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear', 'int'));
				}
			} else {
				$array_query[$key] = GETPOST($key,'int');
			}
		}
		if (preg_match("/^options_.*_cnct/", $key)) {
			$user_contact_query = true;
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_st_dt' . '_cnct', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_st_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear' . '_cnct', 'int'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_end_dt' . '_cnct', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_end_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear' . '_cnct', 'int'));
				}
			} else {
				$array_query[$key] = GETPOST($key,'int');
			}
		}

		if (preg_match("/^cust_/", $key)) {
			$array_query[$key] = GETPOST($key,'int');
		}

		if (preg_match("/^contact_/", $key)) {

			$array_query[$key] = GETPOST($key,'int');

			$specials_date_key = array (
					'contact_update_st_dt',
					'contact_update_end_dt',
					'contact_create_st_dt',
					'contact_create_end_dt'
			);
			foreach ( $specials_date_key as $date_key ) {
				if ($key == $date_key) {
					$dt = GETPOST($date_key,'int');
					if (! empty($dt)) {
						$array_query[$key] = dol_mktime(0, 0, 0, GETPOST($date_key . 'month', 'int'), GETPOST($date_key . 'day', 'int'), GETPOST($date_key . 'year', 'int'));
					} else {
						$array_query[$key] = '';
					}
				}
			}

			if (! empty($array_query[$key])) {
				$user_contact_query = true;
			}
		}

		if ($array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4) {
			$user_contact_query = true;
		}

		if (preg_match("/^type_of_target/", $key)) {
			$array_query[$key] = GETPOST($key,'int');
		}
	}
	// if ($array_query ['type_of_target'] == 1 || $array_query ['type_of_target'] == 3) {
	$result = $advTarget->query_thirdparty($array_query);


	if ($result < 0) {
		setEventMessage($advTarget->error, 'errors');
	}
	/*} else {
		$advTarget->thirdparty_lines = array ();
	}*/

	if ($user_contact_query && ($array_query['type_of_target'] == 1 || $array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4)) {
		$result = $advTarget->query_contact($array_query, 1);
		if ($result < 0) {
			setEventMessage($advTarget->error, 'errors');
		}
		// If use contact but no result use artefact to so not use socid into add_to_target
		if ($array_query['type_of_target'] != 1) {
			$advTarget->thirdparty_lines = array ();
		}
	} else {
		$advTarget->contact_lines = array ();
	}

	if ((!empty($advTarget->thirdparty_lines)) || (!empty($advTarget->contact_lines))) {
		// Add targets into database
		$obj = new InvitationUser($db);
		$cibles = $obj->add_to_target_spec($id, $advTarget->thirdparty_lines, $array_query['type_of_target'], $advTarget->contact_lines);
		$obj->fk_questionnaire = $id;
		$obj->date_limite_reponse = strtotime($date_limite_reponseyear.'-'.$date_limite_reponsemonth.'-'.$date_limite_reponseday);
		$empty=array();
		$obj->addInvitationsUser($empty,$empty, $empty,$cibles);

		$result=1;
	} else {
		$result = 0;
	}

	if ($result > 0) {
		$query_temlate_id = '';
		if (! empty($template_id)) {
			$query_temlate_id = '&template_id=' . $template_id;
		}

		header("Location: " .dol_buildpath('/questionnaire/invitation.php', 2) . "?id=" . $id);
		exit();
	}
	if ($result == 0) {
		setEventMessage($langs->trans("WarningNoEMailsAdded"), 'warnings');
	}
	if ($result < 0) {
		setEventMessage($obj->error, 'errors');
	}
}

if ($action == 'clear') {
	// Chargement de la classe
	$classname = "MailingTargets";
	$obj = new $classname($db);
	$obj->clear_target($id);

	header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	exit();
}

if ($action == 'savefilter' || $action == 'createfilter') {

	$template_name = GETPOST('template_name','alpha');
	$error = 0;

	if ($action == 'createfilter' && empty($template_name)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('AdvTgtOrCreateNewFilter')), 'errors');
		$error ++;
	}

	if (empty($error)) {

		$array_query = array ();

		// Get extra fields
		foreach ( $_POST as $key => $value ) {
			if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_st_dt', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_st_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear', 'int'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_end_dt', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_end_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear', 'int'));
						// print $array_query['options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key,'int');
				}
			}
			if (preg_match("/^options_.*_cnct/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_st_dt' . '_cnct', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_st_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear' . '_cnct', 'int'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_end_dt' . '_cnct', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_end_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear' . '_cnct', 'int'));
						// print $array_query['cnct_options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key,'int');
				}
			}

			if (preg_match("/^cust_/", $key)) {
				$array_query[$key] = GETPOST($key,'int');
			}

			if (preg_match("/^contact_/", $key)) {

				$array_query[$key] = GETPOST($key,'int');

				$specials_date_key = array (
						'contact_update_st_dt',
						'contact_update_end_dt',
						'contact_create_st_dt',
						'contact_create_end_dt'
				);
				foreach ( $specials_date_key as $date_key ) {
					if ($key == $date_key) {
						$dt = GETPOST($date_key,'int');
						if (! empty($dt)) {
							$array_query[$key] = dol_mktime(0, 0, 0, GETPOST($date_key . 'month', 'int'), GETPOST($date_key . 'day', 'int'), GETPOST($date_key . 'year', 'int'));
						} else {
							$array_query[$key] = '';
						}
					}
				}
			}

			if (preg_match("/^type_of_target/", $key)) {
				$array_query[$key] = GETPOST($key,'int');
			}
		}
		$advTarget->filtervalue = json_encode($array_query);

		if ($action == 'createfilter') {
			$advTarget->name = $template_name;
			$result = $advTarget->create($user);
			if ($result < 0) {
				setEventMessage($advTarget->error, 'errors');
			}
		} elseif ($action == 'savefilter') {

			$result = $advTarget->update($user);
			if ($result < 0) {
				setEventMessage($advTarget->error, 'errors');
			}
		}
		$template_id = $advTarget->id;
	}
}

if ($action == 'deletefilter') {
	$result = $advTarget->delete($user);
	if ($result < 0) {
		setEventMessage($advTarget->error, 'errors');
	}
	header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	exit();
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
	$filePath = '/questionnaire/tpl/advtarget.tpl.php'; // fix For Dolistore zip check
	$customFilePath = '/custom'.$filePath; // fix For Dolistore zip check

	if(file_exists(DOL_DOCUMENT_ROOT.$customFilePath))
	{
		include DOL_DOCUMENT_ROOT . $customFilePath;
	}
	else
	{
		include DOL_DOCUMENT_ROOT . $filePath;
	}
}
else
{
	include DOL_DOCUMENT_ROOT.'/core/tpl/advtarget.tpl.php';
}



?>

<script>
	$(document).ready(function() {
		$('form').last().hide();
		$('#find_customer tr:first').before("<tr><td class='fieldrequired'><?php echo $langs->trans('questionnaire_date_limite_reponse'); ?></td><td id='select_date_quest'></td><td></td></tr>");
		$($('#to_move').detach()).appendTo($('#select_date_quest'));

	});
</script>
<?php  print '<div id="to_move" >';
if(!empty($date_limite_reponseday))$date=strtotime($date_limite_reponseyear.'-'.$date_limite_reponsemonth.'-'.$date_limite_reponseday);
else $date=dol_now() + ($object->answer_deadline > 0 ? $object->answer_deadline : $conf->global->QUESTIONNAIRE_DEFAULT_ANSWER_DEADLINE) * 24 * 60 * 60;
$form->select_date($date, 'date_limite_reponse');
	print '</div>';
	llxFooter();
