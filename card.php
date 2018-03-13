 <?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');

//if(empty($user->rights->questionnaire->read)) accessforbidden();

$langs->load('questionnaire@questionnaire');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');

$form = new Form($db);

$mode = 'view';
if (empty($user->rights->questionnaire->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Questionnaire($db);

if (!empty($id)) $object->load($id);
elseif (!empty($ref)) $object->loadBy($ref, 'ref');

$hookmanager->initHooks(array('questionnairecard', 'globalcard'));

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

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
			$object->title = GETPOST('title'); // Set standard attributes
			
			if ($error > 0)
			{
				$mode = 'edit';
				break;
			}
			
			$object->save(true);
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			
			break;
		case 'save_answer':
			//var_dump($_REQUEST);exit;
			$TAnswer = GETPOST('TAnswer');
			foreach($_REQUEST as $k=>&$v) {
				
				if($k === 'TAnswer') {
					
					foreach($v as $fk_question=>&$content) {
					
						// Suppression anciennes réponses
						Answer::deleteAllAnswersUser($user->id, $fk_question);
						
						// Ajout nouvelles réponses
						if(is_array($content) && !empty($content)) {
							foreach($content as &$answer_user) {
								
								$answer = new Answer($db);
								$answer->fk_user = $user->id;
								$answer->fk_question = $fk_question;
								if(strpos($answer_user, '_') !== false) {
									$TDetailRep = explode('_', $answer_user);
									$answer->fk_choix = $TDetailRep[0];
									$answer->fk_choix_col = $TDetailRep[1];
								} else $answer->fk_choix = $answer_user;
								$answer->save();
								
							}
						} elseif(!is_array($content)) {
							$answer = new Answer($db);
							$answer->fk_user = $user->id;
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
					Answer::deleteAllAnswersUser($user->id, $fk_question);
					
					$answer = new Answer($db);
					$answer->fk_user = $user->id;
					$answer->value = $v;
					if(strpos($k, 'date_q') !== false) $answer->value = strtotime(GETPOST('date_q'.$fk_question.'year').'-'.GETPOST('date_q'.$fk_question.'month').'-'.GETPOST('date_q'.$fk_question.'day'));
					if(strpos($k, 'time_q') !== false) $answer->value = ((int)GETPOST('time_q'.$fk_question.'hour') * 60 * 60) + ((int)GETPOST('time_q'.$fk_question.'min') * 60);
					$answer->fk_question = $fk_question;
					$answer->save();
					
				}
				
			}
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id.'&action=answer');
			exit;
			break;
		case 'confirm_clone':
			$object->cloneObject();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'modif':
			if (!empty($user->rights->questionnaire->write)) $object->setDraft();
				
			break;
		case 'validate' :
			$error = 0;
			
			// We verifie whether the object is provisionally numbering
			$ref = substr($object->ref, 1, 4);
			if ($ref == 'PROV') {
				$numref = $object->getNextNumRef();
				
				if (empty($numref)) {
					$error ++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$numref = $object->ref;
			}
			
			$text = $langs->trans('ConfirmValidateQuestionnaire', $numref);
			/*if (! empty($conf->notification->enabled)) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
				$notify = new Notify($db);
				$text .= '<br>';
				$text .= $notify->confirmMessage('QUESTIONNAIRE_VALIDATE', $object->socid, $object);
			}*/
			
			if (! $error) $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateQuestionnaire'), $text, 'confirm_validate', '', 0, 1, 200, 520);
			
			break;
			
		case 'confirm_validate':
			if (!empty($user->rights->questionnaire->write)) $object->setValid();
			
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'confirm_delete':
			if (!empty($user->rights->questionnaire->write)) $object->delete();
			
			header('Location: '.dol_buildpath('/questionnaire/list.php', 1));
			exit;
			break;
		// link from llx_element_element
		case 'dellink':
			$object->generic->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/questionnaire/card.php', 1).'?id='.$object->id);
			exit;
			break;
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
	$head = questionnaire_prepare_head($object);
	$picto = 'generic';
	dol_fiche_head($head, 'card', $langs->trans("questionnaire"), 0, $picto);
}

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$formconfirm = getFormConfirmquestionnaire($PDOdb, $form, $object, $action);
if (!empty($formconfirm)) echo $formconfirm;

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_questionnaire');

$linkback = '<a href="'.dol_buildpath('/questionnaire/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/card.tpl.php'
	,array() // Block
	,array(
		'object'=>$object
		,'view' => array(
			'mode' => $mode
			,'action' => 'save'
			,'urlcard' => dol_buildpath('/questionnaire/card.php', 1)
			,'urllist' => dol_buildpath('/questionnaire/list.php', 1)
			,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '')
			,'showTitle' => $formcore->texte('', 'title', $object->title, 80, 255)
			,'showStatus' => $object->getLibStatut(1)
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

if ($mode == 'edit') echo $formcore->end_form();

//if ($mode == 'view' && $object->id) $somethingshown = $form->showLinkedObjectBlock($object->generic);

// Print list of questions
if(empty($action) || $action === 'view') {
	if(empty($object->questions)) $object->loadQuestions();
	print '<div id="allQuestions">';
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) print draw_question($q);
	}
	print '</div>';
	
	if($action !== 'create') {
		$q = new Question($db);
		print '<br /><div class="center">'.$form->selectarray('select_choice', $q->TTypes);
		print '<button class="butAction" id="butAddQuestion" name="butAddQuestion">Ajouter une question</button></div>';
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
	print '<input type="HIDDEN" name="action" value="save_answer"/>';
	if(empty($object->questions)) $object->loadQuestions();
	print '<div id="allQuestions">';
	if(!empty($object->questions)) {
		foreach($object->questions as &$q) {
			if(empty($q->answers)) $q->loadAnswers($user->id);
			print draw_question_for_user($q).'<br />';
			print '<br /><b><hr style="height:1px;border:none;color:#333;background-color:#333;" /></b><br />';
		}
	}
	print '</div>';
	print '<div class="center"><input class="butAction" name="subSave" type="SUBMIT" value="Enregistrer"/><input class="butAction" name="subSave" type="SUBMIT" value="Valider"/></div>';
	print '</form>';
}

if(empty($action) || $action === 'view') {

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

			setQuestionDivCSS();
			
		});
		
	</script>
	
	<?php

}

?>

<script type="text/javascript">

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
		
	});

</script>

<?php
	
llxFooter();