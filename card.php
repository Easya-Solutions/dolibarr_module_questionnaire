 <?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

//if(empty($user->rights->questionnaire->read)) accessforbidden();

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_invitation = GETPOST('fk_invitation');
$title = GETPOST('title');
$origin = GETPOST('origin');
$originid = GETPOST('originid');

$invitation = new InvitationUser($db);
$res = $invitation->load($fk_invitation);

if($action === 'answer' && empty($res) || $invitation->date_limite_reponse < strtotime(date('Y-m-d'))) accessforbidden();

$form = new Form($db);

$mode = 'view';
if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Questionnaire($db);

if (!empty($id)) $object->load($id);
elseif (!empty($ref)) $object->load('', $ref);

$object->loadInvitations();

$hookmanager->initHooks(array('questionnairecard', 'globalcard'));

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
//var_dump($_REQUEST);
// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	switch ($action) {
		case 'create':
			//print_form_add_question();
			$mode = 'edit';
			break;
			
		case 'save':
			$object->title = $title; // Set standard attributes
			$object->origin = $origin;
			$object->originid = $originid;
			$object->fk_user_author = $user->id;
			
			if ($error > 0)
			{
				$mode = 'edit';
				break;
			}
			
			$object->save(true);
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			
			break;
		case 'settitle':
			$object->title = $title;
			$object->save();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'setorigin':
			$object->origin = $origin;
			$object->originid = $originid;
			$object->save(true);
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'save_answer':
			
			// Suppression anciennes réponses
			$object->deleteAllAnswersUser($fk_invitation);
			
			$TAnswer = GETPOST('TAnswer');
			foreach($_REQUEST as $k=>&$v) {
				
				if($k === 'TAnswer') {
					
					foreach($v as $fk_question=>&$content) {
					
						// Ajout nouvelles réponses
						if(is_array($content) && !empty($content)) {
							foreach($content as $pos => &$answer_user) {
								
								if(empty($answer_user))continue;
								
								$answer = new Answer($db);
								$answer->fk_invitation_user = GETPOST('fk_invitation');
								$answer->fk_question = $fk_question;
								
								if(strpos($pos, '_') !== false ){
									$TDetailRep = explode('_', $pos);
									$answer->fk_choix = $TDetailRep[0];
									$answer->fk_choix_col = $TDetailRep[1];
									$answer->value = $answer_user;
								}
								else if(strpos($answer_user, '_') !== false ) {
									
									$TDetailRep = explode('_', $answer_user);
									$answer->fk_choix = $TDetailRep[0];
									$answer->fk_choix_col = $TDetailRep[1];
								} else $answer->fk_choix = $answer_user;
								
								$answer->save();
								
							}
						} elseif(!is_array($content) && !empty($content)) {
							$answer = new Answer($db);
							$answer->fk_invitation_user = GETPOST('fk_invitation');
							$answer->fk_question = $fk_question;
							$answer->value = $content;
							$answer->save();
						}
						
					}
					
				} elseif(strpos($k, 'linearscal_q') !== false || strpos($k, 'date_q') !== false || strpos($k, 'time_q') !== false) { // Ajout réponses non gérées dans le TAnswer (car pas possible ou galère en js)
					
					// Pour ne pas faire 4 fois l'enregistrement pour les dates
					if((strpos($k, 'date_q') !== false && (strpos($k, 'day') !== false || strpos($k, 'month') !== false || strpos($k, 'year') !== false))
						|| (strpos($k, 'time_q') !== false && strpos($k, 'min') !== false)) continue;
					
					// Suppression anciennes réponses
					$fk_question = strtr($k, array('linearscal_q'=>'', 'date_q'=>'', 'time_q'=>'', 'hour'=>'', 'min'=>''));
					
					$answer = new Answer($db);
					$answer->fk_question = $fk_question;
					$answer->fk_invitation_user = GETPOST('fk_invitation');
					$answer->value = $v;
					
					$year = GETPOST('date_q'.$fk_question.'year');
					$month = GETPOST('date_q'.$fk_question.'month');
					$day = GETPOST('date_q'.$fk_question.'day');
					
					$hour = GETPOST('time_q'.$fk_question.'hour');
					$min = GETPOST('time_q'.$fk_question.'min');
					
					if(strpos($k, 'date_q') !== false && !empty($year)) $answer->value = strtotime($year.'-'.$month.'-'.$day);
					if(strpos($k, 'time_q') !== false && (!empty($hour) || !empty($min))) $answer->value = ((int)$hour * 60 * 60) + ((int)$min * 60);
					
					if(!empty($answer->value)) $answer->save();
					
				}
				
			}
			
			if(isset($_REQUEST['subSave'])) {
				setEventMessage($langs->trans('questionnaireSaved'));
				header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id.'&action=answer&fk_invitation='.$fk_invitation);
			} else { // Validation finale
				header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id.'&action=validate_answers&fk_invitation='.$fk_invitation);
			}
			exit;
			break;
		case 'modif':
		case 'clone':
		case 'delete':
		case 'validate':
		case 'validate_answers':
			$formconfirm = getFormConfirmquestionnaire($form, $object, $action);
			if($action === 'validate_answers') $action = 'answer';
			break;
		case 'confirm_modif':
			$object->setDraft();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'confirm_validate':
			$object->setValid();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'confirm_validate_answers':
			$invitation_user = new InvitationUser($db);
			

			$invitation_user->loadBy(array('rowid'=>GETPOST('fk_invitation')));
			$isOkForValidation = $object->isOkForValidation(GETPOST('fk_invitation'));
			
			if($isOkForValidation) {
			
				$invitation_user->setValid();
				setEventMessage($langs->trans('questionnaireValidated'));
				header('Location: '.dol_buildpath('/questionnaire/list.php', 1).'?action=to_answer');
				
			} else {
				setEventMessage($langs->trans('questionnaireNotValidated'), 'errors');
				header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$id.'&action=answer&fk_invitation='.$fk_invitation);
			}
			exit;
			break;
		case 'confirm_delete':
			if(!empty($user->rights->questionnaire->delete)) {
				$object->delete($user);
				header('Location: '.dol_buildpath('/questionnaire/list.php', 1));
			} else {
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
			}
			
			exit;
			break;
		case 'confirm_clone':
			$res = $object->cloneObj();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$res);
			exit;
			break;
		// link from llx_element_element
		case 'dellink':
			$object->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
	}
}


/**
 * View
 */

$title=$langs->trans("Module104961Name");
llxHeader('',$title);

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
	if($action !== 'answer') $head = questionnaire_prepare_head($object);
	$picto = dol_buildpath('/questionnaire/img/object_questionnaire.png', 1);
	dol_fiche_head($head, 'card', $langs->trans("questionnaire"), 0, $picto, 1);
}

if($action !== 'create') {
	$shownav = $show_linkback = ($action === 'answer' ? false : true);
	if($action === 'answer') $questionnaire_status_forced_key = 'questionnaireStatusValidatedShort';
	_getBanner($object, $action, true, $shownav, $show_linkback);
}

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$formconfirm = getFormConfirmquestionnaire($PDOdb, $form, $object, $action);
if (!empty($formconfirm)) echo $formconfirm;

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');
if($action === 'answer') $mode = 'answer';

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/card.tpl.php'
	,array() // Block
	,array(
		'object'=>$object
		,'view' => array(
			'mode' => $mode
			,'action' => 'save'
			,'act'=>$action
			,'urlcard' => dol_buildpath('/questionnaire/card.php', 1)
			,'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			,'showRef' => ($action == 'create') ? $langs->trans('Draft') : ($mode === 'answer' ? '<div class="refid">'.$object->ref.'</div>' : $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', ''))
			,'showTitle' => $formcore->texte('', 'title', $object->title, 80, 255)
			,'showStatus' => $object->getLibStatut(1)
			//,'showLinkedObject' => (!empty($origin) && !empty($originid)) ? _showLinkedObject($origin, $originid) : _formSetObjectLinked($origin, $originid, false)
		)
		,'langs' => $langs
		,'user' => $user
		,'conf' => $conf
		,'Questionnaire' => array(
			'STATUS_DRAFT' => Questionnaire::STATUS_DRAFT
			,'STATUS_VALIDATED' => Questionnaire::STATUS_VALIDATED
			,'STATUS_CLOSED' => Questionnaire::STATUS_CLOSED
		)
	)
);

if ($mode == 'edit' && $action !='create') echo $formcore->end_form();

print '<hr /><br /><br />';

// Print list of questions
if(empty($action) || $action === 'view' || $action === 'validate' || $action === 'delete' || $action === 'modif' || $action === 'clone') {
	
	if(empty($object->questions)) $object->loadQuestions();
	
	print '<div id="allQuestions">';
	
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) print draw_question($q, $object->fk_statut);
	}
	
	print '</div>';
	
	if(empty($object->fk_statut)) {
		
		$q = new Question($db);
		print '<div id="addQuestion" class="center"><br /><br />'.$form->selectarray('select_choice', $q->TTypes);
		print '<button class="butAction" id="butAddQuestion" name="butAddQuestion">Ajouter une question</button><br /><br /></div>';
		
	}
	
} elseif($action === 'apercu') {
	if(empty($object->questions)) $object->loadQuestions();
	print '<div id="allQuestions">';
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) print draw_question_for_user($q).'<br />';
	}
	print '</div>';
	
} elseif($action === 'answer') {
	print '<form name="answerQuestionnaire" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
	print '<input type="HIDDEN" name="fk_invitation" value="'.$fk_invitation.'"/>';
	print '<input type="HIDDEN" name="action" value="save_answer"/>';
	if(empty($object->questions)) $object->loadQuestions();
	print '<div id="allQuestions">';
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) {
			if(empty($q->answers)) $q->loadAnswers($fk_invitation);
			print draw_question_for_user($q).'<br />';
			print '<br /><b><hr style="height:1px;border:none;color:#333;background-color:#333;" /></b><br />';
		}
	}
	print '</div>';
	print '<div class="center"><input class="butAction" name="subSave" type="SUBMIT" value="Enregistrer"/><input name="subValid" type="SUBMIT" class="butAction"/>';
	print '</form>';
}

print '</div>';

// Boutons d'actions
if($action !== 'answer' && $action != 'create') {
	
	print '<div class="tabsAction">';
	
	if(empty($object->fk_statut)) print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=validate" class="butAction">'.$langs->transnoentities('Validate').'</a></div>';
	// On ne peut modifier le questionnaire que s'il n'existe aucune invitation)
	if($object->fk_statut == 1 ) print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=modif" class="butAction">'.$langs->transnoentities('Modify').'</a></div>';
	print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=clone" class="butAction">'.$langs->transnoentities('ToClone').'</a></div>';
	if(!empty($user->rights->questionnaire->delete)) print '<div class="inline-block divButAction"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=delete" class="butActionDelete">'.$langs->transnoentities('Delete').'</a></div>';
	
	print '</div>';
	
}else if($action == 'create'){
	print '<div class="tabsAction">';
	print '<input type="submit" value="'.$langs->transnoentities('CreateDraft').'" class="butAction" />
	
	
	<input type="button" onclick="javascript:history.go(-1)" value="'.$langs->transnoentities('Cancel').'" class="butActionDelete"/>';
	print '</div>';
	echo $formcore->end_form();
}

if (!empty($conf->related->enabled) && $object->id && $action !== 'answer') {
	print '<div class="fichehalfleft">';
	$form->showLinkedObjectBlock($object);
	print '</div>';
	// Header car obligé de conserver l'action pour le hook related dans la fonction showLinkedObjectBlock
	if($action === 'add_related_link' || $action === 'delete_related_link') {
		?>
			<script>document.location.href="<?php echo $_SERVER['PHP_SELF'].'?id='.$object->id; ?>"</script>
		<?php
	}
}

if((empty($action) || $action === 'view') && empty($object->fk_statut)) {

	?>
	
	<script type="text/javascript">
	
		$(document).ready(function() {
	
			$("#butAddQuestion").click(function() {
				
				var select_choice = $(this).prev('[name*=select_choice]');
				
				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
							,data:{
									fk_questionnaire:<?php echo (int)$object->id ?>
									,type_question:select_choice.val()
									,put:"add-question"
								}
	
				}).done(function(res) {
	
					$('#allQuestions').append(res);
					setQuestionDivCSS();
	
				});
			});
	
			$(document).on('click', '[name*=butAddChoice]', function() {
	
				$btnAddChoice = $(this);
				var $div_question = $btnAddChoice.closest('div[type=question]');
				var id_question = $div_question.attr('id');
				id_question = id_question.replace('question', '');
	
				var choice_type = '';
				if($btnAddChoice.attr('name').indexOf('Line') > 0) choice_type = 'line';
				else choice_type = 'column';
				
				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
							,data:{
									fk_question:id_question
									,put:"add-choice"
									,type_choice:choice_type
								}
	
				}).done(function(res) {
	
					$btnAddChoice.before(res);
	
				});
				
			});
	
			$(document).on('click', '[name*=del_element_]', function() {
	
				var $div = $(this).closest('div[class*=element]')
				var type_object = $div.attr('type');
				var id_obj = $div.attr('id');
				id_obj = id_obj.replace('choice', '');
				id_obj = id_obj.replace('question', '');
				
				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
							,data:{
									fk_object:id_obj
									,type_object:type_object
									,put:"del-object"
								}
	
				}).done(function(res) {
	
					$div.remove();
					setQuestionDivCSS();
	
				});
				
			});
			
			$(document).on('change', '[class=field]', function() {
	
				var $div = $(this).closest('div[class*=element]');
				var type_object = $div.attr('type');
				var id_obj = $div.attr('id');
				id_obj = id_obj.replace('choice', '');
				id_obj = id_obj.replace('question', '');
				var field = $(this).attr('name');
				
				var value = $(this).val();
				if($(this).is(":checkbox") === true) {
					
					if($(this).prop('checked') === true) value = 1;
					else value = 0; 
					
				}
				
				$input = $(this);
				
				$input.css('background-color','grey');
				
				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
							,data:{
									fk_object:id_obj
									,type_object:type_object
									,put:"set-field"
									,field:field
									,value:value
								}
	
				}).done(function(res) {
	
					$input.css('background-color','');
	
				});
				
			});

			$(document).on('click', '[name*=link_element_]', function() {
				var $btn = $(this);
				var choice = $btn.data('choice');
				var $div_question = $btn.closest('div[type=question]');
				var id_question = $div_question.attr('id');
				id_question = id_question.replace('question', '');
				
				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
					,data:{
						fk_questionnaire:<?php echo $id; ?>
						,fk_question: id_question
 						,fk_choix:choice
 						,get:"next-questions"
					}
	
				}).done(function(result) {
					console.log(result);
					$('#sel_'+choice).html(result);
	
				});

			});

			$(document).on('change', '.select_question', function() {
				var $select = $(this);
				var choice = $select.prev().data('choice');
				var id_question = $select.val();
				var questionnaire = $select.data('questionnaire');

				$.ajax({
					dataType:'json'
					,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
					,data:{
						put:"link-question"
						,fk_questionnaire:questionnaire
						,fk_question: id_question
 						,fk_choix:choice
					}
				
				}).done(function(result) {
					console.log(result);
					if(result.success == true)
					{
						
						$('#sel_'+choice).html();
					}
				});
				
			});
			
		});
		
	</script>
	
	<?php

}

?>

<script type="text/javascript">

	<?php if($action !== 'apercu' && $action !== 'answer') print 'setQuestionDivCSS();'; ?>

	function setQuestionDivCSS() {

		$(document).find('div[type=question]').each(function(i, item) {
			
			// Suppression anciennes classes
			$(item).removeClass('pair');
			$(item).removeClass('impair');

			// Ajout nouvelles classes
			if(i % 2 == 0) $(item).addClass('pair');
			else $(item).addClass('impair');
			
		});
		
	}

	$(document).ready(function () {
		
		// Echelles linéaires
		$(document).on('input', 'input[type=range]', function() {
			var qid = $(this).attr('name').replace('linearscal_q', '');
			$('span[id="val_linearscal_q'+qid+'"]').html($(this).val());
		});

		$(document).on('change', 'select[name=origin]', function() {
			
			$input_origin = $(this);
			$input_originid = $('select[name=originid]');
			$div_origin = $('#divoriginid');
			
			origin = $(this).val();
			$.ajax({
				dataType:'json'
				,url:"<?php echo dol_buildpath('/questionnaire/script/interface.php',1) ?>"
						,data:{
								origin:origin
								,get:"select-originid"
							}

			}).done(function(res) {

				$input_originid.remove();
				$input_origin.after(res);

			});
		});

	});

</script>

<?php
	
llxFooter();