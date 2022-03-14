<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*if (!defined('INC_FROM_DOLIBARR'))
{
        define('INC_FROM_DOLIBARR', '1');
}*/


if (!defined('NOCSRFCHECK'))
{
	define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU'))
{
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN"))
{
	define("NOLOGIN", '1');
}
// If this page is public (can be called outside logged session)
// Change this following line to use the correct relative path (../, ../../, etc)


require '../config.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
$langs->load('questionnaire@questionnaire');

$form = new Form($db);


$mode = 'view';

$action = GETPOST('action','alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref','alpha');
$fk_invitation = GETPOST('fk_invitation','int');
$title = GETPOST('title','alpha');
$origin = GETPOST('origin','alpha');
$originid = GETPOST('originid','int');
$fk_user_invitation = GETPOST('fk_userinvit','int');
$token = GETPOST('token','alpha');
$page=GETPOST('page','int');
$gotopage = GETPOST('gotopage','int');
if(empty($page))$page=1;


$invitation_user = new InvitationUser($db);
$res=$invitation_user->loadBy(array( 'rowid' => $fk_invitation, 'token' => "'$token'"));


if($action === 'answer' && empty($res) ||  empty($invitation_user->id) ||  $invitation_user->date_limite_reponse < strtotime(date('Y-m-d')) ){

	print('Date limite de reponse atteinte, ou token invalide.');
	exit;
}


if(!empty($conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN)) $path = '';
else $path = '/questionnaire/public';


$object = new Questionnaire($db);

if (!empty($id))
	$object->load($id);
elseif (!empty($ref))
	$object->load('', $ref);

if($invitation_user->fk_statut==1 ){
    print('Merci pour votre participation.');

	if(empty($object->after_answer_html) && !empty($conf->global->QUESTIONNAIRE_DEFAULT_AFTER_ANSWER_HTML)){
		$object->after_answer_html = $conf->global->QUESTIONNAIRE_DEFAULT_AFTER_ANSWER_HTML;
	}

	$substitution_questionnaire = $object->get_substitutionArray('questionnaire');
	$substitution_invitation_user = $invitation_user->get_substitutionArray('invitation');
	$substitution = array_replace ($substitution_questionnaire, $substitution_invitation_user );
	print make_substitutions($object->after_answer_html,$substitution);

    exit;
}


$object->loadInvitations();

$hookmanager->initHooks(array('questionnairecard', 'globalcard'));

if ($action == 'save_answer')
{
	// Suppression anciennes réponses
	$object->deleteAllAnswersUser($fk_invitation,$page);

	$TAnswer = GETPOST('TAnswer','array');

	foreach ($_REQUEST as $k => &$v)
	{

		if ($k === 'TAnswer')
		{

			foreach ($v as $fk_question => &$content)
			{

				// Ajout nouvelles réponses
				if (is_array($content) && !empty($content))
				{
					foreach ($content as $pos => &$answer_user)
					{

						if (empty($answer_user))
							continue;

						$answer = new Answer($db);
						$answer->fk_invitation_user = $fk_invitation;
						$answer->fk_question = $fk_question;

						if (strpos($pos, '_') !== false)
						{
							$TDetailRep = explode('_', $pos);
							$answer->fk_choix = $TDetailRep[0];
							$answer->fk_choix_col = $TDetailRep[1];
							$answer->value = $answer_user;
						}
						else if (strpos($answer_user, '_') !== false)
						{

							$TDetailRep = explode('_', $answer_user);
							$answer->fk_choix = $TDetailRep[0];
							$answer->fk_choix_col = $TDetailRep[1];
						}
						else
							$answer->fk_choix = $answer_user;
							$answer->fk_choix_col = 'DEFAULT';

						$result = $answer->save();
						if ($result == -1) {
							setEventMessage($answer->errors, 'errors');
						}
					}
				} elseif (!is_array($content) && !empty($content))
				{
					$answer = new Answer($db);
					$answer->fk_invitation_user = $fk_invitation;
					$answer->fk_question = $fk_question;
					$answer->fk_choix = 'DEFAULT';
					$answer->fk_choix_col = 'DEFAULT';
					$answer->value = $content;

					$result = $answer->save();
					if ($result == -1) {
						setEventMessage($answer->errors, 'errors');
					}
				}
			}
		}
		elseif (strpos($k, 'linearscal_q') !== false || strpos($k, 'date_q') !== false || strpos($k, 'time_q') !== false)
		{ // Ajout réponses non gérées dans le TAnswer (car pas possible ou galère en js)
			// Pour ne pas faire 4 fois l'enregistrement pour les dates
			if ((strpos($k, 'date_q') !== false && (strpos($k, 'day') !== false || strpos($k, 'month') !== false || strpos($k, 'year') !== false)) || (strpos($k, 'time_q') !== false && strpos($k, 'min') !== false))
				continue;

			// Suppression anciennes réponses
			$fk_question = strtr($k, array('linearscal_q' => '', 'date_q' => '', 'time_q' => '', 'hour' => '', 'min' => ''));

			$answer = new Answer($db);
			$answer->fk_question = $fk_question;
			$answer->fk_invitation_user= $fk_invitation;
			$answer->value = $v;

			$year = GETPOST('date_q'.$fk_question.'year','alpha');
			$month = GETPOST('date_q'.$fk_question.'month','alpha');
			$day = GETPOST('date_q'.$fk_question.'day','alpha');

			$hour = GETPOST('time_q'.$fk_question.'hour','alpha');
			$min = GETPOST('time_q'.$fk_question.'min','alpha');

			if (strpos($k, 'date_q') !== false && !empty($year))
				$answer->value = strtotime($year.'-'.$month.'-'.$day);
			if (strpos($k, 'time_q') !== false && (!empty($hour) || !empty($min)))
				$answer->value = ((int) $hour * 60 * 60) + ((int) $min * 60);

			if (!empty($answer->value))
				$answer->save();
		}
	}

	if (isset($_REQUEST['subSave']))
	{
		$invitation_user->fk_statut=2;
		$invitation_user->save();

		header('Location: '.dol_buildpath($path.'/toAnswer.php', 1).'?id='.$object->id.'&action=answer&fk_invitation='.$fk_invitation."&token=".$token.'&page='.$gotopage);
	}
	else
	{ // Validation finale
		header('Location: '.dol_buildpath($path.'/toAnswer.php', 1).'?id='.$object->id.'&action=validate_answers&fk_invitation='.$fk_invitation."&token=".$token);
	}
	exit;
}

else if ($action == 'validate_answers')
{

	$isOkForValidation = $object->isOkForValidation($fk_invitation);

	if ($isOkForValidation)
	{

		$invitation_user->setValid();
		$object->checkAllAnswer();

		setEventMessage($langs->trans('questionnaireValidated'));
		header('Location: '.dol_buildpath($path.'/toAnswer.php', 1).'?id='.$id.'&action=answer&fk_invitation='.$fk_invitation."&token=".$token);
	}
	else
	{
		setEventMessage($langs->trans('questionnaireNotValidated'), 'errors');
		header('Location: '.dol_buildpath($path.'/toAnswer.php', 1).'?id='.$id.'&action=answer&fk_invitation='.$fk_invitation."&token=".$token);
	}
	exit;
}
/**
 * View
 */
$title = $langs->trans("Module104961Name");
llxHeaderQuest();
//llxHeader('', $title,'', '',1, 0,'', array('/questionnaire/css/styles.css'));
//llxHeader('', $title,'', '', 0, 0,'', array($path.'/css/styles.css.php'));

print $formconfirm;
?>

<style>
	.slidecontainer {
		width: 50%;
	}

	.slider-color {
		-webkit-appearance: none;
		width: 100%;
		height: 10px;
		border-radius: 20px;
		background: #d3d3d3;
		outline: none;
		opacity:0.7;
		-webkit-transition: opacity .15s ease-in-out;
		transition: opacity .15s ease-in-out;
	}
	.slider-color:hover {
		opacity:1;
	}
	.slider-color::-webkit-slider-thumb {
		-webkit-appearance: none;
		appearance: none;
		width: 25px;
		height: 25px;
		border-radius: 50%;
		background: #000000;
		cursor: pointer;
	}
	.slider-color::-moz-range-thumb {
		width: 25px;
		height: 25px;
		border: 0;
		border-radius: 50%;
		background: #4CAF50;
		cursor: pointer;
	}

</style>

<?php
if ($action == 'create' && $mode == 'edit')
{
	load_fiche_titre($langs->trans("Newquestionnaire"));
	dol_fiche_head();
}
else
{
	if ($action !== 'answer')
		$head = questionnaire_prepare_head($object);
	$picto = dol_buildpath($path.'/img/object_questionnaire.png', 1);
	dol_fiche_head($head, 'card', $langs->trans("questionnaire"), 0, $picto, 1);
}

if ($action !== 'create')
{
	$shownav = $show_linkback = ($action === 'answer' ? false : true);
	if ($action === 'answer')
		$questionnaire_status_forced_key = 'questionnaireStatusValidatedShort';

		if(empty($object->questions))$object->loadQuestions($page);

	if (!empty($object->questions) && $action === 'answer')
	{
		foreach ($object->questions as $quest)
		{
			if ($quest->loadAnswers($fk_invitation) )
			{

				if (!empty($quest->answers))
				{
					$questionnaire_status_forced_key = 'questionnaireStatusClosed';
					$object->fk_statut = 2;
					break;
				}
			}
		}
	}

	_getBannerToAnswer($object, $action, true, $shownav, $show_linkback);
}

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$formconfirm = getFormConfirmquestionnaire($PDOdb, $form, $object, $action);
if (!empty($formconfirm))
	echo $formconfirm;

$TBS = new TTemplateTBS();
$TBS->TBS->protect = false;
$TBS->TBS->noerr = true;

if ($mode == 'edit')
	echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');
if ($action === 'answer')
	$mode = 'answer';

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
print $TBS->render('../tpl/card.tpl.php'
		, array() // Block
		, array(
		'object' => $object
		, 'view' => array(
			'mode' => $mode
			, 'action' => 'save'
			,'act'=>$action
			, 'urlcard' => dol_buildpath($path.'/toAnswer.php', 1)
			, 'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			, 'showRef' => ($action == 'create') ? $langs->trans('Draft') : ($mode === 'answer' ? '<div class="refid">'.$object->ref.'</div>' : $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', ''))
			, 'showTitle' => $formcore->texte('', 'title', $object->title, 80, 255)
			, 'showStatus' => $object->getLibStatut(1)
		//,'showLinkedObject' => (!empty($origin) && !empty($originid)) ? _showLinkedObject($origin, $originid) : _formSetObjectLinked($origin, $originid, false)
		)
		, 'langs' => $langs
		, 'user' => $user
		, 'conf' => $conf
		, 'Questionnaire' => array(
			'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
			, 'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
			, 'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
		)
		)
);

if ($mode == 'edit')
	echo $formcore->end_form();

print '<hr /><br /><br />';

// Print list of questions
if (empty($action) || $action === 'view' || $action === 'validate' || $action === 'delete' || $action === 'modif' || $action === 'clone')
{

	if (empty($object->questions))
		$object->loadQuestions();

	print '<div id="allQuestions">';

	if (!empty($object->questions))
	{
		foreach ($object->questions as &$q)
			print draw_question($q, $object->fk_statut);
	}

	print '</div>';

	if (empty($object->fk_statut))
	{

		$q = new Question($db);
		print '<div id="addQuestion" class="center"><br /><br />'.$form->selectarray('select_choice', $q->TTypes);
		print '<button class="butAction" id="butAddQuestion" name="butAddQuestion">Ajouter une question</button><br /><br /></div>';
	}
}
elseif ($action === 'apercu')
{
	if (empty($object->questions))
		$object->loadQuestions();
	print '<div id="allQuestions">';
	if (!empty($object->questions))
	{
		foreach ($object->questions as &$q)
			print draw_question_for_user($q).'<br />';
	}
	print '</div>';
}
elseif ($action === 'answer')
{
	print '<form name="answerQuestionnaire" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
	print '<input type="HIDDEN" name="fk_invitation" value="'.$fk_invitation.'"/>';
	print '<input type="HIDDEN" name="token" value="'.$token.'"/>';
	print '<input type="HIDDEN" name="page" value="'.$page.'"/>';
	print '<input type="HIDDEN" name="gotopage" value="'.$page.'"/>';
	print '<input type="HIDDEN" name="action" value="save_answer"/>';
	if (empty($object->questions))
		$object->loadQuestions($page);
	print '<div id="allQuestions">';
	draw_pagination($page, $object);
	if (!empty($object->questions))
	{
		foreach ($object->questions as &$q)
		{
			if (empty($q->answers))
				$q->loadAnswers($fk_invitation);
			print draw_question_for_user($q);
			print '<br /><br />';
		}
	}
	print '</div>';
    print '<div class="center">';
    if ($page > 1) print '<span class="paginationbt" ><a  href="#" page='.($page - 1).'><input class="butAction" name="previousPage" type="button" value="'.$langs->trans('PreviousPage').'"/></a></span>';
	print '<input class="butAction" name="subSave" type="SUBMIT" value="'.$langs->trans('SaveAnswer').'"/>';
    if ($page < $object->nbpages + 1) print '<span class="paginationbt" ><a  href="#" page='.($page + 1).'><input class="butAction" name="nextPage" type="button" value="'.$langs->trans('NextPage').'"/></a></span>';
	if($page == $object->nbpages + 1 || $object->nbpages ==1) print '<input name="subValid" type="SUBMIT" class="butAction"  value="'.$langs->trans('SaveAndClose').'"/>';
	print '</div>';
	print '</form>';
}

print '</div>';

// Boutons d'actions
if ($action !== 'answer')
{
	$urlToken = '';
	if (function_exists('newToken')) $urlToken = "&token=".newToken();
	print '<div class="tabsAction">';

	if (empty($object->fk_statut))
		print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=validate" class="butAction">'.$langs->transnoentities('Validate').'</a></div>';
	// On ne peut modifier le questionnaire que s'il n'existe aucune invitation)
	if ($object->fk_statut == 1 && empty($object->invitations))
		print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=modif" class="butAction">'.$langs->transnoentities('Modify').'</a></div>';
	print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=clone" class="butAction">'.$langs->transnoentities('ToClone').'</a></div>';
	if (!empty($user->rights->questionnaire->delete))
		print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.$urlToken.'&action=delete" class="butActionDelete">'.$langs->transnoentities('Delete').'</a></div>';

	print '</div>';
}

if (!empty($conf->related->enabled) && $object->id && $action !== 'answer')
{
	print '<div class="fichehalfleft">';
	$form->showLinkedObjectBlock($object);
	print '</div>';
	// Header car obligé de conserver l'action pour le hook related dans la fonction showLinkedObjectBlock
	if ($action === 'add_related_link' || $action === 'delete_related_link')
	{
		?>
		<script>document.location.href = "<?php echo $_SERVER['PHP_SELF'].'?id='.$object->id; ?>"</script>
		<?php
	}
}

if ((empty($action) || $action === 'view') && empty($object->fk_statut))
{
	?>

	<script type="text/javascript">

		$(document).ready(function () {

			$("#butAddQuestion").click(function () {

				var select_choice = $(this).prev('[name*=select_choice]');

				$.ajax({
					dataType: 'json'
					, url: "<?php echo dol_buildpath('/questionnaire/script/interface.php', 1) ?>"
					, data: {
						fk_questionnaire:<?php echo (int) $object->id ?>
						, type_question: select_choice.val()
						, put: "add-question"
					}

				}).done(function (res) {

					$('#allQuestions').append(res);
					setQuestionDivCSS();

				});
			});

			$(document).on('click', '[name*=butAddChoice]', function () {

				$btnAddChoice = $(this);
				var $div_question = $btnAddChoice.closest('div[type=question]');
				var id_question = $div_question.attr('id');
				id_question = id_question.replace('question', '');

				var choice_type = '';
				if ($btnAddChoice.attr('name').indexOf('Line') > 0)
					choice_type = 'line';
				else
					choice_type = 'column';

				$.ajax({
					dataType: 'json'
					, url: "<?php echo dol_buildpath('/questionnaire/script/interface.php', 1) ?>"
					, data: {
						fk_question: id_question
						, put: "add-choice"
						, type_choice: choice_type
					}

				}).done(function (res) {

					$btnAddChoice.before(res);

				});

			});

			$(document).on('click', '[name*=del_element_]', function () {

				var $div = $(this).closest('div[class*=element]')
				var type_object = $div.attr('type');
				var id_obj = $div.attr('id');
				id_obj = id_obj.replace('choice', '');
				id_obj = id_obj.replace('question', '');

				$.ajax({
					dataType: 'json'
					, url: "<?php echo dol_buildpath('/questionnaire/script/interface.php', 1) ?>"
					, data: {
						fk_object: id_obj
						, type_object: type_object
						, put: "del-object"
					}

				}).done(function (res) {

					$div.remove();
					setQuestionDivCSS();

				});

			});

			$(document).on('change', '[class=field]', function () {

				var $div = $(this).closest('div[class*=element]');
				var type_object = $div.attr('type');
				var id_obj = $div.attr('id');
				id_obj = id_obj.replace('choice', '');
				id_obj = id_obj.replace('question', '');
				var field = $(this).attr('name');

				var value = $(this).val();
				if ($(this).is(":checkbox") === true) {

					if ($(this).prop('checked') === true)
						value = 1;
					else
						value = 0;

				}

				$input = $(this);

				$input.css('background-color', 'grey');

				$.ajax({
					dataType: 'json'
					, url: "<?php echo dol_buildpath('/questionnaire/script/interface.php', 1) ?>"
					, data: {
						fk_object: id_obj
						, type_object: type_object
						, put: "set-field"
						, field: field
						, value: value
					}

				}).done(function (res) {

					$input.css('background-color', '');

				});

			});

		});

	</script>

	<?php
}
?>


<?php
if($action === 'apercu' || $action === 'answer') {

    $ql = new Questionlink($db);
    $links = $ql->loadLinks($id);
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
//         $('.el_linked').each(function(){
// 			$(this).hide();
//         });
    <?php

    foreach ($links as $qId => $cId){
    ?>
    	var choix = $('[value=<?php echo $cId; ?>]');
    	var question = $('#question<?php echo $qId; ?>');
    	var type = choix.attr('type');

    	if (choix.data('done') !== true)
    	{
    		if (type == 'checkbox')
    		{
    			choix.click(function(e) {
    				question = $('#question'+$(this).data('enable'));
    				//console.log($(this).data('enable'));
    				question.toggle(); // on fait apparaitre la question liée suivant la valeur de la checkbox
    				pos = question.css('position');
    				if (pos == 'absolute') question.css('position', 'static');
    				else question.css('position', 'absolute');
    			});
    			choix.attr('data-done', true);

    		} else if (type == 'radio') {
    			var name = choix.attr('name');

    			$('[name="'+name+'"').each(function(){ // on récupère tous les radio du groupe pour apliquer un comportement hide/show en fonction des paramètres
    				$(this).click(function(e) {
						if ($(this).data('enable') !== undefined) $('#question'+$(this).data('enable')).show().css('position', 'static'); // s'il y a une question liée, on l'affiche
						if (typeof $(this).data('disable') == 'string'){ // s'il y a plusieurs question à cacher

    						hideIt = $(this).data('disable').split('|');
    						hideIt.forEach(function(element) {
    							$('#question'+element).hide().css('position', 'absolute');
    						});

						} else if (typeof $(this).data('disable') == 'number') { // s'il n'y a qu'une autre question liée dans ce group de radio
							$('#question'+$(this).data('disable')).hide().css('position', 'absolute');
						}
        			});

    				$(this).attr('data-done', true);
    			});

    		} else if(choix.parent().find('option') !== undefined) { // cas du select
				options = choix.parent().find('option');
				params = choix.parent().data('params')

				array_val = [];
				options.each(function(){
					if (params[$(this).val()]['enable'].length > 0) $(this).attr('data-enable', params[$(this).val()]['enable']);//console.log($(this).val());
					if (params[$(this).val()]['disable'].length > 0) {
						$(this).attr('data-disable', params[$(this).val()]['disable'].join('|'));
					}
					$(this).attr('data-done', true);
				});

				choix.parent().attr('data-params', '');

				choix.parent().change(function(e){
					opt = $(this).find('option[value="'+$(this).val()+'"]');
					if (opt.data('enable') !== undefined) $('#question'+opt.data('enable')).show().css('position', 'static');
					if (typeof opt.data('disable') == 'string'){ // s'il y a plusieurs question à cacher

						hideIt = opt.data('disable').split('|');
						hideIt.forEach(function(element) {
							$('#question'+element).hide().css('position', 'absolute');
						});

					} else if (typeof opt.data('disable') == 'number') { // s'il n'y a qu'une autre question liée dans ce group d'option
						$('#question'+opt.data('disable')).hide().css('position', 'absolute');
					}
				});

    		}
    	}


	<?php
	}

	?>
	$('[data-enable]').each(function(e){
		console.log($(this));
    	if($(this).attr('checked') !== undefined){
    		$('#question'+$(this).data('enable')).removeClass('el_linked');
    	} else if($(this).attr('selected') !== undefined) $('#question'+$(this).data('enable')).removeClass('el_linked');
    });

	$('.el_linked').each(function(){
		$(this).hide().css('position', 'absolute');
    });

    });
	</script>
	<?php
}
?>
<script type="text/javascript">

<?php if ($action !== 'apercu' && $action !== 'answer') print 'setQuestionDivCSS();'; ?>

    function setQuestionDivCSS() {

        $(document).find('div[type=question]').each(function (i, item) {

            // Suppression anciennes classes
            $(item).removeClass('pair');
            $(item).removeClass('impair');

            // Ajout nouvelles classes
            if (i % 2 == 0)
                $(item).addClass('pair');
            else
                $(item).addClass('impair');

        });

    }

    $(document).ready(function () {

        // Echelles linéaires
        $(document).on('input', 'input[type=range]', function () {
            var qid = $(this).attr('name').replace('linearscal_q', '');
            $('span[id="val_linearscal_q' + qid + '"]').html($(this).val());
        });

        $(document).on('change', 'select[name=origin]', function () {

            $input_origin = $(this);
            $input_originid = $('select[name=originid]');
            $div_origin = $('#divoriginid');

            origin = $(this).val();
            $.ajax({
                dataType: 'json'
                , url: "<?php echo dol_buildpath('/questionnaire/script/interface.php', 1) ?>"
                , data: {
                    origin: origin
                    , get: "select-originid"
                }

            }).done(function (res) {

                $input_originid.remove();
                $input_origin.after(res);

            });
        });

		$(".paginationquest a").on('click', function(){
                       $("input[name='gotopage']").val($(this).attr('page'));

                       $("input[name='subSave']").click();

               });
        $(".paginationbt a").on('click', function(){
            $("input[name='gotopage']").val($(this).attr('page'));

            $("input[name='subSave']").click();

        });

    });

</script>

<?php
dol_htmloutput_events();
//llxFooter();
