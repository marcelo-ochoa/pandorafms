<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2013 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access MIB uploader');
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_filemanager.php';

// Header
ui_print_page_header(__('MIB uploader'), 'images/op_snmp.png', false, '', false);

if (isset($config['filemanager']['message'])) {
    echo $config['filemanager']['message'];
    $config['filemanager']['message'] = null;
}

$directory = (string) get_parameter('directory', SNMP_DIR_MIBS);
$directory = str_replace('\\', '/', $directory);

// Add custom directories here
$fallback_directory = 'attachment/mibs';

// A miminal security check to avoid directory traversal
if (preg_match('/\.\./', $directory)) {
    $directory = $fallback_directory;
}

if (preg_match('/^\//', $directory)) {
    $directory = $fallback_directory;
}

if (preg_match('/^manager/', $directory)) {
    $directory = $fallback_directory;
}

$banned_directories['include'] = true;
$banned_directories['godmode'] = true;
$banned_directories['operation'] = true;
$banned_directories['reporting'] = true;
$banned_directories['general'] = true;
$banned_directories[ENTERPRISE_DIR] = true;

if (isset($banned_directories[$directory])) {
    $directory = $fallback_directory;
}

// Current directory
$available_directories[$directory] = $directory;

$real_directory = realpath($config['homedir'].'/'.$directory);

ui_print_info_message(__('MIB files will be installed on the system. Please note that a MIB may depend on other MIB. To customize trap definitions use the SNMP trap editor.'));

filemanager_file_explorer(
    $real_directory,
    $directory,
    'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_mib_uploader',
    SNMP_DIR_MIBS,
    false,
    false,
    '',
    false,
    '',
    false
);
