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

global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "LW") && 
	! check_acl ($config['id_user'], 0, "AD") && 
	! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'] . '/include/functions_agents.php');
require_once ($config['homedir'] . '/include/functions_alerts.php');
require_once ($config['homedir'] . '/include/functions_users.php');
$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');
enterprise_include_once ('meta/include/functions_alerts_meta.php');

$id_group = 0;
/* Check if this page is included from a agent edition */
if (isset ($id_agente)) {
	$id_group = agents_get_agent_group ($id_agente);
}
else {
	$id_agente = 0;
}

$create_alert = (bool) get_parameter ('create_alert');
$add_action = (bool) get_parameter ('add_action');
$update_action = (bool) get_parameter ('update_action');
$delete_action = (bool) get_parameter ('delete_action');
$delete_alert = (bool) get_parameter ('delete_alert');
$update_alert = (bool) get_parameter ('update_alert'); ////
$disable_alert = (bool) get_parameter ('disable_alert');
$enable_alert = (bool) get_parameter ('enable_alert');
$standbyon_alert = (bool) get_parameter ('standbyon_alert');
$standbyoff_alert = (bool) get_parameter ('standbyoff_alert');
$tab = get_parameter('tab', 'list');
$group = get_parameter('group', 0); //0 is All group
$templateName = get_parameter('template_name','');
$moduleName = get_parameter('module_name','');
$agentID = get_parameter('agent_id','');
$agentName = get_parameter('agent_name','');
$actionID = get_parameter('action_id','');
$fieldContent = get_parameter('field_content','');
$searchType = get_parameter('search_type','');
$priority = get_parameter('priority','');
$searchFlag = get_parameter('search',0);
$enabledisable = get_parameter('enabledisable','');
$standby = get_parameter('standby','');
$pure = get_parameter('pure', 0);
$messageAction = '';

if ($update_alert) {

	$id_alert_agent_module = (int) get_parameter ('id_alert_update');

	$id_alert_template = (int) get_parameter ('template');
	$id_agent_module = (int) get_parameter ('id_agent_module');

	$values_upd = array();

	if (!empty($id_alert_template))
		$values_upd['id_agent_module'] = $id_agent_module;

	if (!empty($id_alert_template))
		$values_upd['id_alert_template'] = $id_alert_template;

	$id = alerts_update_alert_agent_module ($id_alert_agent_module, $values_upd);

	$messageAction = ui_print_result_message ($id,
		__('Successfully updated'), __('Could not be updated'), '', true);

}

if ($create_alert) {
	$id_alert_template = (int) get_parameter ('template');
	$id_agent_module = (int) get_parameter ('id_agent_module');

	if (db_get_value_sql("SELECT COUNT(id)
		FROM talert_template_modules
		WHERE id_agent_module = " . $id_agent_module . "
			AND id_alert_template = " . $id_alert_template) > 0) {
		
		$messageAction = ui_print_result_message (false, '',
			__('Already added'), '', true);
	}
	else {
		$id = alerts_create_alert_agent_module ($id_agent_module, $id_alert_template);

		$alert_template_name = db_get_value ("name",
			"talert_templates","id", $id_alert_template);
		$module_name = db_get_value ("nombre",
			"tagente_modulo","id_agente_modulo", $id_agent_module);
		$agent_alias = agents_get_alias (db_get_value ("id_agente",
			"tagente_modulo","id_agente_modulo", $id_agent_module));
		
		// Audit the creation only when the alert creation is correct
		$unsafe_alert_template_name = io_safe_output($alert_template_name);
		$unsafe_module_name = io_safe_output($module_name);
		$unsafe_agent_alias = io_safe_output($agent_alias);
		if ($id) {
			db_pandora_audit("Alert management",
				"Added alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'",
				false, false, 'ID: ' . $id);
		}
		else {
			db_pandora_audit("Alert management",
				"Fail Added alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'");
		}
		

		/* Show errors */
		if (!isset($messageAction)) {
			$messageAction = __('Could not be created');
		}
		
		if ($id_alert_template == "") {
			$messageAction = __('No template specified');
		}

		if ($id_agent_module == "") {
			$messageAction = __('No module specified');
		}

		$messageAction = ui_print_result_message ($id,
			__('Successfully created'), $messageAction, '', true);


		if ($id !== false) {
			$action_select = get_parameter('action_select');
			
			if ($action_select != 0) {
				$values = array();
				$values['fires_min'] = (int) get_parameter ('fires_min');
				$values['fires_max'] = (int) get_parameter ('fires_max');
				$values['module_action_threshold'] =
					(int)get_parameter ('module_action_threshold');
				

				alerts_add_alert_agent_module_action ($id, $action_select, $values);
			}
		}
	}
}

if ($delete_alert) {
	$id_alert_agent_module = (int) get_parameter ('id_alert');
	
	$temp =  db_get_row ("talert_template_modules","id", $id_alert_agent_module);
	$id_alert_template = $temp["id_alert_template"];
	$id_agent_module = $temp["id_agent_module"];
	$alert_template_name = db_get_value ("name", "talert_templates","id", $id_alert_template);
	$module_name = db_get_value ("nombre", "tagente_modulo","id_agente_modulo", $id_agent_module);
	$agent_alias = agents_get_alias(
		db_get_value("id_agente", "tagente_modulo","id_agente_modulo", $id_agent_module));
	
	$result = alerts_delete_alert_agent_module ($id_alert_agent_module);
	
	if ($result) {
		db_pandora_audit("Alert management",
			"Deleted alert '$alert_template_name' for module '$module_name' in agent '$agent_alias'");
	}
	else {
		db_pandora_audit("Alert management",
			"Fail to deleted alert '$alert_template_name' for module '$module_name' in agent '$agent_alias'");
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully deleted'), __('Could not be deleted'), '', true);
		
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_delete_alert='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_delete_alert='.$result);	
		}
		
	}
		
}

if ($add_action) {
	$id_action = (int) get_parameter ('action_select');
	$id_alert_module = (int) get_parameter ('id_alert_module');
	$fires_min = (int) get_parameter ('fires_min');
	$fires_max = (int) get_parameter ('fires_max');
	$values = array ();
	if ($fires_min != -1)
		$values['fires_min'] = $fires_min;
	if ($fires_max != -1)
		$values['fires_max'] = $fires_max;
	$values['module_action_threshold'] = (int) get_parameter ('module_action_threshold');
	
	$result = alerts_add_alert_agent_module_action ($id_alert_module, $id_action, $values);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Add action ' . $id_action . ' in  alert ' . $id_alert_module);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to add action ' . $id_action . ' in alert ' . $id_alert_module);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully added'), __('Could not be added'), '', true);
}

if ($update_action) {
	$id_action = (int) get_parameter ('action_select_ajax');
	$id_module_action = (int) get_parameter ('id_module_action_ajax');
	$fires_min = (int) get_parameter ('fires_min_ajax');
	$fires_max = (int) get_parameter ('fires_max_ajax');
	
	$values = array ();
	if ($fires_min != -1)
		$values['fires_min'] = $fires_min;
	if ($fires_max != -1)
		$values['fires_max'] = $fires_max;
	$values['module_action_threshold'] = (int) get_parameter ('module_action_threshold_ajax');
	$values['id_alert_action'] = $id_module_action;
	
	$result = alerts_update_alert_agent_module_action ($id_action, $values);
	if ($result) {
		db_pandora_audit("Alert management", 'Update action ' . $id_action . ' in  alert ' . $id_alert_module);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to updated action ' . $id_action . ' in alert ' . $id_alert_module);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully updated'), __('Could not be updated'), '', true);
}

if ($delete_action) {
	$id_action = (int) get_parameter ('id_action');
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = alerts_delete_alert_agent_module_action ($id_action);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Delete action ' . $id_action . ' in alert ' . $id_alert);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to delete action ' . $id_action . ' in alert ' . $id_alert);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully deleted'), __('Could not be deleted'), '', true);
		
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_delete_action='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_delete_action='.$result);	
		}
		
	}
		
}

if ($enable_alert) {
	$searchFlag = true;
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = alerts_agent_module_disable ($id_alert, false);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Enable  ' . $id_alert);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to enable ' . $id_alert);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully enabled'), __('Could not be enabled'), '', true);
		
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_enable_alert='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_enable_alert='.$result);	
		}
		
	}
}

if ($disable_alert) {
	$searchFlag = true;
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = alerts_agent_module_disable ($id_alert, true);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Disable  ' . $id_alert);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to disable ' . $id_alert);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully disabled'), __('Could not be disabled'), '', true);
	
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_disable_alert='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_disable_alert='.$result);	
		}
		
	}
	
}

if ($standbyon_alert) {
	$searchFlag = true;
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = alerts_agent_module_standby ($id_alert, true);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Standby  ' . $id_alert);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to standby ' . $id_alert);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully set standby'), __('Could not be set standby'), '', true);
		
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_standbyon='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_standbyon='.$result);	
		}
		
	}
}

if ($standbyoff_alert) {
	$searchFlag = true;
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = alerts_agent_module_standby ($id_alert, false);
	
	if ($result) {
		db_pandora_audit("Alert management", 'Standbyoff  ' . $id_alert);
	}
	else {
		db_pandora_audit("Alert management", 'Fail to standbyoff ' . $id_alert);
	}
	
	$messageAction = ui_print_result_message ($result,
		__('Successfully set off standby'), __('Could not be set off standby'), '', true);
		
	$id_cluster = db_get_all_rows_sql('select id,cluster_type from tcluster where id_agent = '.$id_agente);
	
	if($id_cluster){
		
		if($id_cluster[0]['cluster_type'] == 'AA'){
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=5&update=1&message_standbyoff='.$result);
		}
		else{
			header('Location: index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$id_cluster[0]['id'].'&step=7&update=1&message_standbyoff='.$result);	
		}
		
	}
}

if ($id_agente) {
	$agents = array ($id_agente => agents_get_name ($id_agente));
	
	if ($group == 0) {
		$groups = users_get_groups ();
	}
	else {
		$groups = array(0 => __('All'));
	}
	
	echo $messageAction;
	
	require_once('godmode/alerts/alert_list.list.php');
	$all_groups = agents_get_all_groups_agent ($id_agente, $agent['id_grupo']);
	if(check_acl_one_of_groups ($config['id_user'], $all_groups, "LW") || check_acl_one_of_groups ($config['id_user'], $all_groups, "LM")) {
		require_once('godmode/alerts/alert_list.builder.php');
	}
	
	return;
}
else {
	$searchFlag = true;
	if (!is_metaconsole()) {
		// The tabs will be shown only with manage alerts permissions
		if(check_acl ($config['id_user'], 0, "LW") || check_acl ($config['id_user'], 0, "LM")) {
			$buttons = array(
				'list' => array(
					'active' => false,
					'text' => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure='.$pure.'">' . 
						html_print_image ("images/list.png", true, array ("title" => __('List alerts'))) .'</a>'),
				'builder' => array(
					'active' => false,
					'text' => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=builder&pure='.$pure.'">' . 
						html_print_image ("images/pen.png", true, array ("title" => __('Builder alert'))) .'</a>'));
			
			$buttons[$tab]['active'] = true;
		}
		else {
			$buttons = "";
		}
		
		if ($tab == 'list') {
			ui_print_page_header(__('Alerts') . ' &raquo; ' . __('Manage alerts') . ' &raquo; ' . __('List'), "images/gm_alerts.png", false, "alerts_config", true, $buttons);
		}
		else {
			ui_print_page_header(__('Alerts') . ' &raquo; ' . __('Manage alerts') . ' &raquo; ' . __('Create'), "images/gm_alerts.png", false, "manage_alert_list", true, $buttons);
		}
		
	} 
	else {
		alerts_meta_print_header();
	}
	
	echo $messageAction;
	
	switch ($tab) {
		case 'list':
			if ($group == 0) {
				$groups = users_get_groups ();
			}
			else {
				$groups = array(0 => __('All'));
			}
			$agents = agents_get_group_agents (array_keys ($groups), false, "none",true);
			
			require_once($config['homedir'] . '/godmode/alerts/alert_list.list.php');
			
			return;
			break;
		case 'builder':
			if ($group == 0) {
				$groups = users_get_groups ();
			}
			else {
				$groups = array(0 => __('All'));
			}
			
			require_once($config['homedir'] . '/godmode/alerts/alert_list.builder.php');
			
			return;
			break;
	}
}
?>
