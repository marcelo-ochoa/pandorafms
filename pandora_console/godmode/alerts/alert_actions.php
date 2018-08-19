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

// Load global vars
global $config;

require_once ($config['homedir'] . "/include/functions_alerts.php");
require_once ($config['homedir'] . '/include/functions_users.php');
require_once ($config['homedir'] . '/include/functions_groups.php');
enterprise_include_once ('meta/include/functions_alerts_meta.php');

check_login ();

enterprise_hook('open_meta_frame');

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert actions");
	require ("general/noaccess.php");
	exit;
}

if (is_ajax ()) {
	$get_alert_action = (bool) get_parameter ('get_alert_action');
	if ($get_alert_action) {
		$id = (int) get_parameter ('id');
		$action = alerts_get_alert_action ($id);
		$action['command'] = alerts_get_alert_action_alert_command ($action['id']);
		
		echo json_encode ($action);
	}
	return;
}

$update_action = (bool) get_parameter ('update_action');
$create_action = (bool) get_parameter ('create_action');
$delete_action = (bool) get_parameter ('delete_action');
$copy_action = (bool) get_parameter ('copy_action');
$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
	$sec = 'advanced';
}
else {	
	$sec = 'galertas';
}

if ((!$copy_action) && (!$delete_action) && (!$update_action)) {
	// Header	
	if (defined('METACONSOLE')) {
		alerts_meta_print_header ();
	}
	else {	
		ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
	}
}


if ($copy_action) {
	$id = get_parameter ('id');

	$al_action = alerts_get_alert_action ($id);

	if ($al_action !== false) {
		// If user tries to copy an action with group=ALL
		if ($al_action['id_group'] == 0) {
			// then must have "PM" access privileges
			if (! check_acl ($config['id_user'], 0, "PM")) {
				db_pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
			else {
				// Header
				if (defined('METACONSOLE')) {
					alerts_meta_print_header ();
				}
				else {
					ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
				}
			}
		} // If user tries to copy an action of others groups
		else {
			$own_info = get_user_info ($config['id_user']);
			if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
				$own_groups = array_keys(users_get_groups($config['id_user'], "LM"));
			else
				$own_groups = array_keys(users_get_groups($config['id_user'], "LM", false));
			$is_in_group = in_array($al_action['id_group'], $own_groups);
			// Then action group have to be in his own groups
			if ($is_in_group) {
				// Header
				if (defined('METACONSOLE')) {
					alerts_meta_print_header ();
				}
				else {
					ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
				}
			}
			else {
				db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
		}
	}
	else {
		// Header
		if (defined('METACONSOLE')) {
			alerts_meta_print_header ();
		}
		else {
			ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
		}
	}
	$result = alerts_clone_alert_action ($id);
	
	if ($result) {
		db_pandora_audit("Command management", "Duplicate alert action " . $id . " clone to " . $result);
	}
	else {
		db_pandora_audit("Command management", "Fail try to duplicate alert action " . $id);
	}
	
	ui_print_result_message ($result,
		__('Successfully copied'),
		__('Could not be copied'));
}

if ($create_action) {
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	
	$fields_descriptions = array();
	$fields_values = array();
	$info_fields = '';
	$values = array();
	for($i=1;$i<=$config['max_macro_fields'];$i++) {
		$values['field'.$i] = (string) get_parameter ('field'.$i.'_value');
		$info_fields .= ' Field'.$i.': ' . $values['field'.$i];
		$values['field'.$i.'_recovery'] = (string) get_parameter ('field'.$i.'_recovery_value');
		$info_fields .= ' Field'.$i.'Recovery: ' . $values['field'.$i.'_recovery'];
	}

	$values['id_group'] = (string) get_parameter ('group');
	$values['action_threshold'] = (int) get_parameter ('action_threshold');
	
	$name_check = db_get_value ('name', 'talert_actions', 'name', $name);
	
	if ($name_check) {
		$result = '';
	}
	else {
		$result = alerts_create_alert_action ($name, $id_alert_command,
			$values);
		
		$info = '{"Name":"'.$name.'", "ID alert Command":"'.$id_alert_command.'", "Field information":"'.$info_fields.'", "Group":"'.$values['id_group'].'",
			"Action threshold":"'.$values['action_threshold'].'"}';
	}
	
	if ($result) {
		db_pandora_audit("Command management", "Create alert action #" . $result, false, false, $info);
	}
	else {
		db_pandora_audit("Command management", "Fail try to create alert action", false, false);
	}
	
	ui_print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
}

if ($update_action) {
	$id = (string) get_parameter ('id');
	
	$al_action = alerts_get_alert_action ($id);
	
	if ($al_action !== false) {
		if ($al_action['id_group'] == 0) {
			if (! check_acl ($config['id_user'], 0, "PM")) {
				db_pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
			else {
				// Header
				if (defined('METACONSOLE')) {
					alerts_meta_print_header ();
				}
				else {
					ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
				}
			}
		}
	}
	else {
		// Header
		if (defined('METACONSOLE')) {
			alerts_meta_print_header ();
		}
		else {
			ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alerts_config", true);
		}
	}
	
	
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$group = get_parameter ('group');
	$action_threshold = (int) get_parameter ('action_threshold');
	
	$info_fields = '';
	$values = array();
	
	for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
		$values['field'.$i] = (string) get_parameter ('field'.$i.'_value');
		$info_fields .= ' Field1: ' . $values['field'.$i];
		$values['field'.$i.'_recovery'] = (string) get_parameter ('field'.$i.'_recovery_value');
		$info_fields .= ' Field'.$i.'Recovery: ' . $values['field'.$i.'_recovery'];
	}
	
	$values['name'] = $name;
	$values['id_alert_command'] = $id_alert_command;
	$values['id_group'] = $group;
	$values['action_threshold'] = $action_threshold;
	
	if (!$name) {
		$result = '';
	}
	else {
		$result = alerts_update_alert_action ($id, $values);
	}
	
	if ($result) {
		db_pandora_audit("Command management", "Update alert action #" . $id, false, false, json_encode($values));
	}
	else {
		db_pandora_audit("Command management", "Fail try to update alert action #" . $id, false, false, json_encode($values));
	}
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_action) {
	$id = get_parameter ('id');
	
	$al_action = alerts_get_alert_action ($id);
	
	if ($al_action !== false) {
		// If user tries to delete an action with group=ALL
		if ($al_action['id_group'] == 0) {
			// then must have "PM" access privileges
			if (! check_acl ($config['id_user'], 0, "PM")) {
				db_pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
			else {
				// Header
				if (defined('METACONSOLE')) {
					alerts_meta_print_header ();
				}
				else {
					ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alert_action", true);
				}
			}
		// If user tries to delete an action of others groups
		}
		else {
			$own_info = get_user_info ($config['id_user']);
			if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
				$own_groups = array_keys(users_get_groups($config['id_user'], "LM"));
			else
				$own_groups = array_keys(users_get_groups($config['id_user'], "LM", false));
			$is_in_group = in_array($al_action['id_group'], $own_groups);
			// Then action group have to be in his own groups
			if ($is_in_group) {
				// Header	
				if (defined('METACONSOLE')) {
					alerts_meta_print_header ();
				}
				else {	
					ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "alert_action", true);
				}
			}
			else {
				db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
		}
	}
	else
		// Header
		ui_print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/gm_alerts.png", false, "", true);
	
	
	$result = alerts_delete_alert_action ($id);
	
	if ($result) {
		db_pandora_audit("Command management", "Delete alert action #" . $id);
	}
	else {
		db_pandora_audit("Command management", "Fail try to delete alert action #" . $id);
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '100%';
$table->class = 'databox data';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->head[2] = __('Copy');
$table->head[3] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[1] = '200px';
$table->size[2] = '40px';
$table->size[3] = '40px';
$table->align = array ();
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';

$filter = array();
if (!is_user_admin($config['id_user']))
	$filter['id_group'] = array_keys(users_get_groups(false, "LM"));

$actions = db_get_all_rows_filter ('talert_actions', $filter);
if ($actions === false)
	$actions = array ();

$rowPair = true;
$iterator = 0;
foreach ($actions as $action) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array ();
	
	$data[0] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&id='.$action['id'].'&pure='.$pure.'">'.
		$action['name'].'</a>';
	$data[1] = ui_print_group_icon ($action["id_group"], true) .'&nbsp;';
	
	if (check_acl($config['id_user'], $action["id_group"], "LM")) {
		$data[2] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions&amp;copy_action=1&amp;id='.$action['id'].'&pure='.$pure.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">' .
			html_print_image("images/copy.png", true) . '</a>';
		$data[3] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions&delete_action=1&id='.$action['id'].'&pure='.$pure.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
			html_print_image("images/cross.png", true) . '</a>';
	}
	
	array_push ($table->data, $data);
}
if (isset($data)) {
	html_print_table ($table);
}
else {
	ui_print_info_message ( array('no_close'=>true, 'message'=>  __('No alert actions configured') ) );
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'">';
html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';

enterprise_hook('close_meta_frame');

?>
