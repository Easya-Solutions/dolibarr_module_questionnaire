<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

chdir(__DIR__);
define('INC_FROM_CRON_SCRIPT', true);
    
require '../config.php';
	

	
global $db;

	
$sql = 'UPDATE '.MAIN_DB_PREFIX.'quest_invitation_user SET fk_element = fk_user, type_element="user"
		WHERE fk_user !=0';

$db->query($sql);