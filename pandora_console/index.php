<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches
if (function_exists ('mb_internal_encoding')) {
	mb_internal_encoding ("UTF-8");
}

// Set to 1 to do not check for installer or config file (for development!).
// Activate gives more error information, not useful for production sites
$develop_bypass = 0;

if ($develop_bypass != 1) {
	// If no config file, automatically try to install
	if (! file_exists ("include/config.php")) {
		if (! file_exists ("install.php")) {
			$url = explode('/', $_SERVER['REQUEST_URI']);
			$flag_url =0;
			foreach ($url as $key => $value) {
				if (strpos($value, 'index.php') !== false || $flag_url) {
					$flag_url=1;
					unset($url[$key]);
				}
				else if(strpos($value, 'enterprise') !== false || $flag_url){
					$flag_url=1;
					unset($url[$key]);
				}
			}
			$config["homeurl"] = rtrim(join("/", $url),"/");
			$config["homeurl_static"] = $config["homeurl"];
			$login_screen = 'error_noconfig';
			$ownDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
			$config['homedir'] = $ownDir;
			require('general/error_screen.php');
			exit;
		}
		else {
			include ("install.php");
			exit;
		}
	}
	
	if (filesize("include/config.php") == 0) {
		include ("install.php");
		exit;
	}

	if (isset($_POST["rename_file"])){
		$rename_file_install = (bool)$_POST["rename_file"];
		if ($rename_file_install) {
			$salida_rename = rename("install.php", "install_old.php");
		}
	}

	// Check for installer presence
	if (file_exists ("install.php")) {
		$login_screen = 'error_install';
		require('general/error_screen.php');
		exit;
	}
	// Check perms for config.php
	if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
		if ((substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0600") &&
			(substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0660") &&
			(substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0640")) {
			$url = explode('/', $_SERVER['REQUEST_URI']);
			$flag_url =0;
			foreach ($url as $key => $value) {
				if (strpos($value, 'index.php') !== false || $flag_url) {
					$flag_url=1;
					unset($url[$key]);
				}
				else if(strpos($value, 'enterprise') !== false || $flag_url){
					$flag_url=1;
					unset($url[$key]);
				}
			}
			$config["homeurl"] = rtrim(join("/", $url),"/");
			$config["homeurl_static"] = $config["homeurl"];
			$ownDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;	
			$config['homedir'] = $ownDir;
			$login_screen = 'error_perms';
			require('general/error_screen.php');
			exit;
		}
	}
}

if ((! file_exists ("include/config.php")) || (! is_readable ("include/config.php"))) {
	$login_screen = 'error_noconfig';
	require('general/error_screen.php');
	exit;
}

//////////////////////////////////////
//// PLEASE DO NOT CHANGE ORDER //////
//////////////////////////////////////
require_once ("include/config.php");
require_once ("include/functions_config.php");

if (isset($config["error"])) {
	$login_screen = $config["error"];
	require('general/error_screen.php');
	exit;
}

// If metaconsole activated, redirect to it
if ($config['metaconsole'] == 1 && $config['enterprise_installed'] == 1) {
	header ("Location: " . $config['homeurl'] . "enterprise/meta");
}

if (file_exists (ENTERPRISE_DIR . "/include/functions_login.php")) {
	include_once (ENTERPRISE_DIR . "/include/functions_login.php");
}
////////////////////////////////////////

if (!empty ($config["https"]) && empty ($_SERVER['HTTPS'])) {
	$query = '';
	if (sizeof ($_REQUEST))
		//Some (old) browsers don't like the ?&key=var
		$query .= '?1=1';
	
	//We don't clean these variables up as they're only being passed along
	foreach ($_GET as $key => $value) {
		if ($key == 1)
			continue;
		$query .= '&'.$key.'='.$value;
	}
	foreach ($_POST as $key => $value) {
		$query .= '&'.$key.'='.$value;
	}
	$url = ui_get_full_url($query);
	
	// Prevent HTTP response splitting attacks
	// http://en.wikipedia.org/wiki/HTTP_response_splitting
	$url = str_replace ("\n", "", $url);
	
	header ('Location: '.$url);
	exit; //Always exit after sending location headers
}

// Pure mode (without menu, header and footer).
$config["pure"] = (bool) get_parameter ("pure");

// Auto Refresh page (can now be disabled anywhere in the script)
if (get_parameter ("refr"))
	$config["refr"] = (int) get_parameter ("refr");

$delete_file = get_parameter("del_file");
if ($delete_file == 'yes_delete'){
	$salida_delete = shell_exec('rm /var/www/html/pandora_console/install.php');
}

ob_start ();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' .
	"\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
echo '<head>' . "\n";

//This starts the page head. In the call back function, things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');

// Enterprise main 
enterprise_include ('index.php');

echo '<script type="text/javascript">';
	echo 'var dispositivo = navigator.userAgent.toLowerCase();';
	echo 'if( dispositivo.search(/iphone|ipod|ipad|android/) > -1 ){';
    	echo 'document.location = "'. $config["homeurl"] .'mobile";  }';
echo '</script>';

// This tag is included in the buffer passed to ui_process_page_head so
// technically it can be stripped
echo '</head>' . "\n";

require_once ("include/functions_themes.php");
ob_start ('ui_process_page_body');

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
$page = $sec2; //Reference variable for old time sake

$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

$process_login = false;

// Update user password
$change_pass = get_parameter_post('renew_password', 0);
	
if ($change_pass == 1) {

	$password_old = (string) get_parameter_post ('old_password', '');
	$password_new = (string) get_parameter_post ('new_password', '');
	$password_confirm = (string) get_parameter_post ('confirm_new_password', '');
	$id = (string) get_parameter_post ('login', '');
	
	$changed_pass = login_update_password_check ($password_old, $password_new, $password_confirm, $id);
}

$minor_release_message = false;
$searchPage = false;
$search = get_parameter_get("head_search_keywords");
if (strlen($search) > 0) {
	$config['search_keywords'] = io_safe_input(trim(io_safe_output(get_parameter('keywords'))));
	// If not search category providad, we'll use an agent search
	$config['search_category'] = get_parameter('search_category', 'all');
	if (($config['search_keywords'] != 'Enter keywords to search') && (strlen($config['search_keywords']) > 0))
		$searchPage = true;
}

// Login process
if (! isset ($config['id_user'])) {
	if (isset ($_GET["login"])) {
		include_once('include/functions_db.php'); //Include it to use escape_string_sql function
		
		$config["auth_error"] = ""; //Set this to the error message from the authorization mechanism
		$nick = get_parameter_post ("nick"); //This is the variable with the login
		$pass = get_parameter_post ("pass"); //This is the variable with the password
		$nick = db_escape_string_sql($nick);
		$pass = db_escape_string_sql($pass);
		
		//Since now, only the $pass variable are needed
		unset ($_GET['pass'], $_POST['pass'], $_REQUEST['pass']);
		
		// If the auth_code exists, we assume the user has come through the double auth page
		if (isset ($_POST['auth_code'])) {
			$double_auth_success = false;
			
			// The double authentication is activated and the user has surpassed the first step (the login).
			// Now the authentication code provided will be checked.
			if (isset ($_SESSION['prepared_login_da'])) {
				if (isset ($_SESSION['prepared_login_da']['id_user'])
						&& isset ($_SESSION['prepared_login_da']['timestamp'])) {
					
					// The user has a maximum of 5 minutes to introduce the double auth code
					$dauth_period = SECONDS_2MINUTES;
					$now = time();
					$dauth_time = $_SESSION['prepared_login_da']['timestamp'];
					
					if ($now - $dauth_period < $dauth_time) {
						// Nick
						$nick = $_SESSION["prepared_login_da"]['id_user'];
						// Code
						$code = (string) get_parameter_post ("auth_code");
						
						if (!empty($code)) {
							$result = validate_double_auth_code($nick, $code);
							
							if ($result === true) {
								// Double auth success
								$double_auth_success = true;
							}
							else {
								// Screen
								$login_screen = 'double_auth';
								// Error message
								$config["auth_error"] = __("Invalid code");
								
								if (!isset($_SESSION['prepared_login_da']['attempts']))
									$_SESSION['prepared_login_da']['attempts'] = 0;
								$_SESSION['prepared_login_da']['attempts']++;
							}
						}
						else {
							// Screen
							$login_screen = 'double_auth';
							// Error message
							$config["auth_error"] = __("The code shouldn't be empty");
							
							if (!isset($_SESSION['prepared_login_da']['attempts']))
								$_SESSION['prepared_login_da']['attempts'] = 0;
							$_SESSION['prepared_login_da']['attempts']++;
						}
					}
					else {
						// Expired login
						unset ($_SESSION['prepared_login_da']);
						
						// Error message
						$config["auth_error"] = __('Expired login');
					}
				}
				else {
					// If the code doesn't exist, remove the prepared login
					unset ($_SESSION['prepared_login_da']);
					
					// Error message
					$config["auth_error"] = __('Login error');
				}
			}
			// If $_SESSION['prepared_login_da'] doesn't exist, the user have to do the login again
			else {
				// Error message
				$config["auth_error"] = __('Login error');
			}
			
			// Remove the authenticator code
			unset ($_POST['auth_code'], $code);
			
			if (!$double_auth_success) {
				$login_failed = true;
				require_once ('general/login_page.php');
				db_pandora_audit("Logon Failed", "Invalid double auth login: "
					.$_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_ADDR']);
				while (@ob_end_flush ());
				exit ("</html>");
			}
		}
		$login_button_saml = get_parameter("login_button_saml", false);
		if (isset ($double_auth_success) && $double_auth_success) {
			// This values are true cause there are checked before complete the 2nd auth step
			$nick_in_db = $_SESSION["prepared_login_da"]['id_user'];
			$expired_pass = false;
		}
		else if (($config['auth'] == 'saml') && ($login_button_saml)) {
			include_once(ENTERPRISE_DIR . "/include/auth/saml.php");
			
			$saml_user_id = saml_process_user_login();
			
			$nick_in_db = $saml_user_id;
			if (!$nick_in_db) {
				require_once($config['saml_path'] . 'simplesamlphp/lib/_autoload.php');
				$as = new SimpleSAML_Auth_Simple('PandoraFMS');
				$as->logout();
			}
		}
		else {
			// process_user_login is a virtual function which should be defined in each auth file.
			// It accepts username and password. The rest should be internal to the auth file.
			// The auth file can set $config["auth_error"] to an informative error output or reference their internal error messages to it
			// process_user_login should return false in case of errors or invalid login, the nickname if correct
			$nick_in_db = process_user_login ($nick, $pass);
			
			$expired_pass = false;
			
			if (($nick_in_db != false) && ((!is_user_admin($nick)
				|| $config['enable_pass_policy_admin']))
				&& (file_exists (ENTERPRISE_DIR . "/load_enterprise.php"))
				&& ($config['enable_pass_policy'])) {
				include_once(ENTERPRISE_DIR . "/include/auth/mysql.php");
				
				$blocked = login_check_blocked($nick);
				
				if ($blocked) {
					require_once ('general/login_page.php');
					db_pandora_audit("Password expired", "Password expired: ".io_safe_output($nick), io_safe_output($nick));
					while (@ob_end_flush ());
					exit ("</html>");
				}
				
				//Checks if password has expired
				$check_status = check_pass_status($nick, $pass);
				
				switch ($check_status) {
					case PASSSWORD_POLICIES_FIRST_CHANGE: //first change
					case PASSSWORD_POLICIES_EXPIRED: //pass expired
						$expired_pass = true;
						login_change_password($nick, '',$check_status);
						break;
				}
			}
		}
		
		if (($nick_in_db !== false) && $expired_pass) {
			//login ok and password has expired
			
			require_once ('general/login_page.php');
			db_pandora_audit("Password expired",
				"Password expired: " . io_safe_output($nick), $nick);
			while (@ob_end_flush ());
			exit ("</html>");
		}
		else if (($nick_in_db !== false) && (!$expired_pass)) {
			//login ok and password has not expired
			
			// Double auth check
			if ((!isset ($double_auth_success) || !$double_auth_success) && is_double_auth_enabled($nick_in_db)) {
				// Store this values in the session to know if the user login was correct
				$_SESSION['prepared_login_da'] = array(
						'id_user' => $nick_in_db,
						'timestamp' => time(),
						'attempts' => 0
					);
				
				// Load the page to introduce the double auth code
				$login_screen = 'double_auth';
				require_once ('general/login_page.php');
				while (@ob_end_flush ());
				exit ("</html>");
			}
			
			//login ok and password has not expired
			$process_login = true;
			
			if (is_user_admin($nick))
				echo "<script type='text/javascript'>var process_login_ok = 1;</script>";
			else
				echo "<script type='text/javascript'>var process_login_ok = 0;</script>";
			
			if (!isset($_GET["sec2"]) && !isset($_GET["sec"])) {
				// Avoid the show homepage when the user go to
				// a specific section of pandora
				// for example when timeout the sesion
				
				unset ($_GET["sec2"]);
				$_GET["sec"] = "general/logon_ok";
				$home_page ='';
				if (isset($nick)) {
					$user_info = users_get_user_by_id($nick);
					$home_page = io_safe_output($user_info['section']);
					$home_url = $user_info['data_section'];
					if ($home_page != '') {
						switch ($home_page) {
							case 'Event list':
								$_GET["sec"] = "eventos";
								$_GET["sec2"] = "operation/events/events";
								break;
							case 'Group view':
								$_GET["sec"] = "estado";
								$_GET["sec2"] = "operation/agentes/group_view";
								break;
							case 'Alert detail':
								$_GET["sec"] = "estado";
								$_GET["sec2"] = "operation/agentes/alerts_status";
								break;
							case 'Tactical view':
								$_GET["sec"] = "estado";
								$_GET["sec2"] = "operation/agentes/tactical";
								break;
							case 'Default':
								$_GET["sec"] = "general/logon_ok";
								break;
							case 'Dashboard':
								$_GET["sec"] = "reporting";
								$_GET["sec2"] = ENTERPRISE_DIR.'/dashboard/main_dashboard';
								$id_dashboard_select =
									db_get_value('id', 'tdashboard', 'name', $home_url);
								$_GET['id_dashboard_select'] = $id_dashboard_select;
								$_GET['d_from_main_page'] = 1;
								break;
							case 'Visual console':
								$_GET["sec"] = "network";
								$_GET["sec2"] = "operation/visual_console/index";
								break;
							case 'Other':
								$home_url = io_safe_output($home_url);
								$url_array = parse_url($home_url);
								parse_str ($url_array['query'], $res);
								foreach ($res as $key => $param) {
									$_GET[$key] = $param;
								}
								break;
						}
					}
					else {
						$_GET["sec"] = "general/logon_ok";
					}
				}
				
			}
			
			db_logon ($nick_in_db, $_SERVER['REMOTE_ADDR']);
			$_SESSION['id_usuario'] = $nick_in_db;
			$config['id_user'] = $nick_in_db;
			
			// Check if connection goes through F5 balancer. If it does, then don't call config_prepare_session() or user will be back to login all the time
      $prepare_session = true;
      foreach ($_COOKIE as $key=>$value) {
              if (preg_match('/BIGipServer*/',$key) ) {
                      $prepare_session = false;
              }
      }
      if ($prepare_session){
              config_prepare_session();
      }

			if (is_user_admin($config['id_user'])) {
				// PHP configuration values
				$PHPupload_max_filesize = config_return_in_bytes(ini_get('upload_max_filesize'));
				$PHPmemory_limit = config_return_in_bytes(ini_get('memory_limit'));
				$PHPmax_execution_time = ini_get('max_execution_time');
				
				if ($PHPmax_execution_time !== '0') {
					set_time_limit(0);
				}
				
				$PHPupload_max_filesize_min = config_return_in_bytes('800M');
				
				if ($PHPupload_max_filesize < $PHPupload_max_filesize_min) {
					ini_set('upload_max_filesize', config_return_in_bytes('800M'));
				}
				
				$PHPmemory_limit_min = config_return_in_bytes('500M');
				
				if ($PHPmemory_limit < $PHPmemory_limit_min && $PHPmemory_limit !== '-1') {
					ini_set('memory_limit', config_return_in_bytes('500M'));
				}
				
				set_time_limit((int)$PHPmax_execution_time);
				ini_set('upload_max_filesize', $PHPupload_max_filesize);
				ini_set('memory_limit', $PHPmemory_limit);
			}
			
			//==========================================================
			//-------- SET THE CUSTOM CONFIGS OF USER ------------------
			
			config_user_set_custom_config();
			//==========================================================
			
			//Remove everything that might have to do with people's passwords or logins
			unset ($pass, $login_good);
			
			$user_language = get_user_language($config['id_user']);
			
			$l10n = NULL;
			if (file_exists ('./include/languages/' . $user_language . '.mo')) {
				$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$user_language.'.mo'));
				$l10n->load_tables();
			}
		}
		else { //login wrong
			$blocked = false;
			
			if ((!is_user_admin($nick) || $config['enable_pass_policy_admin']) && file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
				$blocked = login_check_blocked($nick);
			}
			$nick_usable = io_safe_output($nick);
			if (!$blocked) {
				if (file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
					login_check_failed($nick); //Checks failed attempts
				}
				$login_failed = true;
				require_once ('general/login_page.php');
				db_pandora_audit("Logon Failed", "Invalid login: ".$nick_usable, $nick_usable);
				while (@ob_end_flush ());
				exit ("</html>");
			}
			else {
				require_once ('general/login_page.php');
				db_pandora_audit("Logon Failed", "Invalid login: ".$nick_usable, $nick_usable);
				while (@ob_end_flush ());
				exit ("</html>");
			}
		}
		// Form the url
		$query_params_redirect = $_GET;
		// Visual console do not want sec2
		if($home_page == 'Visual console') unset($query_params_redirect["sec2"]);
		$redirect_url = '?logged=1';
		foreach ($query_params_redirect as $key => $value) {
			if ($key == "login") continue;
			$redirect_url .= '&'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
		}
		header("Location: ".$config['homeurl']."index.php".$redirect_url);
	}
	// Hash login process
	elseif (isset ($_GET["loginhash"])) {
		$loginhash_data = get_parameter("loginhash_data", "");
		$loginhash_user = str_rot13(get_parameter("loginhash_user", ""));
		
		if ($config["loginhash_pwd"] != "" && $loginhash_data == md5($loginhash_user.io_output_password($config["loginhash_pwd"]))) {
			db_logon ($loginhash_user, $_SERVER['REMOTE_ADDR']);
			$_SESSION['id_usuario'] = $loginhash_user;
			$config["id_user"] = $loginhash_user;
		}
		else {
			require_once ('general/login_page.php');
			db_pandora_audit("Logon Failed (loginhash", "", "system");
			while (@ob_end_flush ());
			exit ("</html>");
		}
	}
	// There is no user connected
	else {
		if ($config['enterprise_installed']) {
			enterprise_include_once ('include/functions_reset_pass.php');
		}

		$correct_pass_change = (boolean)get_parameter('correct_pass_change', 0);
		$reset = (boolean)get_parameter('reset', 0);
		$first = (boolean)get_parameter('first', 0);
		$reset_hash = get_parameter('reset_hash', "");

		if ($correct_pass_change) {
			$correct_reset_pass_process = "";
			$process_error_message = "";
			$pass1 = get_parameter('pass1');
			$pass2 = get_parameter('pass2');
			$id_user = get_parameter('id_user');

			if ($pass1 == $pass2) {
				$res = update_user_password ($id_user, $pass1);
				if ($res) {
					
					db_process_sql_insert ('tsesion', array('id_sesion' => '','id_usuario' => $id_user,'ip_origen' => $_SERVER['REMOTE_ADDR'],'accion' => 'Reset&#x20;change',
					'descripcion' => 'Successful reset password process ','fecha' => date("Y-m-d H:i:s"),'utimestamp' => time()));
					
					$correct_reset_pass_process = __('Password changed successfully');

					register_pass_change_try($id_user, 1);
				}
				else {
					register_pass_change_try($id_user, 0);

					$process_error_message = __('Failed to change password');
				}
			}
			else {
				register_pass_change_try($id_user, 0);

				$process_error_message = __('Passwords must be the same');
			}
			require_once ('general/login_page.php');
		}
		else {
			if ($reset_hash != "") {
				$hash_data = explode(":::", $reset_hash);
				$id_user = $hash_data[0];
				$codified_hash = $hash_data[1];

				$db_reset_pass_entry = db_get_value_filter('reset_time', 'treset_pass', array('id_user' => $id_user, 'cod_hash' => $id_user . ":::" . $codified_hash));
				$process_error_message = "";

				if ($db_reset_pass_entry) {
					if (($db_reset_pass_entry + SECONDS_2HOUR) < time()) {
						register_pass_change_try($id_user, 0);
						$process_error_message = __('Too much time since password change request');
						delete_reset_pass_entry($id_user);
						require_once ('general/login_page.php');
					}
					else {
						delete_reset_pass_entry($id_user);
						require_once ('enterprise/include/process_reset_pass.php');
					}
				}
				else {
					register_pass_change_try($id_user, 0);
					$process_error_message = __('This user has not requested a password change');
					require_once ('general/login_page.php');
				}
			}
			else {
				if (!$reset) {
					require_once ('general/login_page.php');
				}
				else {
					$user_reset_pass = get_parameter('user_reset_pass', "");
					$error = "";
					$mail = "";
					$show_error = false;

					if (!$first) {
						if ($reset) {
							if ($user_reset_pass == '') {
								$reset = false;
								$error = __('Id user cannot be empty');
								$show_error = true;
							}
							else {
								$check_user = check_user_id($user_reset_pass);

								if (!$check_user) {
									$reset = false;
									register_pass_change_try($user_reset_pass, 0);
									$error = __('Error in reset password request');
									$show_error = true;
								}
								else {
									$check_mail = check_user_have_mail($user_reset_pass);

									if (!$check_mail) {
										$reset = false;
										register_pass_change_try($user_reset_pass, 0);
										$error = __('This user doesn\'t have a valid email address');
										$show_error = true;
									}
									else {
										$mail = $check_mail;
									}
								}
							}
						}

						if (!$reset) {
							if ($config['enterprise_installed']) {
								require_once ('enterprise/include/reset_pass.php');
							}
						}
						else {
							
							$cod_hash = $user_reset_pass . "::::" . md5(rand(10, 1000000) . rand(10, 1000000) . rand(10, 1000000));

							$subject = '[' . get_product_name() . '] '.__('Reset password');
							$body = __('This is an automatically sent message for user ');
							$body .= ' "<strong>' . $user_reset_pass . '"</strong>';
							$body .= '<p />';
							$body .= __('Please click the link below to reset your password');
							$body .= '<p />';
							$body .= '<a href="' . $config['homeurl'] . 'index.php?reset_hash=' . $cod_hash . '">' . __('Reset your password') . '</a>';
							$body .= '<p />';
							$body .= get_product_name();
							$body .= '<p />';
							$body .= '<em>'.__('Please do not reply to this email.').'</em>';
							
							$result = send_email_to_user($mail, $body, $subject);
							
							$process_error_message = "";
							if (!$result) {
								$process_error_message = __('Error at sending the email');
							}
							else {
								send_token_to_db($user_reset_pass, $cod_hash);
							}

							require_once ('general/login_page.php');
						}
					}
					else {
						require_once ('enterprise/include/reset_pass.php');
					}
				}
			}
		}
		
		while (@ob_end_flush ());
		exit ("</html>");
	}
}
else {
	if (isset($_GET["loginhash_data"])) {

        $loginhash_data = get_parameter("loginhash_data", "");
        $loginhash_user = str_rot13(get_parameter("loginhash_user", ""));
        $iduser = $_SESSION["id_usuario"];
        //logoff_db ($iduser, $_SERVER["REMOTE_ADDR"]); check why is not available
        unset($_SESSION["id_usuario"]);
        unset($iduser);

        if ($config["loginhash_pwd"] != "" && $loginhash_data == md5($loginhash_user.io_output_password($config["loginhash_pwd"]))) {
            db_logon ($loginhash_user, $_SERVER['REMOTE_ADDR']);
            $_SESSION['id_usuario'] = $loginhash_user;
            $config["id_user"] = $loginhash_user;
        }
        else {
            require_once ('general/login_page.php');
            db_pandora_audit("Logon Failed (loginhash", "", "system");
            while (@ob_end_flush ());
            exit ("</html>");
        }
    }

	$user_in_db = db_get_row_filter('tusuario', 
		array('id_user' => $config['id_user']), '*');
	if ($user_in_db == false) {
		//logout
		$_REQUEST = array ();
		$_GET = array ();
		$_POST = array ();
		$config["auth_error"] = __("User doesn\'t exist.");
		$iduser = $_SESSION["id_usuario"];
		logoff_db ($iduser, $_SERVER["REMOTE_ADDR"]);
		unset($_SESSION["id_usuario"]);
		unset($iduser);
		require_once ('general/login_page.php');
		while (@ob_end_flush ());
		exit ("</html>");
	}
	else {
		if (((bool) $user_in_db['is_admin'] === false) && 
				((bool) $user_in_db['not_login'] === true)) {
			//logout
			$_REQUEST = array ();
			$_GET = array ();
			$_POST = array ();
			$config["auth_error"] = __("User only can use the API.");
			$iduser = $_SESSION["id_usuario"];
			logoff_db ($iduser, $_SERVER["REMOTE_ADDR"]);
			unset($_SESSION["id_usuario"]);
			unset($iduser);
			require_once ('general/login_page.php');
			while (@ob_end_flush ());
			exit ("</html>");
		}
	}
}

/* Enterprise support */
if (file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
	include_once (ENTERPRISE_DIR . "/load_enterprise.php");
}

// Log off
if (isset ($_GET["bye"])) {
	include ("general/logoff.php");
	$iduser = $_SESSION["id_usuario"];
	db_logoff ($iduser, $_SERVER['REMOTE_ADDR']);

	$_SESSION = array();
	session_destroy();
	header_remove("Set-Cookie");
	setcookie(session_name(), $_COOKIE[session_name()], time() - 4800, "/");

	if ($config['auth'] == 'saml') {
		require_once($config['saml_path'] . 'simplesamlphp/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple('PandoraFMS');
		$as->logout();
	}
	while (@ob_end_flush ());
	exit ("</html>");
}

clear_pandora_error_for_header();

//----------------------------------------------------------------------
// EXTENSIONS
//----------------------------------------------------------------------
/**
 * Load the basic configurations of extension and add extensions into menu.
 * Load here, because if not, some extensions not load well, I don't why.
 */

$config['logged'] = false;
extensions_load_extensions ($process_login);

// Check for update manager messages
if (license_free() && is_user_admin ($config['id_user']) && 
	(($config['last_um_check'] < time()) || 
	(!isset($config['last_um_check'])))) {
		
	require_once("include/functions_update_manager.php");
	update_manager_download_messages ();
}

if ($process_login) {
	 /* Call all extensions login function */
	extensions_call_login_function ();
	
	unset($_SESSION['new_update']);
	
	require_once("include/functions_update_manager.php");
	enterprise_include_once("include/functions_update_manager.php");
	
	if ($config["autoupdate"] == 1) {
		if (enterprise_installed()) {
			$result = update_manager_check_online_enterprise_packages_available();
		}
		else {
			$result = update_manager_check_online_free_packages_available();
		}
		if ($result)
			$_SESSION['new_update'] = 'new';
		
	}
	
	//Set the initial global counter for chat.
	users_get_last_global_counter('session');
	
	$config['logged'] = true;
}
//----------------------------------------------------------------------

//Get old parameters before navigation.
$old_sec = '';
$old_sec2 = '';
$old_page = '';
if (isset($_SERVER['HTTP_REFERER']))
	$old_page = $_SERVER['HTTP_REFERER'];
$chunks = explode('?', $old_page);
if (count($chunks) == 2) {
	$chunks = explode('&', $chunks[1]);
	
	foreach ($chunks as $chunk) {
		if (strstr($chunk, 'sec=') !== false) {
			$old_sec = str_replace('sec=', '', $chunk);
		}
		if (strstr($chunk, 'sec2=') !== false) {
			$old_sec = str_replace('sec2=', '', $chunk);
		}
	}
}

$_SESSION['new_chat'] = false;
if ($old_sec2 == 'operation/users/webchat') {
	users_get_last_global_counter('session');
}

if ($page == 'operation/users/webchat') {
	//Reload the global counter.
	users_get_last_global_counter('session');
}

if (isset($_SESSION['global_counter_chat']))
	$old_global_counter_chat = $_SESSION['global_counter_chat'];
else
	$old_global_counter_chat = users_get_last_global_counter('return');
$now_global_counter_chat = users_get_last_global_counter('return');

if ($old_global_counter_chat != $now_global_counter_chat) {
	if (!users_is_last_system_message())
		$_SESSION['new_chat'] = true;
}

// Pop-ups display order:
// 1) login_required (timezone and email)
// 2) identification (newsletter and register)
// 3) last_message   (update manager message popup
// 4) login_help     (online help, enterpirse version, forums, documentation)
if (is_user_admin ($config['id_user']) && 
	(!isset($config['initial_wizard']) || $config['initial_wizard'] != 1)) {
	include_once ("general/login_required.php");
}
if (get_parameter ('login', 0) !== 0) {
	// Display news dialog
	include_once("general/news_dialog.php");
	
	// Display login help info dialog
	// If it's configured to not skip this
	$display_previous_popup = false;
	if (license_free() && is_user_admin ($config['id_user']) && $config['initial_wizard'] == 1) {
		$display_previous_popup = include_once("general/login_identification_wizard.php");
		if ($display_previous_popup === false) {
			$display_previous_popup = include_once("general/last_message.php");
		}
	}
	if ((!isset($config['skip_login_help_dialog']) || $config['skip_login_help_dialog'] == 0) && 
		$display_previous_popup === false && 
		$config['initial_wizard'] == 1) {
			
		include_once("general/login_help_dialog.php");
	}

	$php_version = phpversion();
	$php_version_array = explode('.', $php_version);
	if($php_version_array[0] < 7){
		include_once("general/php7_message.php");
	}
}

// Header
if ($config["pure"] == 0) {
	if ($config['classic_menu']) {
		echo '<div id="container"><div id="head">';
		require ("general/header.php");
		echo '</div><div id="menu">';
		require ("general/main_menu.php");
		echo '</div>';
		echo '<div style="padding-left:100px;" id="page">';
	}
	else {
		echo '<div id="container"><div id="head">';
		require ("general/header.php");
		echo '</div><div id="page" style="margin-top:20px;"><div id="menu">';
		require ("general/main_menu.php");
		echo '</div>';
	}
}
else {
	echo '<div id="main_pure">';
	// Require menu only to build structure to use it in ACLs
	require ("operation/menu.php");
	require ("godmode/menu.php");
}

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
session_write_close ();


// Main block of content
if ($config["pure"] == 0) {
	echo '<div id="main">';
}



// Page loader / selector
if ($searchPage) {
	require ('operation/search_results.php');
}
else {
	if ($page != "") {
		
		$main_sec = get_sec($sec);
		if ($main_sec == false) {
			if ($sec == 'extensions')
				$main_sec = get_parameter('extension_in_menu');
			else
				if ($sec == 'gextensions')
					$main_sec = get_parameter('extension_in_menu');
				else
					$main_sec = $sec;
			$sec = $sec2;
			$sec2 = '';
		}
		$page .= '.php';
		
		// Enterprise ACL check
		if (enterprise_hook ('enterprise_acl',
			array ($config['id_user'], $main_sec, $sec, true,$sec2)) == false) {
			
			require ("general/noaccess.php");
			
		}
		else {
			$sec = $main_sec;
			if (file_exists ($page)) {
				if (! extensions_is_extension ($page)) {
					
					require_once($page);
				}
				else {
					if ($sec[0] == 'g')
						extensions_call_godmode_function (basename ($page));
					else
						extensions_call_main_function (basename ($page));
				}
			} 
			else {
				ui_print_error_message(__('Sorry! I can\'t find the page!'));
			}
		}
	} 
	else {
		//home screen chosen by the user
		$home_page ='';
		if (isset($config['id_user'])) {
			$user_info = users_get_user_by_id($config['id_user']);
			$home_page = io_safe_output($user_info['section']);
			$home_url = $user_info['data_section'];
		}

		if ($home_page != '') {
			switch ($home_page) {
				case 'Event list':
					$_GET['sec'] = 'eventos';
					$_GET['sec2'] = 'operation/events/events';
					break;
				case 'Group view':
					$_GET['sec'] = 'view';
					$_GET['sec2'] = 'operation/agentes/group_view';
					break;
				case 'Alert detail':
					$_GET['sec'] = 'view';
					$_GET['sec2'] = 'operation/agentes/alerts_status';
					break;
				case 'Tactical view':
					$_GET['sec'] = 'view';
					$_GET['sec2'] = 'operation/agentes/tactical';
					break;
				case 'Default':
					$_GET['sec2'] = 'general/logon_ok';
					break;
				case 'Dashboard':
					$id_dashboard = db_get_value('id', 'tdashboard', 'name', $home_url);
					$str = 'sec=reporting&sec2='.ENTERPRISE_DIR.'/dashboard/main_dashboard&id='.$id_dashboard.'&d_from_main_page=1';
					parse_str($str, $res);
					foreach ($res as $key => $param) {
						$_GET[$key] = $param;
					}
					break;
				case 'Visual console':
					$id_visualc = db_get_value('id', 'tlayout', 'name', $home_url);
					if (($home_url == '') || ($id_visualc == false)) {
						$str = 'sec=network&sec2=operation/visual_console/index&refr=60';
					}
					else 
						$str = 'sec=network&sec2=operation/visual_console/render_view&id='.$id_visualc .'&refr=60';
					parse_str($str, $res);
					foreach ($res as $key => $param) {
						$_GET[$key] = $param;
					}
					break;
				case 'Other':
					$home_url = io_safe_output($home_url);
					$url_array = parse_url($home_url);
					parse_str ($url_array['query'], $res);
					foreach ($res as $key => $param) {
						$_GET[$key] = $param;
					}
					break;
			}
			if (isset($_GET['sec2'])) {
				$file = $_GET['sec2'] . '.php';
				// Translate some secs
				$main_sec = get_sec($_GET['sec']);
				$_GET['sec'] = $main_sec == false ? $_GET['sec'] : $main_sec;
				if (
					!file_exists ($file) ||
					(
						$_GET['sec2'] != 'general/logon_ok' &&
						enterprise_hook ('enterprise_acl',
							array ($config['id_user'], $_GET['sec'], $_GET['sec2'], true,
								isset($_GET['sec3']) ? $_GET['sec3'] : '')
						) == false
					)
				) {
					unset($_GET['sec2']);
					require ("general/noaccess.php");
				}
				else {
					require($file);
				}
			} else {
				require ("general/noaccess.php");
			}
		}
		else {
			require("general/logon_ok.php");
		}
	}
}

if ($config["pure"] == 0) {
	echo '<div style="clear:both"></div>';
	echo '</div>'; // main
	echo '<div style="clear:both">&nbsp;</div>';
	echo '</div>'; // page (id = page)
}
else {
	echo "</div>"; // main_pure
}


if ($config["pure"] == 0) {
	echo '</div>'; //container div
	echo '<div style="clear:both"></div>';
	echo '<div id="foot">';
	require ("general/footer.php");
	echo '</div>';
}

/// Clippy function
require_once('include/functions_clippy.php');
clippy_start($sec2);

while (@ob_end_flush ());

db_print_database_debug ();
echo '</html>';

$run_time = format_numeric (microtime (true) - $config['start_time'], 3);
echo "\n<!-- Page generated in $run_time seconds -->\n";

// Values from PHP to be recovered from JAVASCRIPT
require('include/php_to_js_values.php');


?>

<script type="text/javascript" language="javascript">
	//Initial load of page
	$(document).ready(adjustFooter);
	
	//Every resize of window
	$(window).resize(adjustFooter);
	
	//Every show/hide call may need footer re-layout
	(function() {
		var oShow = jQuery.fn.show;
		var oHide = jQuery.fn.hide;
		
		jQuery.fn.show = function () {
			var rv = oShow.apply(this, arguments);
			adjustFooter();
			return rv;
		};
		jQuery.fn.hide = function () {
			var rv = oHide.apply(this, arguments);
			adjustFooter();
			return rv;
		};
	})();
	
	function force_run_register () {
		run_identification_wizard (1, 0, 0);
	}
	function force_run_newsletter () {
		run_identification_wizard (0, 1, 0);
	}
	function first_time_identification () {
		run_identification_wizard (-1, -1, 1);
	}

	var times_fired_register_wizard = 0;

	function run_identification_wizard (register, newsletter , return_button) {
		if (times_fired_register_wizard) {
			$(".ui-dialog-titlebar-close").show();
			
			//Reset some values				
			$("#label-email-newsletter").hide();
			$("#text-email-newsletter").hide();
			$("#required-email-newsletter").hide();
			$("#checkbox-register").removeAttr('checked');
			$("#checkbox-newsletter").removeAttr('checked');
			
			// Hide or show parts
			if (register == 1) {
				$("#checkbox-register").show();
				$("#label-register").show ();
			}
			if (register == 0) {
				$("#checkbox-register").attr ('style', 'display: none !important');
				$("#label-register").hide ();
			}
			if (newsletter == 1) {
				$("#checkbox-newsletter").show();
				$("#label-newsletter").show ();
			}
			if (newsletter == 0) {
				$("#checkbox-newsletter").attr ('style', 'display: none !important');
				$("#label-newsletter").hide ();
			}
			$("#login_accept_register").dialog('open');
		}
		else {
			$(".ui-dialog-titlebar-close").show();
			$("#container").append('<div class="id_wizard"></div>');
			jQuery.post ("ajax.php",
				{"page": "general/login_identification_wizard",
				 "not_return": 1,
				 "force_register": register,
				 "force_newsletter": newsletter,
				 "return_button": return_button},
				function (data) {
					$(".id_wizard").hide ()
						.empty ()
						.append (data);
				},
				"html"
			);
		}
		times_fired_register_wizard++;
		return false;
	}

	//Dynamically assign footer position and width.
	function adjustFooter() {
		/*
		if (document.readyState !== 'complete' || $('#container').position() == undefined) {
			return;
		}
		// minimum top value (upper limit) for div#foot
		var ulim = $('#container').position().top + $('#container').outerHeight(true);
		// window height. $(window).height() returns wrong value on Opera and Google Chrome.
		var wh = document.documentElement.clientHeight;
		// save div#foot's height for latter use
		var h = $('#foot').height();
		// new top value for div#foot
		var t = (ulim + $('#foot').outerHeight() > wh) ? ulim : wh - $('#foot').outerHeight();
		/*
		if ($('#foot').position().top != t) {
			$('#foot').css({ position: "absolute", top: t, left: $('#foot').offset().left});
			$('#foot').height(h);
		}
		if ($('#foot').width() !=  $(window).width()) {
			$('#foot').width($(window).width());
		}
		*/
	}
</script>
