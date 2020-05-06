<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function view_logfile($file_name)
{
    global $config;

    $memory_limit = ini_get('memory_limit');

    if (strstr($memory_limit, 'M') !== false) {
        $memory_limit = str_replace('M', '', $memory_limit);
        $memory_limit = ($memory_limit * 1024 * 1024);

        // Arbitrary size for the PHP program
        $memory_limit = ($memory_limit - (8 * 1024 * 1024));
    }

    if (!file_exists($file_name)) {
        ui_print_error_message(__('Cannot find file').'('.$file_name.')');
    } else {
        $file_size = filesize($file_name);

        if ($memory_limit < $file_size) {
            echo "<h2>$file_name (".__('File is too large than PHP memory allocated in the system.').')</h2>';
            echo '<h2>'.__('The preview file is imposible.').'</h2>';
        } else if ($file_size > ($config['max_log_size'] * 1000)) {
            $data = file_get_contents($file_name, false, null, ($file_size - ($config['max_log_size'] * 1000)));
            echo "<h2>$file_name (".format_numeric(filesize($file_name) / 1024).' KB) </h2>';
            echo "<textarea style='width: 98%; float:right; height: 200px; margin-bottom:20px;' name='$file_name'>";
            echo '... ';
            echo $data;
            echo '</textarea><br><br>';
        } else {
            $data = file_get_contents($file_name);
            echo "<h2>$file_name (".format_numeric(filesize($file_name) / 1024).' KB) </h2>';
            echo "<textarea style='width: 98%; float:right; height: 200px; margin-bottom:20px;' name='$file_name'>";
            echo $data;
            echo '</textarea><br><br>';
        }
    }
}


function pandoralogs_extension_main()
{
    global $config;

    if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
        db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
        include 'general/noaccess.php';
        return;
    }

    ui_print_page_header(__('System logfile viewer'), 'images/extensions.png', false, '', true, '');

    echo '<p>'.__('Use this tool to view your %s logfiles directly on the console', get_product_name()).'</p>';

    echo '<p>'.__('You can choose the amount of information shown in general setup (Log size limit in system logs viewer extension), '.($config['max_log_size'] * 1000).'B at the moment').'</p>';

    $logs_directory = (!empty($config['server_log_dir'])) ? io_safe_output($config['server_log_dir']) : '/var/log/pandora';

    view_logfile($config['homedir'].'/pandora_console.log');
    view_logfile($logs_directory.'/pandora_server.log');
    view_logfile($logs_directory.'/pandora_server.error');
}


extensions_add_godmode_menu_option(__('System logfiles'), 'PM', '', null, 'v1r1');
extensions_add_godmode_function('pandoralogs_extension_main');
