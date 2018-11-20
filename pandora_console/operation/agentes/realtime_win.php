<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


if (! isset($_SESSION['id_usuario'])) {
	session_start();
	//session_write_close();
}

// Global & session management
require_once ('../../include/config.php');
require_once ($config['homedir'] . '/include/auth/mysql.php');
require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_modules.php');
require_once ($config['homedir'] . '/include/functions_agents.php');
require_once ($config['homedir'] . '/include/functions_tags.php');
require_once ($config['homedir'] . '/include/functions_extensions.php');
check_login ();

// Metaconsole connection to the node
$server_id = (int) get_parameter("server");
if (is_metaconsole() && !empty($server_id)) {
	$server = metaconsole_get_connection_by_id($server_id);
	
	// Error connecting
	if (metaconsole_connect($server) !== NOERR) {
		echo "<html>";
			echo "<body>";
				ui_print_error_message(__('There was a problem connecting with the node'));
			echo "</body>";
		echo "</html>";
		exit;
	}
}

$user_language = get_user_language ($config['id_user']);
if (file_exists ('../../include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('../../include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css"/>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php
		// Parsing the refresh before sending any header
		$refresh = (int) get_parameter ("refresh", -1);
		if ($refresh > 0) {
			$query = ui_get_url_refresh (false);
			echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
		}
		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo __("%s Realtime Module Graph", get_product_name());?></title>
		<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
		<link rel="stylesheet" href="../../include/styles/jquery-ui.min.css" type="text/css" />
		<script type='text/javascript' src='../../include/javascript/pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery-3.3.1.min.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery-ui.min.js'></script>
		<?php
            //Include the javascript for the js charts library
            include_once($config["homedir"] . '/include/graphs/functions_flot.php');
            include_javascript_dependencies_flot_graph();
		?>
	</head>
	<body bgcolor="#ffffff" style='background:#ffffff;'>
		<?php
            if (!check_acl ($config["id_user"], 0, "AR")) {
                require ($config['homedir'] . "/general/noaccess.php");
                exit;
            }
            $config['extensions'] = extensions_get_extensions (false, '../../');
            if (!extensions_is_enabled_extension("realtime_graphs.php")) {
				ui_print_error_message(__('Realtime extension is not enabled.'));
                return;
            } else {
                include_once('../../extensions/realtime_graphs.php');
            }
            pandora_realtime_graphs();
		?>

	</body>
</html>
