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
 * 	\file		admin/questionnaire.php
 * 	\ingroup	questionnaire
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('abricot/includes/lib/admin.lib.php');
require_once '../lib/questionnaire.lib.php';
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/invitation.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Translations
$langs->load("questionnaire@questionnaire");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

/*
 * Actions
 */
$dirforimage=dol_buildpath('/questionnaire/public/img/');
if($action == 'updatelogo'){
    $varforimage='logo';

    if ($_FILES[$varforimage]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforimage]["name"],$reg))
        {
            $original_file=$reg[1];

            $isimage=image_format_supported($original_file);
            if ($isimage >= 0)
            {
                dol_syslog("Move file ".$_FILES[$varforimage]["tmp_name"]." to ".$dirforimage.$original_file);

                if (! is_dir($dirforimage))
                {
                    dol_mkdir($dirforimage);
                }
                $result=dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"],$dirforimage.$original_file,1,0,$_FILES[$varforimage]['error']);

                if ($result > 0)
                {
                    dolibarr_set_const($db, "QUESTIONNAIRE_COMPANY_LOGO",$original_file,'chaine',0,'',$conf->entity);

                    // Create thumbs of logo (Note that PDF use original file and not thumbs)
                    if ($isimage > 0)
                    {
                        // Create thumbs
                        //$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get...

                        // Create small thumb, Used on logon for example
                        $imgThumbSmall = vignette($dirforimage.$original_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
                        if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i',$imgThumbSmall,$reg))
                        {
                            $imgThumbSmall = $reg[1];    // Save only basename
                            dolibarr_set_const($db, "QUESTIONNAIRE_COMPANY_LOGO_SMALL",$imgThumbSmall,'chaine',0,'',$conf->entity);
                        }
                        else dol_syslog($imgThumbSmall);


                    }
                    else dol_syslog("ErrorImageFormatNotSupported",LOG_WARNING);
                }
                else if (preg_match('/^ErrorFileIsInfectedWithAVirus/',$result))
                {
                    $error++;
                    $langs->load("errors");
                    $tmparray=explode(':',$result);
                    setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus',$tmparray[1]), null, 'errors');
                }
                else
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
                }
            }
            else
            {
                $error++;
                $langs->load("errors");
                setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
            }
        }
    }
}
if ($action == 'removelogo')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $logofile=$dirforimage.$conf->global->QUESTIONNAIRE_COMPANY_LOGO;
    if(!empty($conf->global->QUESTIONNAIRE_COMPANY_LOGO))dol_delete_file($logofile);

    $logosmallfile=$dirforimage.'/thumbs/'.$conf->global->QUESTIONNAIRE_COMPANY_LOGO_SMALL;
    if(!empty($conf->global->QUESTIONNAIRE_COMPANY_LOGO_SMALL))dol_delete_file($logosmallfile);

    dolibarr_del_const($db, "QUESTIONNAIRE_COMPANY_LOGO",$conf->entity);
    dolibarr_del_const($db, "QUESTIONNAIRE_COMPANY_LOGO_SMALL",$conf->entity);

}

if ($action == 'updateMask') {
	
	$maskconstrefleter = GETPOST('maskconstrefletter', 'alpha');
	$maskrefletter = GETPOST('maskrefletter', 'alpha');
	if ($maskconstrefleter) $res = dolibarr_set_const($db, $maskconstrefleter, $maskrefletter, 'chaine', 0, '', $conf->entity);
		
	if (! $res > 0) $error ++;
	if (! $error) setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	else setEventMessage($langs->trans("Error"), 'errors');
	
} elseif($action === 'setmod') dolibarr_set_const($db, "QUESTIONNAIRE_ADDON", $value, 'chaine', 0, '', $conf->entity);
elseif($action === 'setmodanswer') dolibarr_set_const($db, "QUESTIONNAIRE_ANSWER_ADDON", $value, 'chaine', 0, '', $conf->entity);
else if (preg_match('/set_(.*)/',$action,$reg)) {

	$code=$reg[1];

	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else dol_print_error($db);
	
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "questionnaireSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = questionnaireAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104961Name"),
    1,
    "questionnaire@questionnaire"
);

if(!function_exists('setup_print_title')){
    print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
    exit;
}

// Setup page goes here
$dirmodels = array_merge(array (
		'/'
), ( array ) $conf->modules_parts['models']);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/questionnaire/");
	
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			
			while ( ($file = readdir($handle)) !== false ) {
				
				if (preg_match('/mod_questionnaire_/', $file) && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';
					
					$module = new $file();
					
					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
							continue;
							
							if ($module->isEnabled()) {
								$var = ! $var;
								print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
								print $module->info();
								print '</td>';
								
								// Show example of numbering module
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp))
									print '<div class="error">' . $langs->trans($tmp) . '</div>';
									elseif ($tmp == 'NotConfigured')
									print $langs->trans($tmp);
									else
										print $tmp;
										print '</td>' . "\n";
										
										print '<td align="center">';
										if ($conf->global->QUESTIONNAIRE_ADDON == "$file") {
											print img_picto($langs->trans("Activated"), 'switch_on');
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . $file . '">';
											print img_picto($langs->trans("Disabled"), 'switch_off');
											print '</a>';
										}
										print '</td>';
										
										$businesscase = new Questionnaire($db);
										$businesscase->initAsSpecimen();
										
										// Info
										$htmltooltip = '';
										$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
										$nextval = $module->getNextValue($user->id, 'contract', '', '');
										// Keep " on nextval
										if ("$nextval" != $langs->trans("NotAvailable")) {
											$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
											if ($nextval) {
												$htmltooltip .= $nextval . '<br>';
											} else {
												$htmltooltip .= $langs->trans($module->error) . '<br>';
											}
										}
										
										print '<td align="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';
										
										print "</tr>\n";
							}
				}
			}
			closedir($handle);
		}
	}
}
print '</table>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>';
print '<td align="center" width="200">&nbsp;</td>';
print '<td align="center" width="150">' . $langs->trans("Value") . '</td>';


print '<tr class="oddeven"><td><div class="inline-block"><label for="logo">'.$langs->trans("Logo").' (png,jpg)</label></div></td>';

if (! empty($conf->global->QUESTIONNAIRE_COMPANY_LOGO_SMALL)) {
    print '<td class="nocellnopadd" valign="middle" text-align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete")).'</a>';
    if (file_exists(dol_buildpath('/questionnaire/public/img/thumbs/'.$conf->global->QUESTIONNAIRE_COMPANY_LOGO_SMALL))) {
        print ' &nbsp; ';
        print '<img src="'.dol_buildpath('/questionnaire/public/img/thumbs/'.$conf->global->QUESTIONNAIRE_COMPANY_LOGO_SMALL,2).'"></td>';
    }
} else {
    print '<td><div class="warning">'.$langs->trans("warningImgDirWritable").'</div></td>';
}

print '<td valign="right" text-align="right" colspan="" class="nocellnopadd">';
print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<span style="display:inline-block;text-align:right;width:100%" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updatelogo">';
print '<input type="file" class="flat class=minwidth200" name="logo" id="logo">';
print '<input class="button" type="submit" name="btUpdateLogo" value="'.$langs->trans('Modify').'"/>';
print '</span>';
print '</form>';
print '</td>';
print '</tr>';

//Domain
$var = !$var;
print '<tr ' . $bc[$var] . '>';

print '<td>';
print $form->textwithpicto(
    '<label for="QUESTIONNAIRE_CUSTOM_DOMAIN">' . $langs->trans("UseCustomDomain") . '</label>',
    $langs->trans("CustomDomainHelp")
);
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="right" width="500">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_QUESTIONNAIRE_CUSTOM_DOMAIN">';
print '<input type="text" id="QUESTIONNAIRE_CUSTOM_DOMAIN" name="QUESTIONNAIRE_CUSTOM_DOMAIN" value="' . $conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN . '"  />';
print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';

print '</form>';
print '</td></tr>';


// Example with imput
setup_print_input_form_part('DEFAULT_AFTER_ANSWER_HTML', '', '', array(), 'textarea');


print '</table>';

print '<div class="warning">'.$langs->trans("warningHtAccess").'</div>';



/*
print "</table><br>\n";


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/answer/");
	
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			
			while ( ($file = readdir($handle)) !== false ) {
				
				if (preg_match('/mod_answer_/', $file) && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';
					
					$module = new $file();
					
					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
							continue;
							
							if ($module->isEnabled()) {
								$var = ! $var;
								print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
								print $module->info();
								print '</td>';
								
								// Show example of numbering module
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp))
									print '<div class="error">' . $langs->trans($tmp) . '</div>';
									elseif ($tmp == 'NotConfigured')
									print $langs->trans($tmp);
									else
										print $tmp;
										print '</td>' . "\n";
										
										print '<td align="center">';
										if ($conf->global->QUESTIONNAIRE_ANSWER_ADDON == "$file") {
											print img_picto($langs->trans("Activated"), 'switch_on');
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodanswer&amp;value=' . $file . '">';
											print img_picto($langs->trans("Disabled"), 'switch_off');
											print '</a>';
										}
										print '</td>';
										
										$businesscase = new InvitationUser($db);
										$businesscase->initAsSpecimen();
										
										// Info
										$htmltooltip = '';
										$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
										$nextval = $module->getNextValue($user->id, 'contract', '', '');
										// Keep " on nextval
										if ("$nextval" != $langs->trans("NotAvailable")) {
											$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
											if ($nextval) {
												$htmltooltip .= $nextval . '<br>';
											} else {
												$htmltooltip .= $langs->trans($module->error) . '<br>';
											}
										}
										
										print '<td align="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';
										
										print "</tr>\n";
							}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";*/

/*
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONSTNAME">';
print $form->selectyesno("CONSTNAME",$conf->global->CONSTNAME,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONSTNAME">';
print ajax_constantonoff('CONSTNAME');
print '</form>';
print '</td></tr>';

print '</table>';*/

llxFooter();

$db->close();