<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global variables
global $config;

check_login ();

$data = array ();
$data[0] = __('Plugin');
$data[1] = html_print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, 'javascript: load_plugin_macros_fields(\'network_component-macro\')', __('None'), 0, true, false, false);
// Store the macros in base64 into a hidden control to move between pages
$data[1] .= html_print_input_hidden('macros',base64_encode($macros),true);
$data[2] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true, ui_get_full_url(false, false, false, false));
$data[3] = html_print_extended_select_for_post_process('post_process',
		$post_process, '', __('Empty'), '0', false, true, false, true);

push_table_row ($data, 'plugin_1');

// A hidden "model row" to clone it from javascript to add fields dynamicly
$data = array ();
$data[0] = 'macro_desc';
$data[0] .= ui_print_help_tip ('macro_help', true);
$data[1] = html_print_input_text ('macro_name', 'macro_value', '', 100, 1024, true);
$table->colspan['macro_field'][1] = 3;
$table->rowstyle['macro_field'] = 'display:none';

push_table_row ($data, 'macro_field');

// If there are $macros, we create the form fields
if (!empty($macros)) {
	$macros = json_decode($macros, true);
	
	foreach ($macros as $k => $m) {
		$data = array ();
		$data[0] = $m['desc'];
		if (!empty($m['help'])) {
			$data[0] .= ui_print_help_tip ($m['help'], true);
		}
		if ($m['hide'] == 1) {
			$data[1] = html_print_input_text($m['macro'], $m['value'], '', 100, 1024, true);
		} else {
			$data[1] = html_print_input_text($m['macro'], io_output_password($m['value']), '', 100, 1024, true);
		}
		$table->colspan['macro'.$m['macro']][1] = 3;
		$table->rowclass['macro'.$m['macro']] = 'macro_field';
		
		push_table_row ($data, 'macro'.$m['macro']);
	}
}

?>

