<?php
/*
    PufferPanel - A Minecraft Server Management Panel
    Copyright (c) 2013 Dane Everitt
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/.
 */
session_start();
require_once('../../../../core/framework/framework.core.php');

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token'), true) !== true){
	$core->framework->page->redirect('../../../../index.php');
}

setcookie("__TMP_pp_admin_updateglobal", json_encode($_POST), time() + 30, '/', $core->framework->settings->get('cookie_website'));

if(!isset($_POST['smail_method'], $_POST['sendmail_email'], $_POST['postmark_api_key'], $_POST['mandrill_api_key'], $_POST['mailgun_api_key']))
	$core->framework->page->redirect('../global.php?error=smail_method|sendmail_email|postmark_api_key|mandrill_api_key|mailgun_api_key&tab=email');
	
if(!in_array($_POST['smail_method'], array('php', 'postmark', 'mandrill', 'mailgun')))
	$core->framework->page->redirect('../global.php?error=smail_method&tab=email');
	
if(!filter_var($_POST['sendmail_email'], FILTER_VALIDATE_EMAIL))
	$core->framework->page->redirect('../global.php?error=sendmail_email&tab=email');
	
if($_POST['smail_method'] != 'php' && empty($_POST[$_POST['smail_method'].'_api_key']))
	$core->framework->page->redirect('../global.php?error=smail_method|'.$_POST['smail_method'].'_api_key&tab=email');
	
$mysql->prepare("UPDATE `acp_settings` SET `setting_val` = ? WHERE `setting_ref` = 'sendmail_method'")->execute(array($_POST['smail_method']));
$mysql->prepare("UPDATE `acp_settings` SET `setting_val` = ? WHERE `setting_ref` = 'sendmail_email'")->execute(array($_POST['sendmail_email']));
$mysql->prepare("UPDATE `acp_settings` SET `setting_val` = ? WHERE `setting_ref` = 'postmark_api_key'")->execute(array($_POST['postmark_api_key']));
$mysql->prepare("UPDATE `acp_settings` SET `setting_val` = ? WHERE `setting_ref` = 'mandrill_api_key'")->execute(array($_POST['mandrill_api_key']));
$mysql->prepare("UPDATE `acp_settings` SET `setting_val` = ? WHERE `setting_ref` = 'mailgun_api_key'")->execute(array($_POST['mailgun_api_key']));

$core->framework->page->redirect('../global.php?tab=email');

?>