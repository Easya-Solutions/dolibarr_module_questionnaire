<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}

global $db;

dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/class/question_link.class.php');
dol_include_once('/questionnaire/class/answer.class.php');
dol_include_once('/questionnaire/class/choice.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');

$o=new Questionnaire($db);
$o->init_db_by_vars();

$o=new Question($db);
$o->init_db_by_vars();

$o=new Questionlink($db);
$o->init_db_by_vars();

$o=new Answer($db);
$o->init_db_by_vars();

$o=new Choice($db);
$o->init_db_by_vars();

$o=new InvitationUser($db);
$o->init_db_by_vars();