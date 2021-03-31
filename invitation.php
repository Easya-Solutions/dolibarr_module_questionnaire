<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/user/class/usergroup.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/contact/class/contact.class.php');

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action','alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref','alpha');
$fk_invitation = GETPOST('fk_invitation','int');
$groups = GETPOST('usergroups','none');
$users = GETPOST('users','none');
$emails = GETPOST('emails','alpha');
$date_limite_year = GETPOST('date_limiteyear','int');
$date_limite_month = GETPOST('date_limitemonth','int');
$date_limite_day = GETPOST('date_limiteday','int');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$title=GETPOST('title','alpha');
$mode = 'view';
if ($action == 'create' || $action == 'edit')
	$mode = 'edit';

$object = new Questionnaire($db);
$form = new Form($db);

if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);

if (empty($object->fk_statut))
{
	header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
	exit;
}

$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (GETPOST('cancel', 'alpha'))
{
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
	$massaction = '';
}
if ($massaction == 'presend') $action = 'presend';
if (GETPOST('modelselected','none')) $action = 'presend';

if ($action == 'presend' && !empty($toselect) && !GETPOST('sendto','none'))
{
    $sendto = array();
    foreach ($toselect as $fk_invite)
    {
        $o = new InvitationUser($db);
        $o->fetch($fk_invite);
        if (!empty($o->email)) $sendto[] = '<'.$o->email.'>'; // Hack
    }

    if (!empty($sendto))
    {
        $_GET['sendto'] = implode(',', $sendto); // Hack
    }

}

$arrayofselected = is_array($toselect) ? $toselect : array();


// Actions to send emails
$actiontypecode='AC_OTH_AUTO';
$trigger_name='QUESTIONNAIRE_SENTBYMAIL';
$autocopy='MAIN_MAIL_AUTOCOPY_QUESTIONNAIRE_TO';
$trackid='quest'.$object->id;
$old_element = $object->element;
$object->element = 'user';
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
$object->element = $old_element;

if (!empty($massaction) && $massaction == 'reopen' && !empty($arrayofselected))
{
	foreach ($arrayofselected as $inv_selected)
    {
        $invitation_user = new InvitationUser($db);
        $invitation_user->load($inv_selected);
        $invitation_user->reopen();
    }
}


if (!empty($massaction) && $massaction == 'send' && !empty($arrayofselected))
{
	$langs->load('mails');
	$invitation_user = new InvitationUser($db);

	foreach ($arrayofselected as $inv_selected)
	{
		$invitation_user->load($inv_selected);

		$contact = null;
		$company = null;
		if($invitation_user->type_element == 'contact'){
			dol_include_once('/contact/class/contact.class.php');
			$contact = new Contact($db);
			$contact->fetch($invitation_user->fk_element);
			$contact->fetch_thirdparty();
			$company = $contact->thirdparty;
		} elseif ($invitation_user->type_element == 'societe') {
			dol_include_once('/societe/class/societe.class.php');
			$company = new Societe($db);
			$company->fetch($invitation_user->fk_element);
		}

		// Update invitation if infos not exacts
		$update = false;
		if (is_object($contact) && !empty($contact->email) && $invitation_user->email != $contact->email) { $update = true; $invitation_user->email = $contact->email; }
		if ($update) {
			$result = $invitation_user->save();
			if ($result < 0) {
				$this->errors[] = $langs->trans('RequestManagerErrorWhenSaveInvitation');
				$this->errors[] = $invitation_user->errorsToString();
				return -1;
			}
		}

		// Send invitation to contact
		$langs->load('mails');
		$langs->load("commercial");
		if (!empty($conf->dolimail->enabled)) $langs->load("dolimail@dolimail");

		$trackid = 'quest' . $object->id;
		$deliveryreceipt = 0;

		// from / send to / ...
		$send_to = $invitation_user->email;
		$from = $conf->email_from;
		$sendtocc = '';
		$sendtobcc = '';

		// Define output language
		$outputlangs = $langs;
		$newlang = $conf->global->MAIN_MULTILANGS && is_object($company) ? $company->default_lang : '';
		if (!empty($newlang)) {
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->loadLangs(array('commercial', 'bills', 'orders', 'contracts', 'members', 'propal', 'products', 'supplier_proposal', 'interventions', 'questionnaire@questionnaire'));
		}

		$invitation_user->context['questionnaire_ref'] = $object->ref;
		$invitation_user->context['questionnaire_title'] = $object->title;
		$invitation_user->context['questionnaire_status'] = $object->getLibStatut(8);

		$invitation_user->context['questionnaire_invitation_ref'] = $invitation_user->ref;
		$invitation_user->context['questionnaire_invitation_token'] = $invitation_user->token;
		$invitation_user->context['questionnaire_invitation_email'] = $invitation_user->email;
		$civility = is_object($contact) ? $contact->getCivilityLabel() : '';
		$invitation_user->context['questionnaire_invitation_contact_name'] = is_object($contact) ? ucfirst($contact->lastname) : '';
		$invitation_user->context['questionnaire_invitation_contact_name_civility'] = is_object($contact) ? ((empty($civility) ? '' : $civility . ' ') . ucfirst($contact->lastname)) : '';
		$invitation_user->context['questionnaire_invitation_contact_full_name'] = is_object($contact) ? $contact->getFullName($outputlangs) : '';
		$invitation_user->context['questionnaire_invitation_contact_full_name_civility'] = is_object($contact) ? $contact->getFullName($outputlangs, 1) : '';
		$invitation_user->context['questionnaire_invitation_company'] = is_object($company) ? $company->getFullName($outputlangs) : '';
		$invitation_user->context['questionnaire_invitation_sent_status'] = $invitation_user->LibStatut($invitation_user->sent, 7);
		$invitation_user->context['questionnaire_invitation_date_answer_deadline'] = dol_print_date($invitation_user->date_limite_reponse, 'date');
		$invitation_user->context['questionnaire_invitation_date_validation'] = dol_print_date($invitation_user->date_validation, 'date');
		$invitation_user->context['questionnaire_invitation_date_sent'] = dol_print_date($invitation_user->date_envoi, 'date');
		$invitation_user->context['questionnaire_invitation_date_sent_remind'] = dol_print_date($invitation_user->date_sent_remind, 'date');
		$invitation_user->context['questionnaire_invitation_status'] = $invitation_user->getLibStatut(7);
		$invitation_user->context['questionnaire_invitation_answer_link'] = (empty($conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN) ? dol_buildpath('/questionnaire/public/toAnswer.php', 2) : $conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN . 'toAnswer.php') . '?id=' . $object->id . '&action=answer&fk_invitation=' . $invitation_user->id . '&token=' . $invitation_user->token;

		// Make substitution in email content
		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $invitation_user);
		$substitutionarray['__CHECK_READ__'] = (is_object($company)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $company->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
		$substitutionarray['__PERSONALIZED__'] = '';    // deprecated
		$substitutionarray['__CONTACTCIVNAME__'] = '';
		$parameters = array('mode' => 'formemail', 'contact' => &$contact, 'company' => &$company, 'questionnaire' => &$object);
		complete_substitutions_array($substitutionarray, $outputlangs, $invitation_user, $parameters);

		// Define subject / message
		$message = str_replace('\n', "\n", $message);
		// Deal with format differences between message and signature (text / HTML)
		if (dol_textishtml($message) && !dol_textishtml($substitutionarray['__USER_SIGNATURE__'])) {
			$substitutionarray['__USER_SIGNATURE__'] = dol_nl2br($substitutionarray['__USER_SIGNATURE__']);
		} else if (!dol_textishtml($message) && dol_textishtml($substitutionarray['__USER_SIGNATURE__'])) {
			$message = dol_nl2br($message);
		}

		$subject = make_substitutions($subject, $substitutionarray);
		$message = make_substitutions($message, $substitutionarray);
		if (method_exists($invitation_user, 'makeSubstitution')) {
			$subject = $invitation_user->makeSubstitution($subject);
			$message = $invitation_user->makeSubstitution($message);
		}

		// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
		$message = preg_replace("/^(<br>)+/", "", $message);
		$message = preg_replace("/^\n+/", "", $message);

		// Define $urlwithroot
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;        // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
		// Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$message = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1' . $urlwithroot . '/viewimage.php\2modulepart=medias\3file=\4\5', $message);

		// Send mail (substitutionarray must be done just before this)
		require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
		$sendcontext = 'standard';
		$mailfile = new CMailFile($subject, $send_to, $from, $message, array(), array(), array(), $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);
		if ($mailfile->error) {
			$this->error = $mailfile->error;
			$this->errors = $mailfile->errors;
			$errors = $mailfile->error . (is_array($mailfile->errors) ? (!empty($mailfile->error) ? ', ' : '') . join('; ', $mailfile->errors) : '');
			dol_syslog(__METHOD__ . " - Errors when create mail file : " . $errors);
			continue;
		} else {
			$result = $mailfile->sendfile();
			if ($result < 0 || !$result) {
				$langs->load("other");
				if ($mailfile->error) {
					$this->errors[] = $langs->trans('ErrorFailedToSendMail', $from, $send_to);
					$this->errors[] = $mailfile->error;
				} else {
					$this->errors[] = ' No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
				}
				$errors = (is_array($this->errors) ? join('; ', $this->errors) : '');
				dol_syslog(__METHOD__ . " - Errors when send mail file : " . $errors);
				continue;
			} else {
				setEventMessages($langs->trans("MailSuccessfulySent", $conf->email_from,  $invitation_user->email), null, 'mesgs');

				if (empty($invitation_user->sent)) $invitation_user->date_envoi = $now;
				else $invitation_user->date_sent_remind = $now;
				$invitation_user->sent = 1;
				$result = $invitation_user->save();
				if ($result < 0) {
					$this->errors[] = $langs->trans('RequestManagerErrorWhenSaveInvitation');
					$this->errors[] = $invitation_user->errorsToString();
				}
			}
		}
	}
}elseif($massaction == 'cancel' && !empty($arrayofselected)){

	foreach ($arrayofselected as $inv_selected)
	{
		$invitation = new InvitationUser($db);
		$invitation->load($inv_selected);
		$invitation->cancel();

	}
}elseif($massaction == 'delete' && !empty($arrayofselected)){

	foreach ($arrayofselected as $inv_selected)
	{
		$invitation = new InvitationUser($db);
		$invitation->load($inv_selected);
		$invitation->delete($user);
		$object->deleteAllAnswersUser($inv_selected);

	}
}
// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	$error = 0;
	switch ($action) {

		case 'edit':

			$invitation = new InvitationUser($db);
			$invitation->load($fk_invitation);
			$emails = $invitation->email;
			
			break;

		case 'save':

			// Enregistrement des données dans les tables invitation et invitation_user
			$invitation = new InvitationUser($db);
			$invitation->fk_questionnaire = $object->id;
			$invitation->date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
			if(!empty($fk_invitation)){
				$invitation->load($fk_invitation);
				$invitation->date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
				if(!empty($emails))$invitation->email = $emails;
				$invitation->save();
			}else {
				$invitation->addInvitationsUser($groups, $users, $emails);
			}
			
			$mode = 'view';
			break;

	
		case 'settitle':
			$object->title = $title;
			$object->save();
			
			header('Location: '.dol_buildpath('/questionnaire/invitation.php', 1).'?id='.$object->id);
			exit;
			break;
	}
}

llxHeader();
$head = questionnaire_prepare_head($object);
$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
dol_fiche_head($head, 'invitation', $langs->trans("questionnaire"), 0, $picto, 1);

_getBanner($object, $action, false);

$TBS = new TTemplateTBS();
$TBS->TBS->protect = false;
$TBS->TBS->noerr = true;

// Presend form
$modelmail='questionnaire';
$defaulttopic='SendQuestionnaireRef';
$diroutput = $conf->questionnaire->multidir_output[$object->entity];
$trackid = 'quest'.$object->id;
$object->context['onlykey'] = true;
include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';


$formcore = new TFormCore;
$formcore->Set_typeaff($mode);
if ($mode == 'edit')
	echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');
$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">'.$langs->trans("BackToList").'</a>';


//if()


print $TBS->render('tpl/invitation.tpl.php'
		, array() // Block
		, array(
		'object' => $object
		, 'view' => array(
			'mode' => $mode
			,'act'=>$action
			, 'action' => 'save'
			, 'urlinvitation' => dol_buildpath('/questionnaire/invitation.php', 1)
			, 'urladvselecttarget' => dol_buildpath('/questionnaire/advselecttarget.php', 1)
			, 'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			, 'showRef' => $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
			, 'showTitle' => $object->title
			, 'showStatus' => $object->getLibStatut(1)
			, 'list_invitations' => _getListInvitations($object)
			//, 'massaction' => printMassActionButton()
			,'fk_user' => $invitation->fk_element
		)
		, 'langs' => $langs
		, 'user' => $user
		, 'conf' => $conf
		, 'form' => array(
			'select_usergroups' => $form->multiselectarray('usergroups', _getUserGroups(), $invitations_usergroups, '', 0, '', 0, '100%')
			, 'select_users' => $form->multiselectarray('users', _getUsers(), $invitations_users, '', 0, '', 0, '100%')
			, 'date_limite' => $form->select_date($action === 'create' ? dol_now() + ($object->answer_deadline > 0 ? $object->answer_deadline : $conf->global->QUESTIONNAIRE_DEFAULT_ANSWER_DEADLINE) * 24 * 60 * 60 : $invitation->date_limite_reponse, 'date_limite', 0, 0, 0, '', 1, 0, 1)
			, 'fk_invitation' => $fk_invitation
			, 'emails' => $emails
		)
		, 'Questionnaire' => array(
			'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
			, 'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
			, 'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
		)
		)
);

function _getListInvitations(&$object)
{

	global $db, $langs, $hookmanager, $user, $form, $formcore;

	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;
    $r = new Listview($db, 'questionnaire-guests-list');

	$sql = 'SELECT invu.rowid, invu.fk_usergroup, invu.ref, COALESCE(NULLIF(invu.type_element,""), "External") as type_element,  invu.fk_questionnaire as fk_questionnaire, invu.token as token, invu.fk_element, invu.fk_questionnaire,invu.email, invu.date_limite_reponse, invu.fk_statut as status, invu.date_validation, invu.sent, invu.rowid as id_user,\'\' AS link_invit, \'\' AS action';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'quest_invitation_user invu ';
	$sql .= ' WHERE fk_questionnaire = '.$object->id;
	$sql .= ' AND (invu.fk_element > 0 OR invu.email != "") ';



    $TStatus = InvitationUser::$TStatus;

    $link = '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation=@rowid@&action=edit">@val@</a>';


    $listViewConfig = array(
        'view_type' => 'list' // default = [list], [raw], [chart]
    ,'allow-fields-select' => true
    ,'limit'=>array('nbLine' => 500)
    ,'subQuery' => array()
    ,'link' => array(
            'ref' => $link,
            'date_limite_reponse' => $link,
            'email' => $link,
        )
    ,'type' => array(
            'date_limite_reponse' => 'date' // [datetime], [hour], [money], [number], [integer]
        ,'date_validation' => 'date'
        )
    ,'search' => array(
        'date_limite_reponse' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'date_validation' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'status' => array('search_type' => $TStatus , 'to_translate' => true, 'field' => array('fk_statut')) // selec
        ,'sent' => array('search_type' => InvitationUser::$TSentStatus , 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
        ,'email' => array('search_type' => true, 'table' => array('invu', 'invu'), 'field' => array('email'))
		,'ref' => array('search_type' => true, 'table' => array('invu', 'invu'), 'field' => array('ref'))
        )
    ,'translate' => array()

    ,'list' => array(
            'param_url' => 'id='.$object->id,
            'title' => $langs->trans('QuestionnaireGuestList')
            ,'massactions'=>array(
                    'presend' => $langs->trans("SendByMail"),
					'reopen' => $langs->trans("Reopen"),
					'cancel' => $langs->trans("Cancel"),
                    'delete'=>$langs->trans("Delete"),
                )
            )
    ,'hide'=> array('rowid')
    ,'title'=>array(
        'ref' => $langs->trans('Ref')
        ,'fk_usergroup' => $langs->trans('Group')
        , 'fk_element' => $langs->trans('Element')
        , 'email' => $langs->trans('Email')
        ,'date_limite_reponse' => $langs->trans('questionnaire_date_limite_reponse')
        ,'date_validation' => $langs->trans('ValidationDate')
        , 'sent' => $langs->trans('Sent')
        , 'status' => $langs->trans('StatusInvitation')

        , 'link_invit' => $langs->trans('LinkInvit')
        ,'selectedfields' => ''
    )
    , 'eval' => array(
            'date_limite_reponse' => '_getDateFr("@date_limite_reponse@")'
        , 'fk_element' => '_getNomUrl("@fk_element@","Externe","@type_element@")'
        , 'sent' => '_libStatut("@sent@")'
        , 'action' => '_actionLink("@id_user@")'
        , 'fk_usergroup' => '_getNomUrlGrp("@fk_usergroup@")'
        , 'link_invit' => '_getLinkUrl("@type_element@","@fk_element@","@fk_questionnaire@","@id_user@","@token@")'

        ,'status' => '_getLinkAnswersStatut("@status@")'
        )
    );

    $formcore = new TFormCore();
    $url = $_SERVER['PHP_SELF'].'?id='.$object->id;

    // Change view from hooks
    $parameters=array(  'listViewConfig' => $listViewConfig, 'url' =>& $url);

    $reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    if ($reshook>0)
    {
        $listViewConfig = $hookmanager->resArray;
    }

    $res = $formcore->begin_form($url, 'form_list_questionnaire', 'POST');
    $res.= $r->render($sql, $listViewConfig);


	$parameters = array('sql' => $sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object);	// Note that $action and $object may have been modified by hook
	$res .= $hookmanager->resPrint;

	$res .= $formcore->end_form();
	return $res;
}

function _getLinkUrl($type_element, $fk_element, $fk_questionnaire, $fk_invit, $token)
{
	global $conf, $langs;

	$url_answer_link = (empty($conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN) ? dol_buildpath('/questionnaire/public/toAnswer.php', 2) : $conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN . 'toAnswer.php') . '?id=' . $fk_questionnaire . '&action=answer&fk_invitation=' . $fk_invit . '&token=' . $token;
	return '<input style="width:100px;" class="button questionnaire_copy_text" type="text" value="' . $langs->trans('CopyLink') . '" text_to_copy="' . $url_answer_link . '"/>';
}

function _getLinkAnswersStatut($status)
{
//
//    global $db, $id, $questionnaire_status_forced_key;
//
//    if ($status == 1)
//        $questionnaire_status_forced_key = 'answerValidate';
//    else
//        $questionnaire_status_forced_key = '';
//
//    // Juste pour utiliser la fonction LibStatus
//    $q = new Questionnaire($db);
//    $q->fetch($id);
//
//    return $q->LibStatut($status, 6);
	global $db;

    // Juste pour utiliser la fonction LibStatus
    $q = new InvitationUser($db);
    $q->fk_statut = $status;
	return $q->getLibStatut(6);

}

function _getUsers()
{

	global $db;

	$sql = 'SELECT rowid, lastname, firstname
			FROM '.MAIN_DB_PREFIX.'user
			WHERE statut = 1';

	$resql = $db->query($sql);
	$TRes = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
			$TRes[$res->rowid] = $res->lastname.' '.$res->firstname;
	}

	return $TRes;
}

function _getUserGroups()
{

	global $db;

	$sql = 'SELECT rowid, nom
			FROM '.MAIN_DB_PREFIX.'usergroup';

	$resql = $db->query($sql);
	$TRes = array();
	if (!empty($resql) && $db->num_rows($resql) > 0)
	{
		while ($res = $db->fetch_object($resql))
			$TRes[$res->rowid] = $res->nom;
	}

	return $TRes;
}

function _getNomUrl($fk_element, $email,$type_element)
{

	global $db;
	$type_element= ucfirst($type_element);
	if($type_element == 'Thirdparty')$type_element='Societe';
	if(class_exists($type_element))$u = new $type_element($db);
	
	if (!empty($fk_element) && method_exists($u, 'getNomUrl')){
		$u->fetch($fk_element);
		$res = $u->getNomUrl(1);	
	}else
		$res = $email;
	return $res;
}
function _getNomUrlGrp($fk_usergroup)
{

	global $db;

	$u = new UserGroup($db);
	$u->fetch($fk_usergroup);
	if (!empty($fk_usergroup))
		if(method_exists($u, 'getNomUrl')) $res = $u->getNomUrl();
		else $res = $u->nom;
	else
		$res = 'Non';
	return $res;
}

function _libStatut($status)
{

	return InvitationUser::LibStatut($status, 6);
}

function _actionLink(  $fk_invit)
{
	global $object, $massactionbutton, $massaction, $arrayofselected;
	$link = '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation='.$fk_invit.'&action=edit">'.img_edit().'</a>'; //<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&fk_invitation=@rowid@&action=delete_invitation">'.img_delete().'</a>'

	if (1)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($fk_invit, $arrayofselected))
			$selected = 1;
		$link .= '&nbsp;&nbsp;&nbsp;<input id="cb'.$fk_invit.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$fk_invit.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	return $link;
}

function printMassActionButton()
{
	global $formcore, $langs, $form, $massaction, $toselect;
	
	
	$ret = $formcore->begin_form($_SERVER['PHP_SELF'], 'form_massaction');
	$ret .= '<input hidden name="id" type="text" value="'.GETPOST('id','int').'"/>';
	
//	if ($massaction == 'predelete')
//	{
//
//		$ret .=  $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500,1);
//	}

	$arrayofmassactions = array(
		'presend' => $langs->trans("SendByMail"),
        'delete'=>$langs->trans("Delete"),
	);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$ret .= '<td class="nobordernopadding center valignmiddle">'.$massactionbutton.'</td>';
	return $ret;
}

llxFooter();
