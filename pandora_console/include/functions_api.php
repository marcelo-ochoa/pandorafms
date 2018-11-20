<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches

require_once('functions_agents.php');
require_once('functions_modules.php');
include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . "/include/functions.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_graph.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_network_components.php");
include_once($config['homedir'] . "/include/functions_netflow.php");
include_once($config['homedir'] . "/include/functions_servers.php");
include_once($config['homedir'] . "/include/functions_planned_downtimes.php");
enterprise_include_once ('include/functions_local_components.php');
enterprise_include_once ('include/functions_events.php');
enterprise_include_once ('include/functions_agents.php');
enterprise_include_once ('include/functions_modules.php');
enterprise_include_once ('include/functions_clusters.php');

/**
 * Parse the "other" parameter.
 * 
 * @param string $other
 * @param mixed $otherType
 * @return mixed
 */
function parseOtherParameter($other, $otherType) {
	
	switch ($otherType) {
		case 'url_encode':
			$returnVar = array('type' => 'string', 'data' => urldecode($other));
			break;
		default:
			if (strpos($otherType, 'url_encode_separator_') !== false) {
				$separator = str_replace('url_encode_separator_', '', $otherType); 
				$returnVar = array('type' => 'array', 'data' => explode($separator, $other));
				foreach ($returnVar['data'] as $index => $element)
					$returnVar['data'][$index] = urldecode($element); 
			}
			else {
				$returnVar = array('type' => 'string', 'data' => urldecode($other));
			}
			break;
	}
	
	return $returnVar;
}

/**
 * 
 * @param $typeError
 * @param $returnType
 * @return unknown_type
 */
function returnError($typeError, $returnType = 'string') {
	switch ($typeError) {
		case 'no_set_no_get_no_help':
			returnData($returnType,
				array('type' => 'string', 'data' => __('No set or get or help operation.')));
			break;
		case 'no_exist_operation':
			returnData($returnType,
				array('type' => 'string', 'data' => __('This operation does not exist.')));
			break;
		case 'id_not_found':
			returnData($returnType,
				array('type' => 'string', 'data' => __('Id does not exist in BD.')));
			break;
		case 'not_allowed_operation_cluster':
			returnData($returnType,
				array('type' => 'string', 'data' => __('This operation can not be used in cluster elements.')));
			break;
		case 'forbidden':
			returnData($returnType,
				array('type' => 'string', 'data' => __('The user has not enough permission to make this action.')));
			break;
		case 'no_data_to_show':
			returnData($returnType,
				array('type' => 'string', 'data' => __('No data to show.')));
			break;
		default:
			returnData("string",
				array('type' => 'string', 'data' => __($returnType)));
			break;
	}
}

/**
 * @param $returnType
 * @param $data
 * @param $separator
 * @return
 */
function returnData($returnType, $data, $separator = ';') {
	switch ($returnType) {
		case 'string':
			if( is_array($data['data']) ){
				echo convert_array_multi($data['data'], $separator);
			}
			else{
				echo $data['data'];
			}
			break;
		case 'csv':
		case 'csv_head':
			if( is_array($data['data']) ){
				if (array_key_exists('list_index', $data)) {
					if ($returnType == 'csv_head') {
						foreach($data['list_index'] as $index) {
							echo $index;
							if (end($data['list_index']) == $index)
								echo "\n";
							else
								echo $separator;
						}
					}
					foreach($data['data'] as $dataContent) {
						foreach($data['list_index'] as $index) {
							if (array_key_exists($index, $dataContent))
								echo str_replace("\n", " ", $dataContent[$index]);
							if (end($data['list_index']) == $index)
								echo "\n";
							else
								echo $separator;
						}
					}
				}
				else {
					if (!empty($data['data'])) {
						foreach ($data['data'] as $dataContent) {
							$clean = array_map("array_apply_io_safe_output", $dataContent);
							foreach ($clean as $k => $v) {
								$clean[$k] = str_replace("\r", "\n", $clean[$k]);
								$clean[$k] = str_replace("\n", " ", $clean[$k]);
								$clean[$k] = strip_tags($clean[$k]);
								$clean[$k] = str_replace(';',' ',$clean[$k]);
							}
							$row = implode($separator, $clean);
							echo $row . "\n";
						}
					}
				}
			}
			else{
				echo $data['data'];
			}
			break;
		case 'json':
			$data = array_apply_io_safe_output($data);
			header('Content-type: application/json');
			// Allows extra parameters to json_encode, like JSON_FORCE_OBJECT
			if ($separator == ";") {
				$separator = null;
			}

			if(empty($separator)){
				echo json_encode ($data);
			} else {
				echo json_encode ($data, $separator);
			}

			break;
	}
}

function array_apply_io_safe_output($item) {
	return io_safe_output($item);
}

/**
 * 
 * @param $ip
 * @return unknown_type
 */
function isInACL($ip) {
	global $config;
	
	if (in_array($ip, $config['list_ACL_IPs_for_API']))
		return true;
	
	// If the IP is not in the list, we check one by one, all the wildcard registers
	foreach($config['list_ACL_IPs_for_API'] as $acl_ip) {
		if (preg_match('/\*/', $acl_ip)) {

			if($acl_ip[0]=='*' && strlen($acl_ip)>1){
				//example *.lab.artica.es == 151.80.15.*
				$acl_ip = str_replace('*.','',$acl_ip);
				$name = array();
				$name = gethostbyname($acl_ip);
				$names = explode('.',$name);
				$names[3] = "";
				$names = implode('.',$names);
				if (preg_match('/'.$names.'/', $ip)) {
					return true;
				}

			}else{
				//example 192.168.70.* or *
				$acl_ip = str_replace('.','\.',$acl_ip);
				// Replace wilcard by .* to do efective in regular expression
				$acl_ip = str_replace('*','.*',$acl_ip);
				// If the string match with the beginning of the IP give it access
				if (preg_match('/'.$acl_ip.'/', $ip)) {
					return true;
				}
			}
			// Scape for protection

		}else{
			//example lab.artica.es without '*'
			$name = array();
			$name = gethostbyname($acl_ip);
			if (preg_match('/'.$name.'/', $ip)) {
				return true;
			}
		}
	}
	
	return false;
}

// Return string OK,[version],[build]
function api_get_test() {
	global $pandora_version;
	global $build_version;
	
	echo "OK,$pandora_version,$build_version";
	
	if (defined ('METACONSOLE')) {
		echo ",meta";
	}
}

//Return OK if agent cache is activated
function api_get_test_agent_cache(){
	if (defined ('METACONSOLE')) {
		return;
	}

	$status = enterprise_hook('test_agent_cache', array());
	if ($status === ENTERPRISE_NOT_HOOK) {
		echo 'ERR';
		return;
	}
	echo $status;

}

// Returs the string OK if a connection to the event replication DB can be established.
function api_get_test_event_replication_db() {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$status = enterprise_hook('events_test_replication_db', array());
	if ($status === ENTERPRISE_NOT_HOOK) {
		echo 'ERR';
		return;
	}
	echo $status;
}

//-------------------------DEFINED OPERATIONS FUNCTIONS-----------------
function api_get_groups($thrash1, $thrash2, $other, $returnType, $user_in_db) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
	}
	
	$groups = users_get_groups ($user_in_db, "IR");
	
	$data_groups = array();
	foreach ($groups as $id => $group) {
		$data_groups[] = array($id, $group);
	}
	
	$data['type'] = 'array';
	$data['data'] = $data_groups;
	
	returnData($returnType, $data, $separator);
}

function api_get_agent_module_name_last_value($agentName, $moduleName, $other = ';', $returnType){
	$idAgent = agents_get_agent_id($agentName);
	$sql = sprintf('SELECT id_agente_modulo
		FROM tagente_modulo
		WHERE id_agente = %d AND nombre LIKE "%s"', $idAgent, $moduleName);
	$idModuleAgent = db_get_value_sql($sql);

	api_get_module_last_value($idModuleAgent, null, $other, $returnType);
}


function api_get_agent_module_name_last_value_alias($alias, $moduleName, $other = ';', $returnType) {
	$sql = sprintf('SELECT tagente_modulo.id_agente_modulo FROM tagente_modulo
			INNER JOIN tagente ON tagente_modulo.id_agente = tagente.id_agente
			WHERE tagente.alias LIKE "%s" AND tagente_modulo.nombre LIKE "%s"', $alias, $moduleName);
	$idModuleAgent = db_get_value_sql($sql);

	api_get_module_last_value($idModuleAgent, null, $other, $returnType);
}


function api_get_module_last_value($idAgentModule, $trash1, $other = ';', $returnType) {
	global $config;
	if (defined ('METACONSOLE')) {
		return;
	}

	$check_access = agents_check_access_agent(modules_get_agentmodule_agent($idAgentModule));
	if ($check_access === false || !check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}

	$sql = sprintf('SELECT datos
		FROM tagente_estado
		WHERE id_agente_modulo = %d', $idAgentModule);
	$value = db_get_value_sql($sql);

	if ($value === false) {
		if (isset($other['data'][1]) && $other['data'][0] == 'error_value') {
			returnData($returnType, array('type' => 'string', 'data' => $other['data'][1]));
		} elseif ($check_access) {
			returnError('no_data_to_show', $returnType);
		} else {
			returnError('id_not_found', $returnType);
		}
		return;
	}

	$data = array('type' => 'string', 'data' => $value);
	returnData($returnType, $data);
}

/*** DB column mapping table used by tree_agents (and get module_properties) ***/

/* agent related field mappings (output field => column designation for 'tagente') */
$agent_field_column_mapping = array(
	/* agent_id is not in this list (because it is mandatory) */
	/* agent_id_group is not in this list  */
	'agent_name' => 'nombre as agent_name',
	'agent_direction' => 'direccion as agent_direction',
	'agent_comentary' => 'comentarios as agent_comentary',
	'agent_last_contant' => 'ultimo_contacto as agent_last_contant',
	'agent_mode' => 'modo as agent_mode',
	'agent_interval' => 'intervalo as agent_interval',
	'agent_id_os' => 'id_os as agent_id_os',
	'agent_os_version' => 'os_version as agent_os_version',
	'agent_version' => 'agent_version as agent_version',
	'agent_last_remote_contact' => 'ultimo_contacto_remoto as agent_last_remote_contact',
	'agent_disabled' => 'disabled as agent_disabled',
	'agent_id_parent' => 'id_parent as agent_id_parent',
	'agent_custom_id' => 'custom_id as agent_custom_id',
	'agent_server_name' => 'server_name as agent_server_name',
	'agent_cascade_protection' => 'cascade_protection as agent_cascade_protection',
	'agent_cascade_protection_module' => 'cascade_protection_module as agent_cascade_protection_module',);

/* module related field mappings 1/2 (output field => column for 'tagente_modulo') */
$module_field_column_mampping = array(
	/* module_id_agent_modulo  is not in this list */
	'module_id_agent' => 'id_agente as module_id_agent',
	'module_id_module_type' => 'id_tipo_modulo as module_id_module_type',
	'module_description' => 'descripcion as module_description',
	'module_name' => 'nombre as module_name',
	'module_max' => 'max as module_max',
	'module_min' => 'min as module_min',
	'module_interval' => 'module_interval',
	'module_tcp_port' => 'tcp_port as module_tcp_port',
	'module_tcp_send' => 'tcp_send as module_tcp_send',
	'module_tcp_rcv' => 'tcp_rcv as module_tcp_rcv',
	'module_snmp_community' => 'snmp_community as module_snmp_community',
	'module_snmp_oid' => 'snmp_oid as module_snmp_oid',
	'module_ip_target' => 'ip_target as module_ip_target',
	'module_id_module_group' => 'id_module_group as module_id_module_group',
	'module_flag' => 'flag as module_flag',
	'module_id_module' => 'id_modulo as module_id_module',
	'module_disabled' => 'disabled as module_disabled',
	'module_id_export' => 'id_export as module_id_export',
	'module_plugin_user' => 'plugin_user as module_plugin_user',
	'module_plugin_pass' => 'plugin_pass as module_plugin_pass',
	'module_plugin_parameter' => 'plugin_parameter as module_plugin_parameter',
	'module_id_plugin' => 'id_plugin as module_id_plugin',
	'module_post_process' => 'post_process as module_post_process',
	'module_prediction_module' => 'prediction_module as module_prediction_module',
	'module_max_timeout' => 'max_timeout as module_max_timeout',
	'module_max_retries' => 'max_retries as module_max_retries',
	'module_custom_id' => 'custom_id as module_custom_id',
	'module_history_data' => 'history_data as module_history_data',
	'module_min_warning' => 'min_warning as module_min_warning',
	'module_max_warning' => 'max_warning as module_max_warning',
	'module_str_warning' => 'str_warning as module_str_warning',
	'module_min_critical' => 'min_critical as module_min_critical',
	'module_max_critical' => 'max_critical as module_max_critical',
	'module_str_critical' => 'str_critical as module_str_critical',
	'module_min_ff_event' => 'min_ff_event as module_min_ff_event',
	'module_delete_pending' => 'delete_pending as module_delete_pending',
	'module_plugin_macros' => 'macros as module_plugin_macros',
	'module_macros' => 'module_macros as module_macros',
	'module_critical_inverse' => 'critical_inverse as module_critical_inverse',
	'module_warning_inverse' => 'warning_inverse as module_warning_inverse');

/* module related field mappings 2/2 (output field => column for 'tagente_estado') */
$estado_fields_to_columns_mapping = array(
	/* module_id_agent_modulo  is not in this list */
	'module_id_agent_state' => 'id_agente_estado as module_id_agent_state',
	'module_data' => 'datos as module_data',
	'module_timestamp' => 'timestamp as module_timestamp',
	'module_state' => 'estado as module_state',
	'module_last_try' => 'last_try as module_last_try',
	'module_utimestamp' => 'utimestamp as module_utimestamp',
	'module_current_interval' => 'current_interval as module_current_interval',
	'module_running_by' => 'running_by as module_running_by',
	'module_last_execution_try' => 'last_execution_try as module_last_execution_try',
	'module_status_changes' => 'status_changes as module_status_changes',
	'module_last_status' => 'last_status as module_last_status');

/*** end of DB column mapping table ***/

/**
 * 
 * @param $trash1
 * @param $trahs2
 * @param mixed $other If $other is string is only the separator,
 *  but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *  and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *  api.php?op=get&op2=tree_agents&return_type=csv&other=;| |type_row,group_id,agent_name&other_mode=url_encode_separator_|
 *   
 * 
 * @param $returnType
 * @return unknown_type
 */
function api_get_tree_agents($trash1, $trahs2, $other, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		$returnReplace = $other['data'][1];
		if (trim($other['data'][2]) == '')
			$fields = false;
		else {
			$fields = explode(',', $other['data'][2]);
			foreach($fields as $index => $field)
				$fields[$index] = trim($field);
		}
		
	}
	else {
		if (strlen($other['data']) == 0)
			$separator = ';'; //by default
		else
			$separator = $other['data'];
		$returnReplace = ' ';
		$fields = false;
	}
	
	/** NOTE: if you want to add an output field, you have to add it to;
		1. $master_fields (field name)
		2. one of following field_column_mapping array (a pair of field name and corresponding column designation) 
		
		e.g. To add a new field named 'agent_NEWFIELD' that comes from tagente's COLUMN_X , you have to add;
		1. "agent_NEW_FIELD"  to $master_fields
		2. "'agent_NEW_FIELD' => 'agent_NEWFIELD as COLUMN_X'"  to $agent_field_column_mapping
		**/
	
	/* all of output field names */
	$master_fields = array(
		'type_row',
		
		'group_id',
		'group_name',
		'group_parent',
		'disabled',
		'custom_id',
		'group_description',
		'group_contact',
		'group_other',
		
		'agent_id',
		'alias',
		'agent_direction',
		'agent_comentary',
		'agent_id_group',
		'agent_last_contant',
		'agent_mode',
		'agent_interval',
		'agent_id_os',
		'agent_os_version',
		'agent_version',
		'agent_last_remote_contact',
		'agent_disabled',
		'agent_id_parent',
		'agent_custom_id',
		'agent_server_name',
		'agent_cascade_protection',
		'agent_cascade_protection_module',
		'agent_name',
		
		'module_id_agent_modulo',
		'module_id_agent',
		'module_id_module_type',
		'module_description',
		'module_name',
		'module_max',
		'module_min',
		'module_interval',
		'module_tcp_port',
		'module_tcp_send',
		'module_tcp_rcv',
		'module_snmp_community',
		'module_snmp_oid',
		'module_ip_target',
		'module_id_module_group',
		'module_flag',
		'module_id_module',
		'module_disabled',
		'module_id_export',
		'module_plugin_user',
		'module_plugin_pass',
		'module_plugin_parameter',
		'module_id_plugin',
		'module_post_process',
		'module_prediction_module',
		'module_max_timeout',
		'module_max_retries',
		'module_custom_id',
		'module_history_data',
		'module_min_warning',
		'module_max_warning',
		'module_str_warning',
		'module_min_critical',
		'module_max_critical',
		'module_str_critical',
		'module_min_ff_event',
		'module_delete_pending',
		'module_id_agent_state',
		'module_data',
		'module_timestamp',
		'module_state',
		'module_last_try',
		'module_utimestamp',
		'module_current_interval',
		'module_running_by',
		'module_last_execution_try',
		'module_status_changes',
		'module_last_status',
		'module_plugin_macros',
		'module_macros',
		'module_critical_inverse',
		'module_warning_inverse',
		
		'alert_id_agent_module',
		'alert_id_alert_template',
		'alert_internal_counter',
		'alert_last_fired',
		'alert_last_reference',
		'alert_times_fired',
		'alert_disabled',
		'alert_force_execution',
		'alert_id_alert_action',
		'alert_type',
		'alert_value',
		'alert_matches_value',
		'alert_max_value',
		'alert_min_value',
		'alert_time_threshold',
		'alert_max_alerts',
		'alert_min_alerts',
		'alert_time_from',
		'alert_time_to',
		'alert_monday',
		'alert_tuesday',
		'alert_wednesday',
		'alert_thursday',
		'alert_friday',
		'alert_saturday',
		'alert_sunday',
		'alert_recovery_notify',
		'alert_field2_recovery',
		'alert_field3_recovery',
		'alert_id_alert_template_module',
		'alert_fires_min',
		'alert_fires_max',
		'alert_id_alert_command',
		'alert_command',
		'alert_internal',
		'alert_template_modules_id',
		'alert_templates_id',
		'alert_template_module_actions_id',
		'alert_actions_id',
		'alert_commands_id',
		'alert_templates_name',
		'alert_actions_name',
		'alert_commands_name',
		'alert_templates_description',
		'alert_commands_description',
		'alert_template_modules_priority',
		'alert_templates_priority',
		'alert_templates_field1',
		'alert_actions_field1',
		'alert_templates_field2',
		'alert_actions_field2',
		'alert_templates_field3',
		'alert_actions_field3',
		'alert_templates_id_group',
		'alert_actions_id_group');
	
	/* agent related field mappings (output field => column designation for 'tagente') */
	
	global $agent_field_column_mapping;
	
	/* module related field mappings 1/2 (output field => column for 'tagente_modulo') */
	
	global $module_field_column_mampping;
	
	/* module related field mappings 2/2 (output field => column for 'tagente_estado') */

	global	$estado_fields_to_columns_mapping;
	
	/* alert related field mappings (output field => column for 'talert_template_modules', ... ) */

	$alert_fields_to_columns_mapping = array(
		/*** 'alert_id_agent_module (id_agent_module) is not in this list ***/
		'alert_template_modules_id'  => 't1.id as alert_template_modules_id',
		'alert_id_alert_template' => 't1.id_alert_template as alert_id_alert_template',
		'alert_internal_counter' => 't1.internal_counter as alert_internal_counter',
		'alert_last_fired' => 't1.last_fired as alert_last_fired',
		'alert_last_reference' => 't1.last_reference as alert_last_reference',
		'alert_times_fired' => 't1.times_fired as alert_times_fired',
		'alert_disabled' => 't1.disabled as alert_disabled',
		'alert_force_execution' => 't1.force_execution as alert_force_execution',
		'alert_template_modules_priority' => 't1.priority as alert_template_modules_priority',
		
		'alert_templates_id'  => 't2.id as alert_templates_id',
		'alert_type' => 't2.type as alert_type',
		'alert_value' => 't2.value as alert_value',
		'alert_matches_value' => 't2.matches_value as alert_matches_value',
		'alert_max_value' => 't2.max_value as alert_max_value',
		'alert_min_value' => 't2.min_value as alert_min_value',
		'alert_time_threshold' => 't2.time_threshold as alert_time_threshold',
		'alert_max_alerts' => 't2.max_alerts as alert_max_alerts',
		'alert_min_alerts' => 't2.min_alerts as alert_min_alerts',
		'alert_time_from' => 't2.time_from as alert_time_from',
		'alert_time_to' => 't2.time_to as alert_time_to',
		'alert_monday' => 't2.monday as alert_monday',
		'alert_tuesday' => 't2.tuesday as alert_tuesday',
		'alert_wednesday' => 't2.wednesday as alert_wednesday',
		'alert_thursday' => 't2.thursday as alert_thursday',
		'alert_friday' => 't2.friday as alert_friday',
		'alert_saturday' => 't2.saturday as alert_saturday',
		'alert_sunday' => 't2.sunday as alert_sunday',
		'alert_templates_name' => 't2.name as alert_templates_name',
		'alert_templates_description' => 't2.description as alert_templates_description',
		'alert_templates_priority' => 't2.priority as alert_templates_priority',
		'alert_templates_id_group' => 't2.id_group as alert_templates_id_group',
		'alert_recovery_notify' => 't2.recovery_notify as alert_recovery_notify',
		'alert_field2_recovery' => 't2.field2_recovery as alert_field2_recovery',
		'alert_field3_recovery' => 't2.field3_recovery as alert_field3_recovery',
		'alert_templates_field1' => 't2.field1 as alert_templates_field1',
		'alert_templates_field2' => 't2.field2 as alert_templates_field2',
		'alert_templates_field3' => 't2.field3 as alert_templates_field3',
		
		'alert_template_module_actions_id' => 't3.id as alert_template_module_actions_id',
		'alert_id_alert_action' => 't3.id_alert_action as alert_id_alert_action',
		'alert_id_alert_template_module' => 't3.id_alert_template_module as alert_id_alert_template_module',
		'alert_fires_min' => 't3.fires_min as alert_fires_min',
		'alert_fires_max' => 't3.fires_max as alert_fires_max',
		
		'alert_actions_id'  => 't4.id as alert_actions_id',
		'alert_actions_name' => 't4.name as alert_actions_name',
		'alert_id_alert_command' => 't4.id_alert_command as alert_id_alert_command',
		'alert_actions_id_group' => 't4.id_group as alert_actions_id_group',
		'alert_actions_field1' => 't4.field1 as alert_actions_field1',
		'alert_actions_field2' => 't4.field2 as alert_actions_field2',
		'alert_actions_field3' => 't4.field3 as alert_actions_field3',
		
		'alert_command'  => 't5.command as alert_command',
		'alert_internal' => 't5.internal as alert_internal',
		'alert_commands_id'  => 't5.id as alert_commands_id',
		'alert_commands_name'	=> 't5.name as alert_commands_name',
		'alert_commands_description'  => 't5.description as alert_commands_description');
	
	if ($fields == false) {
		$fields = $master_fields;
	}

	/** construct column list to query for tagente, tagente_modulo, tagente_estado and alert-related tables **/
	{
		$agent_additional_columns  = "";
		$module_additional_columns = "";
		$estado_additional_columns = "";
		$alert_additional_columns  = "";
		
		foreach ($fields as $fld ) {
			if (array_key_exists ($fld, $agent_field_column_mapping ) ) {
				$agent_additional_columns  .= (", " . $agent_field_column_mapping[$fld] );
			}
			if (array_key_exists ($fld, $module_field_column_mampping ) ) {
				$module_additional_columns .= (", " . $module_field_column_mampping[$fld]);
			}
			if (array_key_exists ($fld, $estado_fields_to_columns_mapping ) ) {
				$estado_additional_columns .= (", " . $estado_fields_to_columns_mapping[$fld]);
			}
			if (array_key_exists ($fld, $alert_fields_to_columns_mapping ) ) {
				$alert_additional_columns .= (", " . $alert_fields_to_columns_mapping[$fld]);
			}
		}
	}
	
	$returnVar = array();

	// Get only the user groups
	$filter_groups = "1 = 1";
	if (!users_is_admin($config['id_user'])) {
		$user_groups = implode (',', array_keys(users_get_groups()));
		$filter_groups = "id_grupo IN ($user_groups)";
	}

	$groups = db_get_all_rows_sql('SELECT id_grupo as group_id, ' .
			'nombre as group_name, parent as group_parent, disabled, custom_id, ' .
			'description as group_description, contact as group_contact, ' .
			'other as group_other FROM tgrupo WHERE ' . $filter_groups);
	if ($groups === false) $groups = array();
	$groups = str_replace('\n', $returnReplace, $groups);

	foreach ($groups as &$group) {
		$group['type_row'] = 'group';
		$returnVar[] = $group;

		// Get the agents for this group
		$id_group = $group['group_id'];
		$agents = db_get_all_rows_sql("SELECT id_agente AS agent_id, id_grupo AS agent_id_group , alias $agent_additional_columns
			FROM tagente ta LEFT JOIN tagent_secondary_group tasg
				ON ta.id_agente = tasg.id_agent
			WHERE ta.id_grupo = $id_group OR tasg.id_group = $id_group"
		);
		if ($agents === false) $agents = array();
		$agents = str_replace('\n', $returnReplace, $agents);

		foreach ($agents as $index => &$agent) {
			$agent['type_row']  = 'agent';
			$returnVar[] = $agent;
			
			if ( strlen($module_additional_columns) <= 0
				&& strlen($estado_additional_columns) <= 0 
				&& strlen($alert_additional_columns) <= 0 ) {
				continue; /** SKIP collecting MODULES and ALERTS **/
			}
			
			$modules = db_get_all_rows_sql('SELECT *
				FROM (SELECT id_agente_modulo as module_id_agent_modulo ' .  $module_additional_columns . '
						FROM tagente_modulo 
						WHERE id_agente = ' . $agent['agent_id'] . ') t1 
					INNER JOIN (SELECT id_agente_modulo as module_id_agent_modulo ' .  $estado_additional_columns . '
						FROM tagente_estado
						WHERE id_agente = ' . $agent['agent_id'] . ') t2
					ON t1.module_id_agent_modulo = t2.module_id_agent_modulo');
			
			if ($modules === false) $modules = array();
			$modules = str_replace('\n', $returnReplace, $modules);
			
			foreach ($modules as &$module) {
				$module['type_row']  = 'module';

				if( $module['module_macros'] ) {
					$module['module_macros'] = base64_decode( $module['module_macros']);
				}

				$returnVar[] = $module;
				
				if ( strlen($alert_additional_columns) <= 0 ) {
					continue;	/** SKIP collecting ALERTS info **/
				}
				
				$alerts = db_get_all_rows_sql('SELECT t1.id_agent_module as alert_id_agent_module ' .  $alert_additional_columns . '
					FROM (SELECT * FROM talert_template_modules
						WHERE id_agent_module = ' . $module['module_id_agent_modulo'] . ') t1 
					INNER JOIN talert_templates t2
						ON t1.id_alert_template = t2.id
					LEFT JOIN talert_template_module_actions t3
						ON t1.id = t3.id_alert_template_module
					LEFT JOIN talert_actions t4
						ON t3.id_alert_action = t4.id
					LEFT JOIN talert_commands t5
						ON t4.id_alert_command = t5.id');
				
				if ($alerts === false) $alerts = array();
				$alerts = str_replace('\n', $returnReplace, $alerts);
				
				foreach ($alerts as &$alert) {
					$alert['type_row'] = 'alert';
					$returnVar[] = $alert;
				}
			}
		}
	}
	$data = array('type' => 'array', 'data' => $returnVar);
	
	$data['list_index'] = $fields;
	
	returnData($returnType, $data, $separator);
}

/**
 * 
 * @param $id_module
 * @param $trahs2
 * @param mixed $other If $other is string is only the separator,
 *  but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *  and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *  api.php?op=get&op2=module_properties&id=1116&return_type=csv&other=;| |module_id_agent,module_name,module_description,module_last_try,module_data&other_mode=url_encode_separator_|
 *  	
 * @param $returnType
 * @return unknown_type
 */
function api_get_module_properties($id_module, $trahs2, $other, $returnType)
{
	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), $returnType)) return;

	if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		$returnReplace = $other['data'][1];
		if (trim($other['data'][2]) == '')
			$fields = false;
		else {
			$fields = explode(',', $other['data'][2]);
			foreach($fields as $index => $field)
				$fields[$index] = trim($field);
		}
		
	}
	else {
		if (strlen($other['data']) == 0)
			$separator = ';'; //by default
		else
			$separator = $other['data'];
		$returnReplace = ' ';
		$fields = false;
	}
	get_module_properties($id_module, $fields, $separator, $returnType, $returnReplace);
}
/**
 * 
 * @param $agent_name
 * @param $module_name
 * @param mixed $other If $other is string is only the separator,
 *  but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *  and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *  api.php?op=get&op2=module_properties_by_name&id=sample_agent&id2=sample_module&return_type=csv&other=;| |module_id_agent,module_name,module_str_critical,module_str_warning&other_mode=url_encode_separator_|
 *  	
 * @param $returnType
 * @return unknown_type
 */
function api_get_module_properties_by_name($agent_name, $module_name, $other, $returnType)
{
	if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		$returnReplace = $other['data'][1];
		if (trim($other['data'][2]) == '')
			$fields = false;
		else {
			$fields = explode(',', $other['data'][2]);
			foreach($fields as $index => $field)
				$fields[$index] = trim($field);
		}
		
	}
	else {
		if (strlen($other['data']) == 0)
			$separator = ';'; //by default
		else
			$separator = $other['data'];
		$returnReplace = ' ';
		$fields = false;
	}

	$agent_id = agents_get_agent_id($agent_name);
	if ($agent_id == 0) {
		returnError('error_get_module_properties_by_name', __('Does not exist agent with this name.'));
		return;
	}
	if (!util_api_check_agent_and_print_error($agent_id, $returnType)) return;

	$tagente_modulo = modules_get_agentmodule_id ($module_name, $agent_id);
	if ($tagente_modulo === false) {
		returnError('error_get_module_properties_by_name', __('Does not exist module with this name.'));
		return;
	}
	$module_id = $tagente_modulo['id_agente_modulo'];

	get_module_properties($module_id, $fields, $separator, $returnType, $returnReplace);
}

/*
 * subroutine for api_get_module_properties() and api_get_module_properties_by_name().
 */

 /**
 * 
 * @param $alias
 * @param $module_name
 * @param mixed $other If $other is string is only the separator,
 *  but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *  and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *  api.php?op=get&op2=module_properties_by_name&id=sample_agent&id2=sample_module&return_type=csv&other=;| |module_id_agent,module_name,module_str_critical,module_str_warning&other_mode=url_encode_separator_|
 *  	
 * @param $returnType
 * @return unknown_type
 */
function api_get_module_properties_by_alias($alias, $module_name, $other, $returnType)
{
	if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		$returnReplace = $other['data'][1];
		if (trim($other['data'][2]) == '')
			$fields = false;
		else {
			$fields = explode(',', $other['data'][2]);
			foreach($fields as $index => $field)
				$fields[$index] = trim($field);
		}
		
	}
	else {
		if (strlen($other['data']) == 0)
			$separator = ';'; //by default
		else
			$separator = $other['data'];
		$returnReplace = ' ';
		$fields = false;
	}

	$sql = sprintf('SELECT tagente_modulo.id_agente_modulo, tagente.id_agente FROM tagente_modulo 
					INNER JOIN tagente ON tagente_modulo.id_agente = tagente.id_agente 
					WHERE tagente.alias LIKE "%s" AND tagente_modulo.nombre LIKE "%s"', $alias, $module_name); 

	$data = db_get_row_sql($sql);
	if ($data === false) {
		returnError('error_get_module_properties_by_name', __('Does not exist the pair alias/module required.'));
	}
	if (!util_api_check_agent_and_print_error($data['id_agente'], $returnType)) return;

	$module_id = $data['id_agente_modulo'];

	get_module_properties($module_id, $fields, $separator, $returnType, $returnReplace);
}

/*
 * subroutine for api_get_module_properties() and api_get_module_properties_by_name().
 */

function get_module_properties($id_module, $fields, $separator, $returnType, $returnReplace)
{
	/** NOTE: if you want to add an output field, you have to add it to;
		1. $module_properties_master_fields (field name in order)
		2. Update field_column_mapping array (arraies are shared with get_tree_agents()).
			Each entry is  (DB coloum name => query fragment)
	**/
	
	/* all of output field names */
	$module_properties_master_fields = array(
		'module_id_agent_modulo',
		'module_id_agent',
		'module_id_module_type',
		'module_description',
		'module_name',
		'module_max',
		'module_min',
		'module_interval',
		'module_tcp_port',
		'module_tcp_send',
		'module_tcp_rcv',
		'module_snmp_community',
		'module_snmp_oid',
		'module_ip_target',
		'module_id_module_group',
		'module_flag',
		'module_id_module',
		'module_disabled',
		'module_id_export',
		'module_plugin_user',
		'module_plugin_pass',
		'module_plugin_parameter',
		'module_id_plugin',
		'module_post_process',
		'module_prediction_module',
		'module_max_timeout',
		'module_max_retries',
		'module_custom_id',
		'module_history_data',
		'module_min_warning',
		'module_max_warning',
		'module_str_warning',
		'module_min_critical',
		'module_max_critical',
		'module_str_critical',
		'module_min_ff_event',
		'module_delete_pending',
		'module_id_agent_state',
		'module_data',
		'module_timestamp',
		'module_state',
		'module_last_try',
		'module_utimestamp',
		'module_current_interval',
		'module_running_by',
		'module_last_execution_try',
		'module_status_changes',
		'module_last_status',
		'module_plugin_macros',
		'module_macros',
		'module_critical_inverse',
		'module_warning_inverse');

	/* module related field mappings 1/2 (output field => column for 'tagente_modulo') */

	global $module_field_column_mampping;
	
	/* module related field mappings 2/2 (output field => column for 'tagente_estado') */

	global $estado_fields_to_columns_mapping;

	if ($fields == false) {
		$fields = $module_properties_master_fields;
	}
	
	/* construct column list to query for tagente, tagente_modulo, tagente_estado and alert-related tables */
	$module_additional_columns = "";
	$estado_additional_columns = "";
	foreach ($fields as $fld ) {
		if (array_key_exists ($fld, $module_field_column_mampping ) ) {
			$module_additional_columns .= (", " . $module_field_column_mampping[$fld]);
		}
		if (array_key_exists ($fld, $estado_fields_to_columns_mapping ) ) {
			$estado_additional_columns .= (", " . $estado_fields_to_columns_mapping[$fld]);
		}
	}
	
	/* query to the DB */
	$returnVar = array();
	$modules = db_get_all_rows_sql('SELECT *
		FROM (SELECT id_agente_modulo as module_id_agent_modulo ' .  $module_additional_columns . '
				FROM tagente_modulo 
				WHERE id_agente_modulo = ' . $id_module . ') t1 
			INNER JOIN (SELECT id_agente_modulo as module_id_agent_modulo ' .  $estado_additional_columns . '
				FROM tagente_estado
				WHERE id_agente_modulo = ' . $id_module . ') t2
			ON t1.module_id_agent_modulo = t2.module_id_agent_modulo');

	if ($modules === false) $modules = array();
	$modules = str_replace('\n', $returnReplace, $modules);

	foreach ($modules as &$module) {
		$module['type_row']  = 'module';

		if( $module['module_macros'] ) {
			$module['module_macros'] = base64_decode( $module['module_macros']);
		}

		$returnVar[] = $module;
		
	}

	$data = array('type' => 'array', 'data' => $returnVar);
	
	$data['list_index'] = $fields;
	
	returnData($returnType, $data, $separator);
}

function api_set_update_agent($id_agent, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
		return;
	}

	$alias = $other['data'][0];
	$ip = $other['data'][1];
	$idParent = $other['data'][2];
	$idGroup = $other['data'][3];
	$cascadeProtection = $other['data'][4];
	$cascadeProtectionModule = $other['data'][5];
	$intervalSeconds = $other['data'][6];
	$idOS = $other['data'][7];
	$nameServer = $other['data'][8];
	$customId = $other['data'][9];
	$learningMode = $other['data'][10];
	$disabled = $other['data'][11];
	$description = $other['data'][12];
	
	if ($cascadeProtection == 1) {
		if (($idParent != 0) && (db_get_value_sql('SELECT id_agente_modulo
									FROM tagente_modulo
									WHERE id_agente = ' . $idParent . 
									' AND id_agente_modulo = ' . $cascadeProtectionModule) === false)) {
				returnError('parent_agent_not_exist', 'Is not a parent module to do cascade protection.');
		}
	}
	else {
		$cascadeProtectionModule = 0;
	}
	// Check ACL group
	if (!check_acl($config['id_user'], $idGroup, "AW")) {
		returnError('forbidden', 'string');
		return;
	}

	// Check selected parent
	if ($idParent != 0) {
		$parentCheck = agents_check_access_agent($idParent);
		if ($parentCheck === null) {
			returnError('parent_agent_not_exist', __('The agent parent don`t exist.'));
			return;
		}
		if ($parentCheck === false) {
			returnError('parent_agent_forbidden', __('The user cannot access to parent agent.'));
			return;
		}
	}
	$values_old = db_get_row_filter('tagente',
		array('id_agente' => $id_agent),
		array('id_grupo', 'disabled')
	);
	$tpolicy_group_old = db_get_all_rows_sql("SELECT id_policy FROM tpolicy_groups
			WHERE id_group = ".$values_old['id_grupo']);
	

	$return = db_process_sql_update('tagente', 
		array('alias' => $alias,
			'direccion' => $ip,
			'id_grupo' => $idGroup,
			'intervalo' => $intervalSeconds,
			'comentarios' => $description,
			'modo' => $learningMode,
			'id_os' => $idOS,
			'disabled' => $disabled,
			'cascade_protection' => $cascadeProtection,
			'cascade_protection_module' => $cascadeProtectionModule,
			'server_name' => $nameServer,
			'id_parent' => $idParent,
			'custom_id' => $customId),
		array('id_agente' => $id_agent));

	if ( $return && !empty($ip)) {
		// register ip for this agent in 'taddress'
		agents_add_address ($id_agent, $ip);
	}

	if($return){
		// Update config file
		if (isset($disabled) && $values_old['disabled'] != $disabled) {
			enterprise_hook(
				'config_agents_update_config_token',
				array($id_agent, 'standby', $disabled)
			);
		}

		if($tpolicy_group_old){
			foreach ($tpolicy_group_old as $key => $value) {
				$tpolicy_agents_old= db_get_sql("SELECT * FROM tpolicy_agents 
					WHERE id_policy = ".$value['id_policy'] . " AND id_agent = " .$id_agent);
							
				if($tpolicy_agents_old){
					$result2 = db_process_sql_update ('tpolicy_agents',
						array('pending_delete' => 1),
						array ('id_agent' => $id_agent, 'id_policy' => $value['id_policy']));
				}
			}
		}
		
		$tpolicy_group = db_get_all_rows_sql("SELECT id_policy FROM tpolicy_groups 
			WHERE id_group = ".$idGroup);
		
		if($tpolicy_group){
			foreach ($tpolicy_group as $key => $value) {
				$tpolicy_agents= db_get_sql("SELECT * FROM tpolicy_agents 
					WHERE id_policy = ".$value['id_policy'] . " AND id_agent =" .$id_agent);
					
				if(!$tpolicy_agents){
					db_process_sql_insert ('tpolicy_agents',
					array('id_policy' => $value['id_policy'], 'id_agent' => $id_agent));
				} else {
					$result3 = db_process_sql_update ('tpolicy_agents',
						array('pending_delete' => 0),
						array ('id_agent' => $id_agent, 'id_policy' => $value['id_policy']));
				}
			}
		}
	}

	returnData('string',
		array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Create a new agent, and print the id for new agent.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <agent_name>;<ip>;<id_parent>;<id_group>;
 *  <cascade_protection>;<interval_sec>;<id_os>;<id_server>;<custom_id>;<learning_mode>;<disabled>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_agent&other=pepito|1.1.1.1|0|4|0|30|8|10||0|0|nose%20nose&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_set_new_agent($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$alias = $other['data'][0];
	$ip = $other['data'][1];
	$idParent = $other['data'][2];
	$idGroup = $other['data'][3];
	$cascadeProtection = $other['data'][4];
	$cascadeProtectionModule = $other['data'][5];
	$intervalSeconds = $other['data'][6];
	$idOS = $other['data'][7];
	//$idServer = $other['data'][7];
	$nameServer = $other['data'][8];
	$customId = $other['data'][9];
	$learningMode = $other['data'][10];
	$disabled = $other['data'][11];
	$description = $other['data'][12];
	$alias_as_name = $other['data'][13];

	if($alias_as_name && !empty($alias)){
		$name = $alias;	
	} else {
		$name = hash("sha256",$alias . "|" .$direccion_agente ."|". time() ."|". sprintf("%04d", rand(0,10000)));
		if(empty($alias)){
			$alias = $name;
		}
	}

	if ($cascadeProtection == 1) {
		if (($idParent != 0) && (db_get_value_sql('SELECT id_agente_modulo
									FROM tagente_modulo
									WHERE id_agente = ' . $idParent . 
									' AND id_agente_modulo = ' . $cascadeProtectionModule) === false)) {
				returnError('parent_agent_not_exist', 'Is not a parent module to do cascade protection.');
		}
	}
	else {
		$cascadeProtectionModule = 0;
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql1 = 'SELECT name
				FROM tserver WHERE BINARY name LIKE "' . $nameServer . '"';
			break;
		case "postgresql":
		case "oracle":
			$sql1 = 'SELECT name
				FROM tserver WHERE name LIKE \'' . $nameServer . '\'';
			break;
	}
	
	$nameServer = db_get_value_sql($sql1);

	// Check ACL group
	if (!check_acl($config['id_user'], $idGroup, "AW")) {
		returnError('forbidden', 'string');
		return;
	}

	// Check selected parent
	if ($idParent != 0) {
		$parentCheck = agents_check_access_agent($idParent);
		if ($parentCheck === null) {
			returnError('parent_agent_not_exist', __('The agent parent don`t exist.'));
			return;
		}
		if ($parentCheck === false) {
			returnError('parent_agent_forbidden', __('The user cannot access to parent agent.'));
			return;
		}
	}
	if (agents_get_agent_id ($name)) {
		returnError('agent_name_exist', 'The name of agent yet exist in DB.');
	}
	else if (db_get_value_sql('SELECT id_grupo
		FROM tgrupo
		WHERE id_grupo = ' . $idGroup) === false) {
		
		returnError('id_grupo_not_exist', 'The group don`t exist.');
	}
	else if (db_get_value_sql('SELECT id_os
		FROM tconfig_os
		WHERE id_os = ' . $idOS) === false) {
		
		returnError('id_os_not_exist', 'The OS don`t exist.');
	}
	else if (db_get_value_sql($sql1) === false) {
		returnError('server_not_exist', 'The ' . get_product_name() . ' Server don`t exist.');
	}
	else {
		$idAgente = db_process_sql_insert ('tagente', 
			array ('nombre' => $name,
				'alias' => $alias,
				'direccion' => $ip,
				'id_grupo' => $idGroup,
				'intervalo' => $intervalSeconds,
				'comentarios' => $description,
				'modo' => $learningMode,
				'id_os' => $idOS,
				'disabled' => $disabled,
				'cascade_protection' => $cascadeProtection,
				'cascade_protection_module' => $cascadeProtectionModule,
				'server_name' => $nameServer,
				'id_parent' => $idParent,
				'custom_id' => $customId));
		
		if (!empty($idAgente) && !empty($ip)) {
			// register ip for this agent in 'taddress'
			agents_add_address ($idAgente, $ip);
		}
		
		if($idGroup && !empty($idAgente)){
			$tpolicy_group_old = db_get_all_rows_sql("SELECT id_policy FROM tpolicy_groups 
				WHERE id_group = ".$idGroup);
			
			if($tpolicy_group_old){
				foreach ($tpolicy_group_old as $key => $old_group) {
					db_process_sql_insert ('tpolicy_agents',
					array('id_policy' => $old_group['id_policy'], 'id_agent' => $idAgente));
				}
			}
		}
		
		returnData('string',
			array('type' => 'string', 'data' => $idAgente));
	}
}


function api_set_create_os($thrash1, $thrash2, $other, $thrash3) {
	global $config;


	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}
	
	if (defined ('METACONSOLE')) {
		return;
	}

	$values = array();
	
	$values['name'] = $other['data'][0];
	$values['description'] = $other['data'][1];

	if (($other['data'][2] !== 0) && ($other['data'][2] != '')) {
		$values['icon_name'] = $other['data'][2];
	}



	$resultOrId = false;
	if ($other['data'][0] != '') {
		$resultOrId = db_process_sql_insert('tconfig_os', $values);
	}

}

function api_set_update_os($id_os, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}
			
	$values = array();
	$values['name'] = $other['data'][0];
	$values['description'] = $other['data'][1];
		
	if (($other['data'][2] !== 0) && ($other['data'][2] != '')) {
		$values['icon_name'] = $other['data'][2];;
	}
	$result = false;


	if ($other['data'][0] != '') {

		$result = db_process_sql_update('tconfig_os', $values, array('id_os' => $id_os));
	}

}


/**
 *
 * Creates a custom field
 *
 * @param string $name Custom field name
 * @param boolean $display_front Flag to display custom field in agent's operation view
 */
function api_set_create_custom_field($t1, $t2, $other, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', $returnType);
		return;
	}

	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		
		$name = "";
		
		if ($other['data'][0] != '') {
			$name = $other['data'][0];
		}
		else {
			returnError('error_parameter', 'Custom field name required');
			return;
		}
		
		$display_front = 0;
		
		if ($other['data'][1] != '') {
			$display_front = $other['data'][1];
		}
		else {
			returnError('error_parameter', 'Custom field display flag required');
			return;
		}

		$is_password_type = 0;
		
		if ($other['data'][2] != '') {
			$is_password_type = $other['data'][2];
		}
		else {
			returnError('error_parameter', 'Custom field is password type required');
			return;
		}
		
		$result = db_process_sql_insert('tagent_custom_fields',
			array('name' => $name, 'display_on_front' => $display_front,
			'is_password_type' => $is_password_type));
		
		$data['type'] = "string";
		$data["data"] = $result;
		
		returnData("string", $data);
	}
}

/**
 *
 * Returns ID of custom field zero if not exists
 *
 * @param string $name Custom field name
 */
function api_get_custom_field_id($t1, $t2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}

	$name = $other["data"][0];
	$id = db_get_value ('id_field', 'tagent_custom_fields', 'name', $name);

	if ($id === false) {
		returnError('id_not_found', $returnType);
		return;
	}

	$data['type'] = "string";
	$data["data"] = $id;
	returnData("string", $data);
}

/**
 * Delete a agent with the name pass as parameter.
 * 
 * @param string $id Name of agent to delete.
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_agent($id, $thrash1, $thrast2, $thrash3) {
	global $config;

	if (is_metaconsole()) {
		if (!check_acl($config['id_user'], 0, "PM")) {
			returnError('forbidden', 'string');
			return;
		}
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup
				WHERE disabled = 0");
		
		if ($servers === false)
			$servers = array();
		
		
		foreach($servers as $server) {
			if (metaconsole_connect($server) == NOERR) {
				$idAgent[0] = agents_get_agent_id($id,true);
				if ($idAgent[0]) {
					$result = agents_delete_agent ($idAgent, true);
				}
			}
		}
	}
	else {
		$idAgent = agents_get_agent_id($id);
		if (!util_api_check_agent_and_print_error($idAgent, 'string', "AD")) {
			return;
		}
		$result = agents_delete_agent ($idAgent, true);
	}
	if (!$result)
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Correct Delete')));
}

/**
 * Get all agents, and print all the result like a csv or other type for example json.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <filter_so>;<filter_group>;<filter_modules_states>;<filter_name>;<filter_policy>;<csv_separator> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example for CSV:
 *  
 *  api.php?op=get&op2=all_agents&return_type=csv&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 * 
 *  example for JSON:
 * 
 * 	api.php?op=get&op2=all_agents&return_type=json&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 * 
 * @param $returnType.
 */
function api_get_all_agents($thrash1, $thrash2, $other, $returnType) {
	global $config;

	// Error if user cannot read agents.
	if (!check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}

	$groups = '1 = 1';
	if (!is_user_admin($config['id_user'])) {
		$user_groups = implode (',', array_keys(users_get_groups()));
		$groups = "(id_grupo IN ($user_groups) OR id_group IN ($user_groups))";
	}

	if (isset($other['data'][0])) {
		// Filter by SO
		if ($other['data'][0] != "") {
			$where .= " AND tconfig_os.id_os = " . $other['data'][0];
		}
	}
	if (isset($other['data'][1])) {
		// Filter by group
		if ($other['data'][1] != "") {
			$where .= " AND id_grupo = " . $other['data'][1];
		}
	}
	if (isset($other['data'][3])) {
		// Filter by alias
		if ($other['data'][3] != "") {
			$where .= " AND alias LIKE ('%" . $other['data'][3] . "%')";
		}
	}
	if (isset($other['data'][4])) {
		// Filter by policy
		if ($other['data'][4] != "") {
			$filter_by_policy = enterprise_hook('policies_get_filter_by_agent', array($other['data'][4]));
			if ($filter_by_policy !== ENTERPRISE_NOT_HOOK) {
				$where .= $filter_by_policy;
			}
		}
	}
	
	if (!isset($other['data'][5]))
		$separator = ';'; //by default
	else
		$separator = $other['data'][5];
	
	// Initialization of array
	$result_agents = array();
	// Filter by state
	
	if (defined ('METACONSOLE')) {
		$sql = "SELECT id_agente, alias, direccion, comentarios,
			tconfig_os.name, url_address, nombre
		FROM tconfig_os, tmetaconsole_agent
		LEFT JOIN tagent_secondary_group
			ON tmetaconsole_agent.id_agente = tagent_secondary_group.id_agent
		WHERE tmetaconsole_agent.id_os = tconfig_os.id_os
			AND disabled = 0 $where AND $groups";
	}
	else{
		$sql = "SELECT id_agente, alias, direccion, comentarios,
				tconfig_os.name, url_address, nombre
			FROM tconfig_os, tagente
			LEFT JOIN tagent_secondary_group
				ON tagente.id_agente = tagent_secondary_group.id_agent
			WHERE tagente.id_os = tconfig_os.id_os
				AND disabled = 0 $where AND $groups";
	}

	$all_agents = db_get_all_rows_sql($sql);

	// Filter by status: unknown, warning, critical, without modules 
	if (isset($other['data'][2])) {
		if ($other['data'][2] != "") {
			foreach($all_agents as $agent) {
				$filter_modules['id_agente'] = $agent['id_agente'];
				$filter_modules['disabled'] = 0;
				$filter_modules['delete_pending'] = 0;
				$modules = db_get_all_rows_filter('tagente_modulo',
					$filter_modules, 'id_agente_modulo'); 
				$result_modules = array(); 
				// Skip non init modules
				foreach ($modules as $module) {
					if (modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
						$result_modules[] = $module;
					}
				}
				
				// Without modules NO_MODULES
				if ($other['data'][2] == 'no_modules') {
					if (empty($result_modules) and $other['data'][2] == 'no_modules') {
						$result_agents[] = $agent;
					}
				}
				// filter by NORMAL, WARNING, CRITICAL, UNKNOWN, ALERT_FIRED
				else {
					$status = agents_get_status($agent['id_agente'], true);
					// Filter by status
					switch ($other['data'][2]) {
						case 'warning':
							if ($status == 2) {
								$result_agents[] = $agent;
							}
							break;
						case 'critical':
							if ($status == 1) {
								$result_agents[] = $agent;
							}
							break;
						case 'unknown':
							if ($status == 3) {
								$result_agents[] = $agent;
							}
							break;
						case 'normal':
							if ($status == 0) {
								$result_agents[] = $agent;
							}
							break;
						case 'alert_fired':
							if ($status == 4) {
								$result_agents[] = $agent;
							}
							break;
					}
				}
			}
		}
		else {
			$result_agents = $all_agents;
		}
	} 
	else {
		$result_agents = $all_agents;
	}
	
	if (empty($returnType)) {
		$returnType = "string";
	}
	
	if (empty($separator)) {
		$separator = ";";
	}
	
	foreach ($result_agents as $key => $value) {
		$result_agents[$key]['status'] = agents_get_status($result_agents[$key]['id_agente'], true);
	}
	
	if (count($result_agents) > 0 and $result_agents !== false) {
		$data = array('type' => 'array', 'data' => $result_agents);
		returnData($returnType, $data, $separator);
	}
	else {
		returnError('error_all_agents', 'No agents retrieved.');
	}
}

/**
 * Get modules for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=agents_modules&return_type=csv&other=14&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_agent_modules($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!util_api_check_agent_and_print_error($other['data'][0], 'csv')) return;

	$sql = sprintf("SELECT id_agente, id_agente_modulo, nombre 
		FROM tagente_modulo
		WHERE id_agente = %d AND disabled = 0
			AND delete_pending = 0", $other['data'][0]);
	
	$all_modules = db_get_all_rows_sql($sql);
	
	if (count($all_modules) > 0 and $all_modules !== false) {
		$data = array('type' => 'array', 'data' => $all_modules);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_agent_modules', 'No modules retrieved.');
	}
}

function api_get_db_uncompress_module_data ($id_agente_modulo,$tstart,$other){
	global $config;
	
	if (!isset($id_agente_modulo)) {
		return false;
	}

	if ((!isset($tstart)) || ($tstart === false)) {
		// Return data from the begining
		//$tstart = 0;
		$tstart = 0;
	}
	$tend = $other['data'];
	if ((!isset($tend)) || ($tend === false)) {
		// Return data until now
		$tend = time();
	}

	if ($tstart > $tend) {
		return false;
	}
	
	$search_historydb = false;
	$table = "tagente_datos";
	
	$module = modules_get_agentmodule($id_agente_modulo);
	
	if ($module === false){
		// module not exists
		return false;
	}
	$module_type = $module['id_tipo_modulo'];
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'string') !== false) {
		$table = "tagente_datos_string";
	}
	
	// Get first available utimestamp in active DB
	$query  = " SELECT utimestamp, datos FROM $table ";
	$query .= " WHERE id_agente_modulo=$id_agente_modulo AND utimestamp < $tstart";
	$query .= " ORDER BY utimestamp DESC LIMIT 1";


	$ret = db_get_all_rows_sql( $query , $search_historydb);

	
	if ( ( $ret === false ) || (( isset($ret[0]["utimestamp"]) && ($ret[0]["utimestamp"] > $tstart )))) {
		// Value older than first retrieved from active DB
		$search_historydb = true;

		$ret = db_get_all_rows_sql( $query , $search_historydb);
	}
	else {
		$first_data["utimestamp"] = $ret[0]["utimestamp"];
		$first_data["datos"]      = $ret[0]["datos"];
	}

	if ( ( $ret === false ) || (( isset($ret[0]["utimestamp"]) && ($ret[0]["utimestamp"] > $tstart )))) {
		// No previous data. -> not init
		// Avoid false unknown status
		$first_data["utimestamp"] = time();
		$first_data["datos"]      = false;
	}
	else {
		$first_data["utimestamp"] = $ret[0]["utimestamp"];
		$first_data["datos"]      = $ret[0]["datos"];
	}
	
	$query  = " SELECT utimestamp, datos FROM $table ";
	$query .= " WHERE id_agente_modulo=$id_agente_modulo AND utimestamp >= $tstart AND utimestamp <= $tend";
	$query .= " ORDER BY utimestamp ASC";

	// Retrieve all data from module in given range
	$raw_data = db_get_all_rows_sql($query, $search_historydb);
	
	if (($raw_data === false) && ($ret === false)) {
		// No data
		return false;
	}

	// Retrieve going unknown events in range
	$unknown_events = db_get_module_ranges_unknown($id_agente_modulo, $tstart, $tend);
	
	// Retrieve module_interval to build the template
	$module_interval = modules_get_interval ($id_agente_modulo);
	$slice_size = $module_interval;
	
	

	// We'll return a bidimensional array
	// Structure returned: schema:
	// 
	// uncompressed_data =>
	//      pool_id (int)
	//          utimestamp (start of current slice)
	//          data
	//              array
	//                  utimestamp
	//                  datos

	$return = array();
	// Point current_timestamp to begin of the set and initialize flags
	$current_timestamp   = $tstart;
	$last_inserted_value = $first_data["datos"];
	$last_timestamp      = $first_data["utimestamp"];
	$data_found          = 0;

	// Build template
	$pool_id = 0;
	$now = time();

	$in_unknown_status = 0;
	if (is_array($unknown_events)) {
		$current_unknown = array_shift($unknown_events);
	}
	
	while ( $current_timestamp < $tend ) {
		$expected_data_generated = 0;

		$return[$pool_id]["data"] = array();
		$tmp_data   = array();
		$data_found = 0;
		
		if (is_array($unknown_events)) {
			$i = 0;
			while ($current_timestamp >= $unknown_events[$i]["time_to"] ) {
				// Skip unknown events in past
				array_splice($unknown_events, $i,1);
				$i++;
				if (!isset($unknown_events[$i])) {
					break;
				}
			}
			if (isset($current_unknown)) {

				// check if recovered from unknown status
				if(is_array($unknown_events) && isset($current_unknown)) {
					if (   (($current_timestamp+$slice_size) > $current_unknown["time_to"])
						&& ($current_timestamp < $current_unknown["time_to"])
						&& ($in_unknown_status == 1) ) {
						// Recovered from unknown

						if (   ($current_unknown["time_to"] > $current_timestamp)
							&& ($expected_data_generated == 0) ) {
							// also add the "expected" data
							$tmp_data["utimestamp"] = $current_timestamp;
							if ($in_unknown_status == 1) {
								$tmp_data["datos"]  = null;
							}
							else {
								$tmp_data["datos"]  = $last_inserted_value;
							}
							$return[$pool_id]["utimestamp"] = $current_timestamp;
							array_push($return[$pool_id]["data"], $tmp_data);
							$expected_data_generated = 1;
						}


						$tmp_data["utimestamp"] = $current_unknown["time_to"];
						$tmp_data["datos"]      = $last_inserted_value;
						// debug purpose
						$tmp_data["obs"]        = "event recovery data";
						
						$return[$pool_id]["utimestamp"] = $current_timestamp;
						array_push($return[$pool_id]["data"], $tmp_data);
						$data_found = 1;
						$in_unknown_status = 0;
					}

					if (   (($current_timestamp+$slice_size) > $current_unknown["time_from"])
						&& (($current_timestamp+$slice_size) < $current_unknown["time_to"])
						&& ($in_unknown_status == 0) ) {
						// Add unknown state detected

						if ( $current_unknown["time_from"] < ($current_timestamp+$slice_size)) {
							if (   ($current_unknown["time_from"] > $current_timestamp)
								&& ($expected_data_generated == 0) ) {
								// also add the "expected" data
								$tmp_data["utimestamp"] = $current_timestamp;
								if ($in_unknown_status == 1) {
									$tmp_data["datos"]  = null;
								}
								else {
									$tmp_data["datos"]  = $last_inserted_value;
								}
								$return[$pool_id]["utimestamp"] = $current_timestamp;
								array_push($return[$pool_id]["data"], $tmp_data);
								$expected_data_generated = 1;
							}

							$tmp_data["utimestamp"] = $current_unknown["time_from"];
							$tmp_data["datos"]      = null;
							// debug purpose
							$tmp_data["obs"] = "event data";
							$return[$pool_id]["utimestamp"] = $current_timestamp;
							array_push($return[$pool_id]["data"], $tmp_data);
							$data_found = 1;
						}
						$in_unknown_status = 1;
					}

					if ( ($in_unknown_status == 0) && ($current_timestamp >= $current_unknown["time_to"]) ) {
						$current_unknown = array_shift($unknown_events);
					}
				}
			} // unknown events handle
		}

		// Search for data
		$i=0;
		
		if (is_array($raw_data)) {
			foreach ($raw_data as $data) {
				if ( ($data["utimestamp"] >= $current_timestamp)
				  && ($data["utimestamp"] < ($current_timestamp+$slice_size)) ) {
					// Data in block, push in, and remove from $raw_data (processed)

					if (   ($data["utimestamp"] > $current_timestamp)
						&& ($expected_data_generated == 0) ) {
						// also add the "expected" data
						$tmp_data["utimestamp"] = $current_timestamp;
						if ($in_unknown_status == 1) {
							$tmp_data["datos"]  = null;
						}
						else {
							$tmp_data["datos"]  = $last_inserted_value;
						}
						$tmp_data["obs"] = "expected data";
						$return[$pool_id]["utimestamp"] = $current_timestamp;
						array_push($return[$pool_id]["data"], $tmp_data);
						$expected_data_generated = 1;
					}

					$tmp_data["utimestamp"] = intval($data["utimestamp"]);
					$tmp_data["datos"]      = $data["datos"];
					// debug purpose
					$tmp_data["obs"] = "real data";

					$return[$pool_id]["utimestamp"] = $current_timestamp;
					array_push($return[$pool_id]["data"], $tmp_data);

					$last_inserted_value = $data["datos"];
					$last_timestamp      = intval($data["utimestamp"]);

					unset($raw_data[$i]);
					$data_found = 1;
					$in_unknown_status = 0;
				}
				elseif ($data["utimestamp"] > ($current_timestamp+$slice_size)) {
					// Data in future, stop searching new ones
					break;
				}
			}
			$i++;
		}

		if ($data_found == 0) {
			// No data found, lug the last_value until SECONDS_1DAY + 2*modules_get_interval
			// UNKNOWN!

			if (($current_timestamp > $now) || (($current_timestamp - $last_timestamp) > (SECONDS_1DAY + 2*$module_interval))) {
				if (isset($last_inserted_value)) {
					// unhandled unknown status control
					$unhandled_time_unknown = $current_timestamp - (SECONDS_1DAY + 2*$module_interval) - $last_timestamp;
					if ($unhandled_time_unknown > 0) {
						// unhandled unknown status detected. Add to previous pool
						$tmp_data["utimestamp"] = intval($last_timestamp) +  (SECONDS_1DAY + 2*$module_interval);
						$tmp_data["datos"]      = null;
						// debug purpose
						$tmp_data["obs"] = "unknown extra";
						// add to previous pool if needed
						if (isset($return[$pool_id-1])) {
							array_push($return[$pool_id-1]["data"], $tmp_data);
						}
					}
				}
				$last_inserted_value = null;
			}

			$tmp_data["utimestamp"] = $current_timestamp;

			if ($in_unknown_status == 1) {
				$tmp_data["datos"]  = null;
			}
			else {
				$tmp_data["datos"]  = $last_inserted_value;
			}
			// debug purpose
			$tmp_data["obs"] = "virtual data";
			
			$return[$pool_id]["utimestamp"] = $current_timestamp;
			array_push($return[$pool_id]["data"], $tmp_data);
		}

		$pool_id++;
		$current_timestamp += $slice_size;
		
	}
	$data = array('type' => 'array', 'data' => $return);
	returnData('json', $return, ';');
}


/**
 * Get modules id for an agent, and print the result like a csv.
 *
 * @param $id Id of agent.
 * @param array $name name of module.
 * @param $thrash1 Don't use.
 *
 *  pi.php?op=get&op2=module_id&id=5&other=Host%20Alive&apipass=1234&user=admin&pass=pandora
 *
 * @param $thrash3 Don't use.
 */
function api_get_module_id($id , $thrash1 , $name, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id, 'csv')) return;

	$sql = sprintf('SELECT id_agente_modulo
		FROM tagente_modulo WHERE id_agente = %d
		AND nombre = "%s" AND disabled = 0
		AND delete_pending = 0', $id , $name['data']);

	$module_id = db_get_all_rows_sql($sql);

	if (count($module_id) > 0 and $module_id !== false) {
		$data = array('type' => 'array', 'data' => $module_id);
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_module_id', 'does not exist module or agent');
	}
}

/**
 * Get modules for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=group_agent&return_type=csv&other=14&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_group_agent($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf("SELECT groups.nombre nombre 
		FROM tagente agents, tgrupo groups
		WHERE id_agente = %d AND agents.disabled = 0
			AND groups.disabled = 0
			AND agents.id_grupo = groups.id_grupo", $other['data'][0]);
	
	$group_names = db_get_all_rows_sql($sql);
	
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}

/**
 * Get name group for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <name_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=group_agent&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_group_agent_by_name($thrash1, $thrash2, $other, $thrash3) {
	
	$group_names =array();
	
	if (is_metaconsole()) {
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup
				WHERE disabled = 0");
			
		if ($servers === false)
			$servers = array();
		
		
		foreach($servers as $server) {
			if (metaconsole_connect($server) == NOERR) {
				$agent_id = agents_get_agent_id($other['data'][0],true);
				
				if ($agent_id) {
					$sql = sprintf("SELECT groups.nombre nombre 
						FROM tagente agents, tgrupo groups
						WHERE id_agente = %d 
							AND agents.id_grupo = groups.id_grupo",$agent_id);
					$group_server_names = db_get_all_rows_sql($sql);
					
					if ($group_server_names) {
						foreach($group_server_names as $group_server_name) {
							$group_names[] = $group_server_name;
						}
					}
				}
			}
			metaconsole_restore_db();
		}
	}
	else {
		$agent_id = agents_get_agent_id($other['data'][0],true);
		if (!util_api_check_agent_and_print_error($agent_id, 'csv')) return;
	
		$sql = sprintf("SELECT groups.nombre nombre 
			FROM tagente agents, tgrupo groups
			WHERE id_agente = %d 
				AND agents.id_grupo = groups.id_grupo",$agent_id);
		$group_names = db_get_all_rows_sql($sql);
	}
	
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}


/**
 * Get name group for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <alias> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=group_agent_by_alias&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_group_agent_by_alias($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', 'csv');
		return;
	}
	$group_names =array();
	
	if (is_metaconsole()) {
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup
				WHERE disabled = 0");
			
		if ($servers === false)
			$servers = array();
		
		
		foreach($servers as $server) {
			if (metaconsole_connect($server) == NOERR) {
				$sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ",$other['data'][0]);
				$agent_id = db_get_all_rows_sql($sql);
				
				foreach ($agent_id as &$id) {
					$sql = sprintf("SELECT groups.nombre nombre 
						FROM tagente agents, tgrupo groups
						WHERE id_agente = %d 
							AND agents.id_grupo = groups.id_grupo",$id['id_agente']);
					$group_server_names = db_get_all_rows_sql($sql);
					
					if ($group_server_names) {
						foreach($group_server_names as $group_server_name) {
							$group_names[] = $group_server_name;
						}
					}
				}
			}
			metaconsole_restore_db();
		}
	}
	else {
		$sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ",$other['data'][0]);
		$agent_id = db_get_all_rows_sql($sql);

		foreach ($agent_id as &$id) {
			if(!users_access_to_agent($id['id_agente'])) continue;

			$sql = sprintf("SELECT groups.nombre nombre 
			FROM tagente agents, tgrupo groups
			WHERE id_agente = %d 
				AND agents.id_grupo = groups.id_grupo",$id['id_agente']);
			$group_name = db_get_all_rows_sql($sql);
			$group_names[] = $group_name[0];
		}
	}
	
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}

/**
 * Get id server whare agent is located, and print all the result like a csv.
 * 
 * @param $id name of agent.
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 *  example:
 *  
 *  api.php?op=get&op2=locate_agent&return_type=csv&id=Pepito&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_get_locate_agent($id, $thrash1, $thrash2, $thrash3) {
	if (!is_metaconsole()) 
		return;
	
	$servers = db_get_all_rows_sql ("SELECT *
		FROM tmetaconsole_setup
			WHERE disabled = 0");
		
	if ($servers === false)
		$servers = array();
	
	foreach($servers as $server) {
		$id_server = $server['id'];
		if (metaconsole_connect($server) == NOERR) {
			$agent_id = agents_get_agent_id($id,true);
			
			if ($agent_id && agents_check_access_agent($agent_id)) {
				$group_servers[]['server'] = $id_server;
			}
		}
		metaconsole_restore_db();
	}
	
	if (count($group_servers) > 0 and $group_servers !== false) {
		$data = array('type' => 'array', 'data' => $group_servers);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_locate_agents', 'No agents located.');
	}
}


/**
 * Get id group for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <name_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=id_group_agent_by_name&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_id_group_agent_by_name($thrash1, $thrash2, $other, $thrash3) {
	if (is_metaconsole()) {
		return;
	}

	$group_names =array();
	
	if (is_metaconsole()) {
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup
				WHERE disabled = 0");
			
		if ($servers === false)
			$servers = array();
		
		
		foreach($servers as $server) {
			if (metaconsole_connect($server) == NOERR) {
				$agent_id = agents_get_agent_id($other['data'][0],true);
				
				if ($agent_id) {
					$sql = sprintf("SELECT groups.id_grupo id_group 
						FROM tagente agents, tgrupo groups
						WHERE id_agente = %d 
							AND agents.id_grupo = groups.id_grupo",$agent_id);
					$group_server_names = db_get_all_rows_sql($sql);
					
					if ($group_server_names) {
						foreach($group_server_names as $group_server_name) {
							$group_names[] = $group_server_name;
						}
					}
				}
			}
			metaconsole_restore_db();
		}
	}
	else {
		$agent_id = agents_get_agent_id($other['data'][0],true);

		if(!util_api_check_agent_and_print_error($agent_id, 'csv')) return;
		
		$sql = sprintf("SELECT groups.id_grupo id_group
			FROM tagente agents, tgrupo groups
			WHERE id_agente = %d 
				AND agents.id_grupo = groups.id_grupo",$agent_id);
		$group_names = db_get_all_rows_sql($sql);
	}
	
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		
		returnData('csv', $data);
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}


/**
 * Get id group for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <alias> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=id_group_agent_by_alias&return_type=csv&other=Nova&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_id_group_agent_by_alias($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', 'csv');
		return;
	}

	$group_names =array();

	if (is_metaconsole()) {
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup
				WHERE disabled = 0");
			
		if ($servers === false)
			$servers = array();
		
		
		foreach($servers as $server) {
			if (metaconsole_connect($server) == NOERR) {
				$sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ",$other['data'][0]);
				$agent_id = db_get_all_rows_sql($sql);
				
				foreach ($agent_id as &$id) {
					$sql = sprintf("SELECT groups.id_grupo id_group 
						FROM tagente agents, tgrupo groups
						WHERE id_agente = %d 
							AND agents.id_grupo = groups.id_grupo",$id['id_agente']);
					$group_server_names = db_get_all_rows_sql($sql);
					
					if ($group_server_names) {
						foreach($group_server_names as $group_server_name) {
							$group_names[] = $group_server_name;
						}
					}
				}
			}
			metaconsole_restore_db();
		}
	}
	else {
		$sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ",$other['data'][0]);
		$agent_id = db_get_all_rows_sql($sql);

		foreach ($agent_id as &$id) {
			if(!users_access_to_agent($id['id_agente'])) continue;

			$sql = sprintf("SELECT groups.id_grupo id_group
			FROM tagente agents, tgrupo groups
			WHERE id_agente = %d 
				AND agents.id_grupo = groups.id_grupo",$id['id_agente']);
			$group_name = db_get_all_rows_sql($sql);
			$group_names[] = $group_name[0];
		}
		
	}
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		returnData('csv', $data);
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}

/**
 * Get all policies, possible filtered by agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=policies&return_type=csv&other=&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_policies($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'csv');
		return;
	}

	$user_groups = implode (',', array_keys(users_get_groups($config["id_user"], "AW")));

	if ($other['data'][0] != "") {
		if (!users_access_to_agent($other['data'][0])) {
			returnError ('forbidden', 'csv');
			return;
		}
		$where = ' AND pol_agents.id_agent = ' . $other['data'][0];

		$sql = sprintf("SELECT policy.id, name, id_agent
			FROM tpolicies AS policy, tpolicy_agents AS pol_agents 
			WHERE policy.id = pol_agents.id_policy %s AND id_group IN (%s)",
			$where, $user_groups);
	}
	else {
		$sql = "SELECT id, name FROM tpolicies AS policy WHERE id_group IN ($user_groups)";
	}

	$policies = db_get_all_rows_sql($sql);
	
	if (count($policies) > 0 and $policies !== false) {
		$data = array('type' => 'array', 'data' => $policies);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_get_policies', 'No policies retrieved.');
	}
}

/**
 * Get policy modules, possible filtered by agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=policy_modules&return_type=csv&other=2&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_policy_modules($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$where = '';
	
	if ($other['data'][0] == "") {
		returnError('error_policy_modules', 'Error retrieving policy modules. Id_policy cannot be left blank.');
		return;
	}
	
	$policies = enterprise_hook('policies_get_modules_api',
		array($other['data'][0], $other['data'][1]));
	
	if ($policies === ENTERPRISE_NOT_HOOK) {
		returnError('error_policy_modules', 'Error retrieving policy modules.');
		return;
	}
	
	if (count($policies) > 0 and $policies !== false) {
		$data = array('type' => 'array', 'data' => $policies);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_policy_modules', 'No policy modules retrieved.');
	}
}


/**
 * Create a network module in agent. And return the id_agent_module of new module.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_network_module&id=pepito&other=prueba|0|7|1|10|15|0|16|18|0|15|0|www.google.es|0||0|180|0|0|0|0|latency%20ping&other_mode=url_encode_separator_| 
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_network_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	$idAgent = agents_get_agent_id($agentName);

	if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
		return;
	}
	
	if ($other['data'][2] < 6 or $other['data'][2] > 18) {
		returnError('error_create_network_module',
			__('Error in creation network module. Id_module_type is not correct for network modules.'));
		return;
	}
	
	$name = $other['data'][0];
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][22];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'id_module_group' => $other['data'][3],
		'min_warning' => $other['data'][4],
		'max_warning' => $other['data'][5],
		'str_warning' => $other['data'][6],
		'min_critical' => $other['data'][7],
		'max_critical' => $other['data'][8],
		'str_critical' => $other['data'][9],
		'min_ff_event' => $other['data'][10],
		'history_data' => $other['data'][11],
		'ip_target' => $other['data'][12],
		'tcp_port' => $other['data'][13],
		'snmp_community' => $other['data'][14],
		'snmp_oid' => $other['data'][15],
		'module_interval' => $other['data'][16],
		'post_process' => $other['data'][17],
		'min' => $other['data'][18],
		'max' => $other['data'][19],
		'custom_id' => $other['data'][20],
		'descripcion' => $other['data'][21],
		'id_modulo' => 2,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $other['data'][23],
		'each_ff' => $other['data'][24],
		'min_ff_event_normal' => $other['data'][25],
		'min_ff_event_warning' => $other['data'][26],
		'min_ff_event_critical' => $other['data'][27],
		'critical_inverse' => $other['data'][28],
		'warning_inverse' => $other['data'][29]
	);

	if ( ! $values['descripcion'] ) {
		$values['descripcion'] = '';	// Column 'descripcion' cannot be null
	}
	if ( ! $values['module_macros'] ) {
		$values['module_macros'] = '';	// Column 'module_macros' cannot be null
	}

	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_network_module', __('Error in creation network module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a network module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of the network module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critidcal>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_network_module&id=271&other=156|0|2|10|15||16|18||7|0|127.0.0.1|0||0|300|30.00|0|0|0|latency%20ping%20modified%20by%20the%20Api&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_network_module($id_module, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_network_module',
			__('Error updating network module. Module name cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error(
		modules_get_agentmodule_agent($id_module), 'string', 'AW')
	) {
		return;
	}
	
	$check_id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);
	
	if (!$check_id_module) {
		returnError('error_update_network_module',
			__('Error updating network module. Id_module doesn\'t exist.'));
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
			return;
		}
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo',
				'tagente_modulo',
				array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_network_module',
					__('Error updating network module. Id_module exists in the new agent.'));
				return;
			}
		}
	}
	
	$network_module_fields = array('id_agente',
		'disabled',
		'id_module_group',
		'min_warning',
		'max_warning',
		'str_warning', 
		'min_critical',
		'max_critical',
		'str_critical',
		'min_ff_event',
		'history_data',
		'ip_target',
		'tcp_port',
		'snmp_community',
		'snmp_oid',
		'module_interval',
		'post_process',
		'min',
		'max',
		'custom_id',
		'descripcion',
		'disabled_types_event',
		'module_macros',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical',
		'critical_inverse',
		'warning_inverse',
		'policy_linked');

	$values = array();
	$cont = 0;
	foreach ($network_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	$values['policy_linked'] = 0;


	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_network_module', 'Error updating network module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Network module updated.')));	
}

/**
 * Create a plugin module in agent. And return the id_agent_module of new module.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<tcp_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_plugin_module&id=pepito&other=prueba|0|1|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function api_set_create_plugin_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][22] == "") {
		returnError('error_create_plugin_module', __('Error in creation plugin module. Id_plugin cannot be left blank.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
 
	if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][26];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$name = $other['data'][0];
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'id_module_group' => $other['data'][3],
		'min_warning' => $other['data'][4],
		'max_warning' => $other['data'][5],
		'str_warning' => $other['data'][6],
		'min_critical' => $other['data'][7],
		'max_critical' => $other['data'][8],
		'str_critical' => $other['data'][9],
		'min_ff_event' => $other['data'][10],
		'history_data' => $other['data'][11],
		'ip_target' => $other['data'][12],
		'tcp_port' => $other['data'][13],
		'snmp_community' => $other['data'][14],
		'snmp_oid' => $other['data'][15],
		'module_interval' => $other['data'][16],
		'post_process' => $other['data'][17],
		'min' => $other['data'][18],
		'max' => $other['data'][19],
		'custom_id' => $other['data'][20],
		'descripcion' => $other['data'][21],
		'id_modulo' => 4,
		'id_plugin' => $other['data'][22],
		'plugin_user' => $other['data'][23],
		'plugin_pass' => $other['data'][24],
		'plugin_parameter' => $other['data'][25],
		'disabled_types_event' => $disabled_types_event,
		'macros' => base64_decode ($other['data'][27]),
		'module_macros' => $other['data'][28],
		'each_ff' => $other['data'][29],
		'min_ff_event_normal' => $other['data'][30],
		'min_ff_event_warning' => $other['data'][31],
		'min_ff_event_critical' => $other['data'][32],
		'critical_inverse' => $other['data'][33],
		'warning_inverse' => $other['data'][34]
	);
	
	if ( ! $values['descripcion'] ) {
		$values['descripcion'] = '';	// Column 'descripcion' cannot be null
	}
	if ( ! $values['module_macros'] ) {
		$values['module_macros'] = '';	// Column 'module_macros' cannot be null
	}

	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_plugin_module', __('Error in creation plugin module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a plugin module in agent. And return the id_agent_module of new module.
 * @param string $id Id of the plugin module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_plugin_module&id=293&other=156|0|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module($id_module, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_plugin_module', __('Error updating plugin module. Id_module cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error(
		modules_get_agentmodule_agent($id_module), 'string', 'AW')
	) {
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
			return;
		}
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_plugin_module', __('Error updating plugin module. Id_module exists in the new agent.'));
				return;
			}
		}
		// Check if agent exists
		$check_id_agent = db_get_value ('id_agente', 'tagente', 'id_agente', $other['data'][0]);
		if (!$check_id_agent) {
			returnError('error_update_data_module', __('Error updating plugin module. Id_agent doesn\'t exist.'));
			return;
		}
	}
	
	$plugin_module_fields = array('id_agente',
		'disabled',
		'id_module_group',
		'min_warning',
		'max_warning',
		'str_warning', 
		'min_critical',
		'max_critical',
		'str_critical',
		'min_ff_event',
		'history_data',
		'ip_target',
		'tcp_port',
		'snmp_community',
		'snmp_oid',
		'module_interval',
		'post_process',
		'min',
		'max',
		'custom_id',
		'descripcion',
		'id_plugin',
		'plugin_user',
		'plugin_pass',
		'plugin_parameter',
		'disabled_types_event',
		'macros',
		'module_macros',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical',
		'critical_inverse',
		'warning_inverse',
		'policy_linked');

	$values = array();
	$cont = 0;
	foreach ($plugin_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];

			if( $field === 'macros' ) {
				$values[$field] = base64_decode($values[$field]);
			}
		}
		
		$cont++;
	}

	$values['policy_linked']= 0;
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_plugin_module', 'Error updating plugin module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Plugin module updated.')));	
}

/**
 * Create a data module in agent. And return the id_agent_module of new module. 
 * Note: Only adds database information, this function doesn't alter config file information.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 * 	<description>;<id_module_group>;<min_value>;<max_value>;<post_process>;<module_interval>;<min_warning>;
 * 	<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<history_data>;
 * 	<disabled_types_event>;<module_macros>;<ff_threshold>;<each_ff>;
 *	<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_data_module&id=pepito&other=prueba|0|1|data%20module%20from%20api|1|10|20|10.50|180|10|15||16|20||0&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function api_set_create_data_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][0] == "") {
		returnError('error_create_data_module', __('Error in creation data module. Module_name cannot be left blank.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
		return;
	}
	
	$name = $other['data'][0];
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][16];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'descripcion' => $other['data'][3],
		'id_module_group' => $other['data'][4],
		'min' => $other['data'][5],
		'max' => $other['data'][6],
		'post_process' => $other['data'][7],
		'module_interval' => $other['data'][8],
		'min_warning' => $other['data'][9],
		'max_warning' => $other['data'][10],
		'str_warning' => $other['data'][11],
		'min_critical' => $other['data'][12],
		'max_critical' => $other['data'][13],
		'str_critical' => $other['data'][14],
		'history_data' => $other['data'][15],
		'id_modulo' => 1,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $other['data'][17],
		'min_ff_event' => $other['data'][18],
		'each_ff' => $other['data'][19],
		'min_ff_event_normal' => $other['data'][20],
		'min_ff_event_warning' => $other['data'][21],
		'min_ff_event_critical' => $other['data'][22],
		'ff_timeout' => $other['data'][23],
		'critical_inverse' => $other['data'][24],
		'warning_inverse' => $other['data'][25]
	);
	
	if ( ! $values['descripcion'] ) {
		$values['descripcion'] = '';	// Column 'descripcion' cannot be null
	}
	if ( ! $values['module_macros'] ) {
		$values['module_macros'] = '';	// Column 'module_macros' cannot be null
	}

	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_data_module', __('Error in creation data module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}


/**
 * Create a synthetic module in agent. And return the id_agent_module of new module. 
 * Note: Only adds database information, this function doesn't alter config file information.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module><synthetic_type><AgentName;Operation;NameModule> OR <AgentName;NameModule> OR <Operation;Value>in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_synthetic_module&id=pepito&other=prueba|average|Agent%20Name;AVG;Name%20Module|Agent%20Name2;AVG;Name%20Module2&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function api_set_create_synthetic_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	global $config;
	
	$agentName = $id;
	
	io_safe_input_array($other);
	
	if ($other['data'][0] == "") {
		returnError('error_create_data_module', __('Error in creation synthetic module. Module_name cannot be left blank.'));
		return;
	}
	
	$idAgent = agents_get_agent_id(io_safe_output($agentName),true);
	
	if (!util_api_check_agent_and_print_error($idAgent, 'string', "AW")) return;

	if (!$idAgent) {
		returnError('error_create_data_module', __('Error in creation synthetic module. Agent name doesn\'t exist.'));
		return;
	}
	
	$name = io_safe_output($other['data'][0]);
	$name = io_safe_input($name);
	$id_tipo_modulo = db_get_row_sql ("SELECT id_tipo FROM ttipo_modulo WHERE nombre = 'generic_data'");
	
	$values = array(
		'id_agente' => $idAgent,
		'id_modulo' => 5,
		'custom_integer_1' => 0,
		'custom_integer_2' => 0,
		'prediction_module' => 3,
		'id_tipo_modulo' => $id_tipo_modulo['id_tipo']
	);
	
	if ( ! $values['descripcion'] ) {
		$values['descripcion'] = '';	// Column 'descripcion' cannot be null
	}

	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_data_module', __('Error in creation data module.'));
	}
	else {
		$synthetic_type = $other['data'][1];
		unset($other['data'][0]);
		unset($other['data'][1]);
		
		
		$filterdata = array();
		foreach ($other['data'] as $data) {
			$data = str_replace(array('ADD','SUB','MUL','DIV'),array('+','-','*','/'),$data);
			$split_data = explode(';',$data);
			
			if ( preg_match("/[x\/+*-]/",$split_data[0]) && strlen($split_data[0]) == 1 ) {
				if ( preg_match("/[\/|+|*|-]/",$split_data[0]) && $synthetic_type === 'average' ) {
					returnError("","[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
				}
				
				$operator = strtolower($split_data[0]);
				$data_module = array("",$operator,$split_data[1]);
				
				$text_data = implode('_',$data_module);
				array_push($filterdata,$text_data);
			}
			else {
				if (count($split_data) == 2) {
					$idAgent = agents_get_agent_id(io_safe_output($split_data[0]),true);
					$data_module = array($idAgent,'',$split_data[1]);
					$text_data = implode('_',$data_module);
					array_push($filterdata,$text_data);
				}
				else {
					if (strlen($split_data[1]) > 1 && $synthetic_type != 'average' ) {
						returnError("","[ERROR] You can only use +, -, *, / or x, and you use this: @split_data[1] \n\n");
						return;
					}
					if ( preg_match("/[\/|+|*|-]/",$split_data[1]) && $synthetic_type === 'average' ) {
						returnError("","[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
						return;
					}
					
					$idAgent = agents_get_agent_id(io_safe_output($split_data[0]),true);
					$operator = strtolower($split_data[1]);
					$data_module = array($idAgent,$operator,$split_data[2]);
					$text_data = implode('_',$data_module);
					array_push($filterdata,$text_data);
				}
			}
		}
		
		$serialize_ops = implode(',',$filterdata);
		
		//modules_create_synthetic_operations
		$synthetic = enterprise_hook('modules_create_synthetic_operations',
			array($idModule, $serialize_ops));
		
		if ($synthetic === ENTERPRISE_NOT_HOOK) {
			returnError('error_synthetic_modules', 'Error Synthetic modules.');
			db_process_sql_delete ('tagente_modulo',
				array ('id_agente_modulo' => $idModule));
			return;
		}
		else {
			$status = AGENT_MODULE_STATUS_NO_DATA;
			switch ($config["dbtype"]) {
				case "mysql":
					$result = db_process_sql_insert ('tagente_estado',
						array ('id_agente_modulo' => $idModule,
							'datos' => 0,
							'timestamp' => '01-01-1970 00:00:00',
							'estado' => $status,
							'id_agente' => (int) $idAgent,
							'utimestamp' => 0,
							'status_changes' => 0,
							'last_status' => $status,
							'last_known_status' => $status
						));
					break;
				case "postgresql":
					$result = db_process_sql_insert ('tagente_estado',
						array ('id_agente_modulo' => $idModule,
							'datos' => 0,
							'timestamp' => null,
							'estado' => $status,
							'id_agente' => (int) $idAgent,
							'utimestamp' => 0,
							'status_changes' => 0,
							'last_status' => $status,
							'last_known_status' => $status
						));
					break;
				case "oracle":
					$result = db_process_sql_insert ('tagente_estado',
						array ('id_agente_modulo' => $idModule,
							'datos' => 0,
							'timestamp' => '#to_date(\'1970-01-01 00:00:00\', \'YYYY-MM-DD HH24:MI:SS\')',
							'estado' => $status,
							'id_agente' => (int) $idAgent,
							'utimestamp' => 0,
							'status_changes' => 0,
							'last_status' => $status,
							'last_known_status' => $status
						));
					break;
			}
			if ($result === false) {
				db_process_sql_delete ('tagente_modulo',
					array ('id_agente_modulo' => $idModule));
				returnError('error_synthetic_modules', 'Error Synthetic modules.');
			}
			else {
				db_process_sql ('UPDATE tagente SET total_count=total_count+1, notinit_count=notinit_count+1 WHERE id_agente=' . (int)$idAgent);
				returnData('string', array('type' => 'string', 'data' => __('Synthetic module created ID: ' . $idModule)));
			}
		}
	}
}

/**
 * Update a data module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of the data module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;<description>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<disabled_types_event>;<module_macros>;<ff_threshold>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>;
 *  <ff_timeout> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_data_module&id=170&other=44|0|data%20module%20modified%20from%20API|6|0|0|50.00|300|10|15||16|18||0&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_data_module($id_module, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_data_module', __('Error updating data module. Id_module cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error(
		modules_get_agentmodule_agent($id_module), 'string', 'AW')
	) {
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
			return;
		}
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_data_module', __('Error updating data module. Id_module exists in the new agent.'));
				return;
			}
		}
		// Check if agent exists
		$check_id_agent = db_get_value ('id_agente', 'tagente', 'id_agente', $other['data'][0]);
		if (!$check_id_agent) {
			returnError('error_update_data_module', __('Error updating data module. Id_agent doesn\'t exist.'));
			return;
		}
	}
	
	$data_module_fields = array('id_agente',
		'disabled',
		'descripcion',
		'id_module_group',
		'min',
		'max', 
		'post_process',
		'module_interval',
		'min_warning',
		'max_warning',
		'str_warning',
		'min_critical',
		'max_critical',
		'str_critical', 
		'history_data',
		'disabled_types_event',
		'module_macros',
		'min_ff_event',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical',
		'ff_timeout',
		'critical_inverse',
		'warning_inverse',
		'policy_linked');

	$values = array();
	$cont = 0;
	foreach ($data_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}

	$values['policy_linked'] = 0;
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_data_module', 'Error updating data module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Data module updated.')));
}


/**
 * Create a SNMP module in agent. And return the id_agent_module of new module. 
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw) 
 * 
 *  api.php?op=set&op2=create_snmp_module&id=pepito&other=prueba|0|15|1|10|15||16|18||15|0|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP%20module%20from%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_| 
 *  
 *  example 2 (snmp v: 1)
 * 
 *  api.php?op=set&op2=create_snmp_module&id=pepito1&other=prueba2|0|15|1|10|15||16|18||15|0|127.0.0.1|60|1|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP module from API&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_snmp_module($id, $thrash1, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][0] == "") {
		returnError('error_create_snmp_module', __('Error in creation SNMP module. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][2] < 15 or $other['data'][2] > 18) {
		returnError('error_create_snmp_module', __('Error in creation SNMP module. Invalid id_module_type for a SNMP module.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
		return;
	}
	
	$name = $other['data'][0];
	
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][27];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][14] == "3") {
		
		if ($other['data'][23] != "AES" and $other['data'][23] != "DES") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_priv_method doesn\'t exist. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][25] != "authNoPriv" and $other['data'][25] != "authPriv" and $other['data'][25] != "noAuthNoPriv") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_sec_level doesn\'t exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][26] != "MD5" and $other['data'][26] != "SHA") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_auth_method doesn\'t exist. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$values = array(
			'id_agente' => $idAgent,
			'disabled' => $other['data'][1],
			'id_tipo_modulo' => $other['data'][2],
			'id_module_group' => $other['data'][3],
			'min_warning' => $other['data'][4],
			'max_warning' => $other['data'][5],
			'str_warning' => $other['data'][6],
			'min_critical' => $other['data'][7],
			'max_critical' => $other['data'][8],
			'str_critical' => $other['data'][9],
			'min_ff_event' => $other['data'][10],
			'history_data' => $other['data'][11],
			'ip_target' => $other['data'][12],
			'tcp_port' => $other['data'][13],
			'tcp_send' => $other['data'][14],
			'snmp_community' => $other['data'][15],
			'snmp_oid' => $other['data'][16],
			'module_interval' => $other['data'][17],
			'post_process' => $other['data'][18],
			'min' => $other['data'][19],
			'max' => $other['data'][20],
			'custom_id' => $other['data'][21],
			'descripcion' => $other['data'][22],
			'id_modulo' => 2,
			'custom_string_1' => $other['data'][23],
			'custom_string_2' => $other['data'][24],
			'custom_string_3' => $other['data'][25],
			'plugin_parameter' => $other['data'][26],
			'plugin_user' => $other['data'][27],
			'plugin_pass' => $other['data'][28],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][30],
			'min_ff_event_normal' => $other['data'][31],
			'min_ff_event_warning' => $other['data'][32],
			'min_ff_event_critical' => $other['data'][33]
		);
	}
	else {
		$values = array(
			'id_agente' => $idAgent,
			'disabled' => $other['data'][1],
			'id_tipo_modulo' => $other['data'][2],
			'id_module_group' => $other['data'][3],
			'min_warning' => $other['data'][4],
			'max_warning' => $other['data'][5],
			'str_warning' => $other['data'][6],
			'min_critical' => $other['data'][7],
			'max_critical' => $other['data'][8],
			'str_critical' => $other['data'][9],
			'min_ff_event' => $other['data'][10],
			'history_data' => $other['data'][11],
			'ip_target' => $other['data'][12],
			'tcp_port' => $other['data'][13],
			'tcp_send' => $other['data'][14],
			'snmp_community' => $other['data'][15],
			'snmp_oid' => $other['data'][16],
			'module_interval' => $other['data'][17],
			'post_process' => $other['data'][18],
			'min' => $other['data'][19],
			'max' => $other['data'][20],	
			'custom_id' => $other['data'][21],
			'descripcion' => $other['data'][22],
			'id_modulo' => 2,
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][24],
			'min_ff_event_normal' => $other['data'][25],
			'min_ff_event_warning' => $other['data'][26],
			'min_ff_event_critical' => $other['data'][27]
		);
	}
	
	if ( ! $values['descripcion'] ) {
		$values['descripcion'] = '';	// Column 'descripcion' cannot be null
	}

	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_snmp_module', __('Error in creation SNMP module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a SNMP module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example (update snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw) 
 * 
 *  api.php?op=set&op2=update_snmp_module&id=example_snmp_module_name&other=44|0|6|20|25||26|30||15|1|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_| 
 *
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module($id_module, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_snmp_module', __('Error updating SNMP module. Id_module cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error(
		modules_get_agentmodule_agent($id_module), 'string', 'AW')
	) {
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
			return; 
		}
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_snmp_module', __('Error updating SNMP module. Id_module exists in the new agent.'));
				return;
			}
		}
		// Check if agent exists
		$check_id_agent = db_get_value ('id_agente', 'tagente', 'id_agente', $other['data'][0]);
		if (!$check_id_agent) {
			returnError('error_update_data_module', __('Error updating snmp module. Id_agent doesn\'t exist.'));
			return;
		}
	}
	
	# SNMP version 3
	if ($other['data'][13] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_create_snmp_module',
				__('Error in creation SNMP module. snmp3_priv_method doesn\'t exist. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][24] != "authNoPriv"
			and $other['data'][24] != "authPriv"
			and $other['data'][24] != "noAuthNoPriv") {
			
			returnError('error_create_snmp_module',
				__('Error in creation SNMP module. snmp3_sec_level doesn\'t exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][25] != "MD5" and $other['data'][25] != "SHA") {
			returnError('error_create_snmp_module',
				__('Error in creation SNMP module. snmp3_auth_method doesn\'t exist. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$snmp_module_fields = array(
			'id_agente',
			'disabled',
			'id_module_group',
			'min_warning',
			'max_warning',
			'str_warning', 
			'min_critical',
			'max_critical',
			'str_critical',
			'min_ff_event',
			'history_data',
			'ip_target',
			'tcp_port',
			'tcp_send', 
			'snmp_community',
			'snmp_oid',
			'module_interval',
			'post_process',
			'min',
			'max',
			'custom_id',
			'descripcion',
			'custom_string_1', 
			'custom_string_2',
			'custom_string_3',
			'plugin_parameter',
			'plugin_user',
			'plugin_pass',
			'disabled_types_event',
			'each_ff',
			'min_ff_event_normal',
			'min_ff_event_warning',
			'min_ff_event_critical',
			'policy_linked');
	}
	else {
		$snmp_module_fields = array(
			'id_agente',
			'disabled',
			'id_module_group',
			'min_warning',
			'max_warning',
			'str_warning', 
			'min_critical',
			'max_critical',
			'str_critical',
			'min_ff_event',
			'history_data',
			'ip_target',
			'tcp_port',
			'tcp_send', 
			'snmp_community',
			'snmp_oid',
			'module_interval',
			'post_process',
			'min',
			'max',
			'custom_id',
			'descripcion',
			'disabled_types_event',
			'each_ff',
			'min_ff_event_normal',
			'min_ff_event_warning',
			'min_ff_event_critical',
			'policy_linked');
	}
	
	$values = array();
	$cont = 0;
	foreach ($snmp_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}

	$values['policy_linked'] = 0;
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_snmp_module', 'Error updating SNMP module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('SNMP module updated.')));	
}

/**
 * Create new network component.
 * 
 * @param $id string Name of the network component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <network_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<snmp_community>;<id_module_group>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<network_component_group>;<enable_unknown_events>;<each_ff>;
 *  <ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>  in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_network_component&id=example_network_component_name&other=7|network%20component%20created%20by%20Api|300|30|10|public|3||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_network_component($id, $thrash1, $other, $thrash2) {
	global $config;
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_network_component', __('Error creating network component. Network component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] < 6 or $other['data'][0] > 18) {
		returnError('error_set_new_network_component', __('Error creating network component. Incorrect value for Network component type field.'));
		return;
	}
	
	if ($other['data'][17] == "") {
		returnError('error_set_new_network_component', __('Error creating network component. Network component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][18];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][1],
		'module_interval' => $other['data'][2],
		'max' => $other['data'][3],
		'min' => $other['data'][4],
		'snmp_community' => $other['data'][5],
		'id_module_group' => $other['data'][6],
		'id_modulo' => 2,
		'max_timeout' => $other['data'][7],
		'history_data' => $other['data'][8],
		'min_warning' => $other['data'][9],
		'max_warning' => $other['data'][10],
		'str_warning' => $other['data'][11],
		'min_critical' => $other['data'][12],
		'max_critical' => $other['data'][13],
		'str_critical' => $other['data'][14],
		'min_ff_event' => $other['data'][15],
		'post_process' => $other['data'][16],
		'id_group' => $other['data'][17],
		'disabled_types_event' => $disabled_types_event,
		'each_ff' => $other['data'][19],
		'min_ff_event_normal' => $other['data'][20],
		'min_ff_event_warning' => $other['data'][21],
		'min_ff_event_critical' => $other['data'][22]);
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_network_component', __('Error creating network component. This network component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][17], $values);
	
	if (!$id)
		returnError('error_set_new_network_component', 'Error creating network component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new plugin component.
 * 
 * @param $id string Name of the plugin component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <plugin_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<module_port>;<id_module_group>;<id_plugin>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<plugin_component_group>;<enable_unknown_events>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_plugin_component&id=example_plugin_component_name&other=2|plugin%20component%20created%20by%20Api|300|30|10|66|3|2|example_user|example_pass|-p%20max||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_plugin_component($id, $thrash1, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id == "") {
		returnError('error_set_new_plugin_component',
			__('Error creating plugin component. Plugin component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][7] == "") {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. Incorrect value for Id plugin.'));
		return;
	}
	
	if ($other['data'][21] == "") {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. Plugin component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][12];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][1],
		'module_interval' => $other['data'][2],
		'max' => $other['data'][3],
		'min' => $other['data'][4],
		'tcp_port' => $other['data'][5],
		'id_module_group' => $other['data'][6],
		'id_modulo' => 4,
		'id_plugin' => $other['data'][7],
		'plugin_user' => $other['data'][8],
		'plugin_pass' => $other['data'][9],
		'plugin_parameter' => $other['data'][10],
		'max_timeout' => $other['data'][11],
		'history_data' => $other['data'][12],
		'min_warning' => $other['data'][13],
		'max_warning' => $other['data'][14],
		'str_warning' => $other['data'][15],
		'min_critical' => $other['data'][16],
		'max_critical' => $other['data'][17],
		'str_critical' => $other['data'][18],
		'min_ff_event' => $other['data'][19],
		'post_process' => $other['data'][20],
		'id_group' => $other['data'][21],
		'disabled_types_event' => $disabled_types_event,
		'each_ff' => $other['data'][23],
		'min_ff_event_normal' => $other['data'][24],
		'min_ff_event_warning' => $other['data'][25],
		'min_ff_event_critical' => $other['data'][26]);
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. This plugin component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][21], $values);
	
	if (!$id)
		returnError('error_set_new_plugin_component', 'Error creating plugin component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new SNMP component.
 * 
 * @param $id string Name of the SNMP component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <snmp_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<id_module_group>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<snmp_version>;<snmp_oid>;<snmp_community>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<module_port>;<snmp3_privacy_method>;<snmp3_privacy_pass>;<snmp3_auth_method>;<snmp3_security_level>;<snmp_component_group>;<enable_unknown_events>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_snmp_component&id=example_snmp_component_name&other=16|SNMP%20component%20created%20by%20Api|300|30|10|3||1|10|20|str|21|30|str1|15|50.00|3|.1.3.6.1.2.1.2.2.1.8.2|public|example_auth_user|example_auth_pass|66|AES|example_priv_pass|MD5|authNoPriv|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_snmp_component($id, $thrash1, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. SNMP component name cannot be left blank.'));
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}
	
	if ($other['data'][0] < 15 or $other['data'][0] > 17) {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. Incorrect value for Snmp component type field.'));
		return;
	}
	
	if ($other['data'][25] == "") {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. Snmp component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][27];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][16] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_set_new_snmp_component', __('Error creating SNMP component. snmp3_priv_method doesn\'t exist. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][25] != "authNoPriv"
			and $other['data'][25] != "authPriv"
			and $other['data'][25] != "noAuthNoPriv") {
			
			returnError('error_set_new_snmp_component',
				__('Error creating SNMP component. snmp3_sec_level doesn\'t exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][24] != "MD5" and $other['data'][24] != "SHA") {
			returnError('error_set_new_snmp_component',
				__('Error creating SNMP component. snmp3_auth_method doesn\'t exist. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$values = array ( 
			'description' => $other['data'][1],
			'module_interval' => $other['data'][2],
			'max' => $other['data'][3],
			'min' => $other['data'][4],
			'id_module_group' => $other['data'][5],
			'max_timeout' => $other['data'][6],
			'history_data' => $other['data'][7],
			'min_warning' => $other['data'][8],
			'max_warning' => $other['data'][9],
			'str_warning' => $other['data'][10],
			'min_critical' => $other['data'][11],
			'max_critical' => $other['data'][12],
			'str_critical' => $other['data'][13],
			'min_ff_event' => $other['data'][14],
			'post_process' => $other['data'][15],
			'tcp_send' => $other['data'][16],
			'snmp_oid' => $other['data'][17],
			'snmp_community' => $other['data'][18],
			'plugin_user' => $other['data'][19],		// snmp3_auth_user
			'plugin_pass' => $other['data'][20],		// snmp3_auth_pass
			'tcp_port' => $other['data'][21],
			'id_modulo' => 2,
			'custom_string_1' => $other['data'][22],	// snmp3_privacy_method
			'custom_string_2' => $other['data'][23],	// snmp3_privacy_pass
			'plugin_parameter' => $other['data'][24],	// snmp3_auth_method
			'custom_string_3' => $other['data'][25],	// snmp3_security_level
			'id_group' => $other['data'][26],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][28],
			'min_ff_event_normal' => $other['data'][29],
			'min_ff_event_warning' => $other['data'][30],
			'min_ff_event_critical' => $other['data'][31]
			);
	}
	else {
		$values = array ( 
			'description' => $other['data'][1],
			'module_interval' => $other['data'][2],
			'max' => $other['data'][3],
			'min' => $other['data'][4],
			'id_module_group' => $other['data'][5],
			'max_timeout' => $other['data'][6],
			'history_data' => $other['data'][7],
			'min_warning' => $other['data'][8],
			'max_warning' => $other['data'][9],
			'str_warning' => $other['data'][10],
			'min_critical' => $other['data'][11],
			'max_critical' => $other['data'][12],
			'str_critical' => $other['data'][13],
			'min_ff_event' => $other['data'][14],
			'post_process' => $other['data'][15],
			'tcp_send' => $other['data'][16],
			'snmp_oid' => $other['data'][17],
			'snmp_community' => $other['data'][18],
			'plugin_user' => '',
			'plugin_pass' => '',
			'tcp_port' => $other['data'][21],
			'id_modulo' => 2,
			'id_group' => $other['data'][22],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][24],
			'min_ff_event_normal' => $other['data'][25],
			'min_ff_event_warning' => $other['data'][26],
			'min_ff_event_critical' => $other['data'][27]
			);
	}
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. This SNMP component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][25], $values);
	
	if (!$id)
		returnError('error_set_new_snmp_component', 'Error creating SNMP component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new local (data) component.
 * 
 * @param $id string Name of the local component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <description>;<id_os>;
 *  <local_component_group>;<configuration_data>;<enable_unknown_events>;
 *  <ff_threshold>;<each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;
 *  <ff_threshold_critical>;<ff_timeout>  in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as
 *  othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_local_component&id=example_local_component_name&other=local%20component%20created%20by%20Api~5~12~module_begin%0dmodule_name%20example_local_component_name%0dmodule_type%20generic_data%0dmodule_exec%20ps%20|%20grep%20pid%20|%20wc%20-l%0dmodule_interval%202%0dmodule_end&other_mode=url_encode_separator_~
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_local_component($id, $thrash1, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_local_component',
			__('Error creating local component. Local component name cannot be left blank.'));
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_set_new_local_component',
			__('Error creating local component. Local component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][4];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][0],
		'id_network_component_group' => $other['data'][2],
		'disabled_types_event' => $disabled_types_event,
		'min_ff_event' => $other['data'][5],
		'each_ff' => $other['data'][6],
		'min_ff_event_normal' => $other['data'][7],
		'min_ff_event_warning' => $other['data'][8],
		'min_ff_event_critical' => $other['data'][9],
		'ff_timeout' => $other['data'][10]);
	
	$name_check = enterprise_hook('local_components_get_local_components',
		array(array('name' => $id), 'name'));
	
	if ($name_check === ENTERPRISE_NOT_HOOK) {
		returnError('error_set_new_local_component',
			__('Error creating local component.'));
		return;
	}
	
	if ($name_check !== false) {
		returnError('error_set_new_local_component',
			__('Error creating local component. This local component already exists.'));
		return;
	}
	
	$id = enterprise_hook('local_components_create_local_component',
		array($id, $other['data'][3], $other['data'][1], $values));
	
	if (!$id)
		returnError('error_set_new_local_component', 'Error creating local component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Get module data value from all agents filter by module name. And return id_agents, agent_name and module value.
 * 
 * @param $id string Name of the module.
 * @param $thrash1 Don't use.
 * @param array $other Don't use.
 *  example:
 *  
 *  api.php?op=get&op2=module_value_all_agents&id=example_module_name
 *  
 * @param $thrash2 Don't use.

 */
function api_get_module_value_all_agents($id, $thrash1, $other, $thrash2) {
	global $config;
	if (defined ('METACONSOLE')) {
		return;
	}

	if ($id == "") {
		returnError('error_get_module_value_all_agents',
			__('Error getting module value from all agents. Module name cannot be left blank.'));
		return;
	}

	$id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'nombre', $id);

	if ($id_module === false) {
		returnError('error_get_module_value_all_agents',
			__('Error getting module value from all agents. Module name doesn\'t exist.'));
		return;
	}

	$groups = '1 = 1';
	if (!is_user_admin($config['id_user'])) {
		$user_groups = implode (',', array_keys(users_get_groups()));
		$groups = "(id_grupo IN ($user_groups) OR id_group IN ($user_groups))";
	}

	$sql = sprintf( "SELECT agent.id_agente, agent.alias, module_state.datos, agent.nombre
		FROM tagente agent LEFT JOIN tagent_secondary_group tasg ON agent.id_agente = tasg.id_agent, tagente_modulo module, tagente_estado module_state
		WHERE agent.id_agente = module.id_agente AND module.id_agente_modulo=module_state.id_agente_modulo AND module.nombre = '%s'
		AND %s", $id, $groups);

	$module_values = db_get_all_rows_sql($sql);

	if (!$module_values) {
		returnError('error_get_module_value_all_agents', 'Error getting module values from all agents.');
	}
	else {
		$data = array('type' => 'array', 'data' => $module_values);
		returnData('csv', $data, ';');
	}
}

/**
 * Create an alert template. And return the id of new template.
 * 
 * @param string $id Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <type>;<description>;<id_alert_action>;
 *  <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *  <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *  <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example 1 (condition: regexp =~ /pp/, action: Mail to XXX, max_alert: 10, min_alert: 0, priority: WARNING, group: databases):
 *  api.php?op=set&op2=create_alert_template&id=pepito&other=regex|template%20based%20in%20regexp|1||||pp|1||||10|0|||||||||||||3&other_mode=url_encode_separator_|
 *  
 * 	example 2 (condition: value is not between 5 and 10, max_value: 10.00, min_value: 5.00, time_from: 00:00:00, time_to: 15:00:00, priority: CRITICAL, group: Servers):
 *  api.php?op=set&op2=create_alert_template&id=template_min_max&other=max_min|template%20based%20in%20range|NULL||||||10|5||||00:00:00|15:00:00|||||||||||4|2&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_create_alert_template($name, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($name == "") {
		returnError('error_create_alert_template',
			__('Error creating alert template. Template name cannot be left blank.'));
		return;
	}
	
	$template_name = $name;
	
	$type = $other['data'][0];
	
	if ($other['data'][2] != "") {
		$values = array(
			'description' => $other['data'][1],
			'id_alert_action' => $other['data'][2],
			'field1' => $other['data'][3],
			'field2' => $other['data'][4],
			'field3' => $other['data'][5],
			'value' => $other['data'][6],
			'matches_value' => $other['data'][7],
			'max_value' => $other['data'][8],
			'min_value' => $other['data'][9],
			'time_threshold' => $other['data'][10],
			'max_alerts' => $other['data'][11],
			'min_alerts' => $other['data'][12],
			'time_from' => $other['data'][13],
			'time_to' => $other['data'][14],
			'monday' => $other['data'][15],
			'tuesday' => $other['data'][16],
			'wednesday' => $other['data'][17],
			'thursday' => $other['data'][18],
			'friday' => $other['data'][19],
			'saturday' => $other['data'][20],
			'sunday' => $other['data'][21],
			'recovery_notify' => $other['data'][22],
			'field2_recovery' => $other['data'][23],
			'field3_recovery' => $other['data'][24],
			'priority' => $other['data'][25],
			'id_group' => $other['data'][26]
		);
	}
	else {
		$values = array(
			'description' => $other['data'][1],
			'field1' => $other['data'][3],
			'field2' => $other['data'][4],
			'field3' => $other['data'][5],
			'value' => $other['data'][6],
			'matches_value' => $other['data'][7],
			'max_value' => $other['data'][8],
			'min_value' => $other['data'][9],
			'time_threshold' => $other['data'][10],
			'max_alerts' => $other['data'][11],
			'min_alerts' => $other['data'][12],
			'time_from' => $other['data'][13],
			'time_to' => $other['data'][14],
			'monday' => $other['data'][15],
			'tuesday' => $other['data'][16],
			'wednesday' => $other['data'][17],
			'thursday' => $other['data'][18],
			'friday' => $other['data'][19],
			'saturday' => $other['data'][20],
			'sunday' => $other['data'][21],
			'recovery_notify' => $other['data'][22],
			'field2_recovery' => $other['data'][23],
			'field3_recovery' => $other['data'][24],
			'priority' => $other['data'][25],
			'id_group' => $other['data'][26]
		);
	}
	
	$id_template = alerts_create_alert_template($template_name, $type, $values);
	
	if (is_error($id_template)) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template', __('Error creating alert template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id_template));
	}
}

/**
 * Update an alert template. And return a message with the result of the operation.
 * 
 * @param string $id_template Id of the template to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <template_name>;<type>;<description>;<id_alert_action>;
 *  <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *  <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *  <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * 
 *  example:
 * 
 * api.php?op=set&op2=update_alert_template&id=38&other=example_template_with_changed_name|onchange|changing%20from%20min_max%20to%20onchange||||||1||||5|1|||1|1|0|1|1|0|0|1|field%20recovery%20example%201|field%20recovery%20example%202|1|8&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_alert_template($id_template, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id_template == "") {
		returnError('error_update_alert_template',
			__('Error updating alert template. Id_template cannot be left blank.'));
		return;
	}
	
	$result_template = alerts_get_alert_template_name($id_template);
	
	if (!$result_template) {
		returnError('error_update_alert_template',
			__('Error updating alert template. Id_template doesn\'t exist.'));
		return;
	}
	
	$fields_template = array('name', 'type', 'description',
		'id_alert_action', 'field1', 'field2', 'field3', 'value',
		'matches_value', 'max_value', 'min_value', 'time_threshold',
		'max_alerts', 'min_alerts', 'time_from', 'time_to', 'monday',
		'tuesday', 'wednesday', 'thursday', 'friday', 'saturday',
		'sunday', 'recovery_notify', 'field2_recovery',
		'field3_recovery', 'priority', 'id_group');
	
	$cont = 0;
	foreach ($fields_template as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$id_template = alerts_update_alert_template($id_template, $values);
	
	if (is_error($id_template)) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template',
			__('Error updating alert template.'));
	}
	else {
		returnData('string',
			array('type' => 'string',
				'data' => __('Correct updating of alert template')));
	}
}

/**
 * Delete an alert template. And return a message with the result of the operation.
 * 
 * @param string $id_template Id of the template to delete.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=delete_alert_template&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_delete_alert_template($id_template, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_template == "") {
		returnError('error_delete_alert_template',
			__('Error deleting alert template. Id_template cannot be left blank.'));
		return;
	}
	
	$result = alerts_delete_alert_template($id_template);
	
	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template',
			__('Error deleting alert template.'));
	}
	else {
		returnData('string', array('type' => 'string',
			'data' => __('Correct deleting of alert template.')));
	}
}

/**
 * Get all alert tamplates, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *  
 *  api.php?op=get&op2=all_alert_templates&return_type=csv&other=;
 * 
 * @param $thrash3 Don't use.
 */
function api_get_all_alert_templates($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];

	if (!check_acl($config["id_user"], 0, "LM")) {
		returnError("forbidden", "csv");
		return;
	}

	$filter_templates = false;
	
	$template = alerts_get_alert_templates();
	
	if ($template !== false) {
		$data['type'] = 'array';
		$data['data'] = $template;
	}
	
	if (!$template) {
		returnError('error_get_all_alert_templates',
			__('Error getting all alert templates.'));
	}
	else {
		returnData('csv', $data, $separator);
	}
}

/**
 * Get an alert tamplate, and print the result like a csv.
 * 
 * @param string $id_template Id of the template to get.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=get&op2=alert_template&id=25
 *    
 * @param $thrash3 Don't use
 */
function api_get_alert_template($id_template, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$filter_templates = false;
	
	if ($id_template != "") {
		$result_template = alerts_get_alert_template_name($id_template);
		
		if (!$result_template) {
			returnError('error_get_alert_template',
				__('Error getting alert template. Id_template doesn\'t exist.'));
			return;
		}
		
		$filter_templates = array('id' => $id_template);
	}
	
	$template = alerts_get_alert_templates($filter_templates,
		array('id', 'name', 'description', 'id_alert_action', 'type', 'id_group'));	
	
	if ($template !== false) {
		$data['type'] = 'array';
		$data['data'] = $template;
	}
	
	if (!$template) {
		returnError('error_get_alert_template',
			__('Error getting alert template.'));
	}
	else {
		returnData('csv', $data, ';');
	}
}

/**
 * Get module groups, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *
 *  api.php?op=get&op2=module_groups&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_module_groups($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config["id_user"], 0, "PM")) {
		returnError('forbidden', 'csv');
		return;
	}

	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	
	$module_groups = @db_get_all_rows_filter ('tmodule_group', $filter);
	
	if ($module_groups !== false) {
		$data['type'] = 'array';
		$data['data'] = $module_groups;
	}
	
	if (!$module_groups) {
		returnError('error_get_module_groups', __('Error getting module groups.'));
	}
	else { 
		returnData('csv', $data, $separator);
	}
}

/**
 * Get plugins, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *
 *  api.php?op=get&op2=plugins&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_plugins($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config["id_user"], 0, "PM")) {
		returnError('forbidden', 'csv');
		return;
	}

	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	$field_list = array( 'id', 'name', 'description',
				'max_timeout', 'max_retries',
				'execute', 'net_dst_opt',
				'net_port_opt', 'user_opt',
				'pass_opt', 'plugin_type',
				'macros', 'parameters');
	
	$plugins = @db_get_all_rows_filter ('tplugin', $filter, $field_list);
	
	if ($plugins !== false) {
		$data['type'] = 'array';
		$data['data'] = $plugins;
	}
	
	if (!$plugins) {
		returnError('error_get_plugins', __('Error getting plugins.'));
	}
	else { 
		returnData('csv', $data, $separator);
	}
}

/**
 * Create a network module from a network component. And return the id of new module.
 * 
 * @param string $agent_name The name of the agent where the module will be created
 * @param string $component_name The name of the network component
 * @param $thrash1 Don't use
 * @param $thrash2 Don't use
 */
function api_set_create_network_module_from_component($agent_name, $component_name, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agent_id = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($agent_id, 'string', 'AW')) {
		return;
	}

	if (!$agent_id) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Agent doesn\'t exist.'));
		return;
	}
	
	$component= db_get_row ('tnetwork_component', 'name', $component_name);
	
	if (!$component) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Network component doesn\'t exist.'));
		return;
	}
	
	// Adapt fields to module structure
	unset($component['id_nc']);
	unset($component['id_group']);
	$component['id_tipo_modulo'] = $component['type'];
	unset($component['type']);
	$component['descripcion'] = $component['description'];
	unset($component['description']);
	unset($component['name']);
	$component['ip_target'] = agents_get_address($agent_id);
	
	// Create module
	$module_id = modules_create_agent_module ($agent_id, $component_name, $component, true);
	
	if (!$module_id) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Error creating module.'));
		return;
	}
	
	return $module_id;
}

/**
 * Assign a module to an alert template. And return the id of new relationship.
 * 
 * @param string $id_template Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_module>;<id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  api.php?op=set&op2=create_module_template&id=1&other=1|10&other_mode=url_encode_separator_|  
 *    
 * @param $thrash3 Don't use
 */
function api_set_create_module_template($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_module_to_template',
			__('Error assigning module to template. Id_template cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_module_to_template',
			__('Error assigning module to template. Id_module cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_module_to_template',
			__('Error assigning module to template. Id_agent cannot be left blank.'));
		return;
	}
	
	$id_module = $other['data'][0];
	$id_agent = $other['data'][1];

	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) {
		return;
	}
	
	$result_template = alerts_get_alert_template($id);
	
	if (!$result_template) {
		returnError('error_module_to_template',
			__('Error assigning module to template. Id_template doensn\'t exists.'));
		return;
	}
	
	$result_agent = agents_get_name($id_agent);
	
	if (!$result_agent) {
		returnError('error_module_to_template', __('Error assigning module to template. Id_agent doesn\'t exist.'));
		return;
	}
	
	$result_module = db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);
	
	if (!$result_module) {
		returnError('error_module_to_template', __('Error assigning module to template. Id_module doesn\'t exist.'));
		return;
	}
	
	$id_template_module = alerts_create_alert_agent_module($id_module, $id);
	
	if (is_error($id_template_module)) {
		// TODO: Improve the error returning more info
		returnError('error_module_to_template', __('Error assigning module to template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id_template_module));
	}
}

/**
 * Delete an module assigned to a template. And return a message with the result of the operation.
 * 
 * @param string $id Id of the relationship between module and template (talert_template_modules) to delete.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=delete_module_template&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_delete_module_template($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AD")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id == "") {
		returnError('error_delete_module_template', __('Error deleting module template. Id_module_template cannot be left blank.'));
		return;
	}
	
	$result_module_template = alerts_get_alert_agent_module($id);
	
	if (!$result_module_template) {
		returnError('error_delete_module_template', __('Error deleting module template. Id_module_template doesn\'t exist.'));
		return;
	}
	
	$result = alerts_delete_alert_agent_module($id);
	
	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_delete_module_template', __('Error deleting module template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct deleting of module template.')));
	}
}

/**
 * Delete an module assigned to a template. And return a message with the result of the operation.
 *
 * @param $id		Agent Name
 * @param $id2		Alert Template Name
 * @param $other	[0] : Module Name
 * @param $trash1	Don't use
 *
 *  example:
 *
 * api.php?op=set&op2=delete_module_template_by_names&id=my_latest_agent&id2=test_template&other=memfree
 *
 */
function api_set_delete_module_template_by_names($id, $id2, $other, $trash1) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	$result = 0;

	if ($other['type'] != 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}

	if (! check_acl ($config['id_user'], 0, "AD") &&
		! check_acl ($config['id_user'], 0, "LM")
	) {
		returnError('forbidden', 'string');
		return;
	}

	$idAgent = agents_get_agent_id($id);

	if (!util_api_check_agent_and_print_error($idAgent, 'string', "AD")) {
		return;
	}

	$row = db_get_row_filter('talert_templates', array('name' => $id2));
	
	if ($row === false) {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	
	$idTemplate = $row['id'];
	$idActionTemplate = $row['id_alert_action'];
	
	$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data']));

	if ($idAgentModule === false) {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}

	$values = array(
		'id_agent_module' => $idAgentModule,
		'id_alert_template' => $idTemplate);

	$result = db_process_sql_delete ('talert_template_modules', $values);

	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_delete_module_template_by_name', __('Error deleting module template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct deleting of module template.')));
	}
}

/**
 * Validate all alerts. And return a message with the result of the operation.
 * 
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=validate_all_alerts
 *    
 * @param $thrash3 Don't use
 */
function api_set_validate_all_alerts($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")){
		returnError('forbidden', 'string');
		return;
	}

	$agents = array();
	$raw_agents = agents_get_agents(false, 'id_agente');
	if ($raw_agents !== false) {
		foreach ($raw_agents  as $agent) {
			$agents[] = $agent['id_agente'];
		}
	}

	$agents_string = implode(',', $agents);

	$sql = sprintf ("
		SELECT talert_template_modules.id
		FROM talert_template_modules
			INNER JOIN tagente_modulo t2
				ON talert_template_modules.id_agent_module = t2.id_agente_modulo
			INNER JOIN tagente t3
				ON t2.id_agente = t3.id_agente
			INNER JOIN talert_templates t4
				ON talert_template_modules.id_alert_template = t4.id
		WHERE t3.id_agente in (%s)", $agents_string);

	$alerts = db_get_all_rows_sql($sql);
	if ($alerts === false) $alerts = array();

	$total_alerts = count($alerts);
	$count_results = 0;
	foreach ($alerts as $alert) {
		$result = alerts_validate_alert_agent_module($alert['id'], false);
		
		if ($result) {
			$count_results++;
		}
	}
	
	if ($total_alerts > $count_results) {
		$errors = $total_alerts - $count_results;	
		returnError('error_validate_all_alerts', __('Error validate all alerts. Failed ' . $errors . '.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct validating of all alerts (total %d).', $count_results)));
	}
}

/**
 * Validate all policy alerts. And return a message with the result of the operation.
 * 
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=validate_all_policy_alerts
 *    
 * @param $thrash3 Don't use
 */
function api_set_validate_all_policy_alerts($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}

	# Get all policies
	$policies = enterprise_hook('policies_get_policies', array(false, false, false));
	
	if ($duplicated === ENTERPRISE_NOT_HOOK) {
		returnError('error_validate_all_policy_alerts', __('Error validating all alert policies.'));
		return;
	}
	
	// Count of valid results
	$total_alerts = 0;
	$count_results = 0;
	// Check all policies
	foreach ($policies as $policy) {
		$policy_alerts = array();
		$policy_alerts = enterprise_hook('policies_get_alerts',  array($policy['id'], false, false));

		// Number of alerts in this policy
		if ($policy_alerts != false) {
			$partial_alerts = count($policy_alerts);
			// Added alerts of this policy to the total
			$total_alerts = $total_alerts + $partial_alerts;
		}
		
		$result_pol_alerts = array();
		foreach ($policy_alerts as $policy_alert) {
			$result_pol_alerts[] = $policy_alert['id'];
		}
		
		$id_pol_alerts = implode(',', $result_pol_alerts);
		
		// If the policy has alerts
		if (count($result_pol_alerts) != 0) {
			$sql = sprintf ("
				SELECT id
				FROM talert_template_modules 
				WHERE id_policy_alerts IN (%s)", 
				$id_pol_alerts);
			
			$id_alerts = db_get_all_rows_sql($sql);
			
			$result_alerts = array();
			foreach ($id_alerts as $id_alert) {
				$result_alerts[] = $id_alert['id'];
			}
			
			// Validate alerts of these modules
			foreach ($result_alerts as $result_alert) {
				$result = alerts_validate_alert_agent_module($result_alert, true);
				
				if ($result) {
					$count_results++;
				}
			}
		}
		
	}
	
	// Check results
	if ($total_alerts > $count_results) {
		$errors = $total_alerts - $count_results;
		returnError('error_validate_all_alerts', __('Error validate all policy alerts. Failed ' . $errors . '.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct validating of all policy alerts.')));	
	}
}

/**
 * Stop a schedule downtime. And return a message with the result of the operation.
 * 
 * @param string $id Id of the downtime to stop.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=stop_downtime&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_stop_downtime($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "AD")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id == "") {
		returnError('error_stop_downtime', __('Error stopping downtime. Id_downtime cannot be left blank.'));
		return;
	}
	
	$date_time_stop = get_system_time();
	
	$values = array();
	$values['date_to'] = $date_time_stop;
	
	$result_update = db_process_sql_update('tplanned_downtime', $values, array ('id' => $id));
	if ($result_update == 0) {
		returnError('error_stop_downtime', __('No action has been taken.'));
	} elseif ($result_update < 0) {
		returnError('error_stop_downtime', __('Error stopping downtime.'));
	} else {
		returnData('string', array('type' => 'string', 'data' => __('Downtime stopped.')));
	}
}

function api_set_add_tag_module($id, $id2, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}

	$id_module = $id;
	$id_tag = $id2;

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string', "AW")) {
		return;
	}

	$exists = db_get_row_filter('ttag_module',
		array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));

	if (empty($exists)) {
		db_process_sql_insert('ttag_module',
			array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));
		
		$exists = db_get_row_filter('ttag_module',
			array('id_agente_modulo' => $id_module,
				'id_tag' => $id_tag));
	}
	
	if (empty($exists))
		returnError('error_set_tag_module', 'Error set tag module.');
	else
		returnData('string',
			array('type' => 'string', 'data' => 1));
}

function api_set_remove_tag_module($id, $id2, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_module = $id;
	$id_tag = $id2;

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string', "AW")) {
		return;
	}

	$row = db_get_row_filter('ttag_module',
		array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));
	
	$correct = 0;
	
	if (!empty($row)) {
		
		// Avoid to delete from policies
		
		if ($row['id_policy_module'] == 0) {
			$correct = db_process_sql_delete('ttag_module',
				array('id_agente_modulo' => $id_module,
					'id_tag' => $id_tag));
		}
	}
	
	returnData('string',
		array('type' => 'string', 'data' => $correct));
}

function api_set_tag($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}

	$values = array();
	$values['name'] = $id;
	$values['description'] = $other['data'][0];
	$values['url'] = $other['data'][1];
	$values['email'] = $other['data'][2];
	$values['phone'] = $other['data'][3];
	
	$id_tag = tags_create_tag($values);
	
	if (empty($id_tag))
		returnError('error_set_tag', __('Error set tag.'));
	else
		returnData('string',
			array('type' => 'string', 'data' => $id_tag));
}

/**
 * Return all planned downtime.
 *
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name>;<id_group>;<type_downtime>;<type_execution>;<type_periodicity>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=all_planned_downtimes&other=test|0|quiet|periodically|weekly&other_mode=url_encode_separator_|&return_type=json
 * 
 * @param type of return json or csv.
 */

function api_get_all_planned_downtimes ($thrash1, $thrash2, $other, $returnType = 'json') {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}

	$values = array();
	$values = array(
		"name LIKE '%".$other['data'][0]."%'"
	);
	
	if (isset($other['data'][1]) && ($other['data'][1] != false ))
		$values['id_group'] = $other['data'][1];
	if (isset($other['data'][2]) && ($other['data'][2] != false))
		$values['type_downtime'] = $other['data'][2];
	if (isset($other['data'][3]) && ($other['data'][3]!= false) )
		$values['type_execution'] = $other['data'][3];
	if (isset($other['data'][4]) && ($other['data'][4] != false) )
		$values['type_periodicity'] = $other['data'][4];
		
	
	$returned = all_planned_downtimes($values);

	if ($returned === false) {
		returnError("error_get_all_planned_downtimes", __('No planned downtime retrieved'));
		return;
	}

	returnData($returnType,
			array('type' => 'array', 'data' => $returned));
}

/**
 * Return all items of planned downtime.
 *
 * @param $id id of planned downtime.
 * @param  array $other it's array, $other as param is <name>;<id_group>;<type_downtime>;<type_execution>;<type_periodicity>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * 
 *  example:
 *  
 *   api.php?op=get&op2=planned_downtimes_items&other=test|0|quiet|periodically|weekly&other_mode=url_encode_separator_|&return_type=json
 * 
 * @param type of return json or csv.
 */

function api_get_planned_downtimes_items ($thrash1, $thrash2, $other, $returnType = 'json') {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	if(!check_acl($config['id_user'], 0, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}
 
	$values = array();
	$values = array(
		"name LIKE '%".$other['data'][0]."%'"
	);
	
	if (isset($other['data'][1]) && ($other['data'][1] != false ))
		$values['id_group'] = $other['data'][1];
	if (isset($other['data'][2]) && ($other['data'][2] != false))
		$values['type_downtime'] = $other['data'][2];
	if (isset($other['data'][3]) && ($other['data'][3]!= false) )
		$values['type_execution'] = $other['data'][3];
	if (isset($other['data'][4]) && ($other['data'][4] != false) )
		$values['type_periodicity'] = $other['data'][4];
		
	
	$returned = all_planned_downtimes($values);
	
	$is_quiet = false;
	$return = array('list_index'=>array('id_agents','id_downtime','all_modules'));
	
	foreach ($returned as $downtime) {
		if ($downtime['type_downtime'] === 'quiet')
			$is_quiet = true;
		
		$filter['id_downtime'] = $downtime['id'];
		
		$items = planned_downtimes_items ($filter);
		if ($items !== false) $return[] = $items;
	}
	
	// If the header is the unique element in the array, return an error
	if (count($return) == 1) {
		returnError('no_data_to_show', $returnType);
		return;
	}

	if ($is_quiet)
		$return['list_index'][] = 'modules';
	
	if ( $returnType == 'json' )
		unset($return['list_index']);
	
	returnData($returnType,
			array('type' => 'array', 'data' => $return));
}

/**
 * Delete planned downtime.
 *
 * @param $id id of planned downtime.
 * @param $thrash1 not use.
 * @param $thrash2 not use.
 *  
 *  api.php?op=set&op2=planned_downtimes_deleted &id=10
 * 
 * @param type of return json or csv.
 */

function api_set_planned_downtimes_deleted ($id, $thrash1, $thrash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "AD")) {
		returnError('forbidden', $returnType);
		return;
	}
	
	$values = array();
	$values = array(
		'id_downtime' => $id
	);
	
	$returned = delete_planned_downtimes($values);
	
	returnData($returnType,
			array('type' => 'string', 'data' => $returned));
}

/**
 * Create a new planned downtime.
 * 
 * @param $id name of planned downtime.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <description>;<date_from>;<date_to>;<id_group>;<monday>;
 *  <tuesday>;<wednesday>;<thursday>;<friday>;<saturday>;<sunday>;<periodically_time_from>;<periodically_time_to>;
 * 	<periodically_day_from>;<periodically_day_to>;<type_downtime>;<type_execution>;<type_periodicity>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=planned_downtimes_created&id=pepito&other=testing|08-22-2015|08-31-2015|0|1|1|1|1|1|1|1|17:06:00|19:06:00|1|31|quiet|periodically|weekly&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_planned_downtimes_created ($id, $thrash1, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AD")){
		returnError('forbidden', 'string');
		return;
	}

	$date_from = strtotime(html_entity_decode($other['data'][1]));
	$date_to = strtotime(html_entity_decode($other['data'][2]));

	$values = array();
	$values['name'] = $id;
	$values = array(
		'name' => $id,
		'description' => $other['data'][0],
		'date_from' => $date_from,
		'date_to' => $date_to,
		'id_group' => $other['data'][3],
		'monday' => $other['data'][4],
		'tuesday' => $other['data'][5],
		'wednesday' => $other['data'][6],
		'thursday' => $other['data'][7],
		'friday' => $other['data'][8],
		'saturday' => $other['data'][9],
		'sunday' => $other['data'][10],
		'periodically_time_from' => $other['data'][11],
		'periodically_time_to' => $other['data'][12],
		'periodically_day_from' => $other['data'][13],
		'periodically_day_to' => $other['data'][14],
		'type_downtime' => $other['data'][15],
		'type_execution' => $other['data'][16],
		'type_periodicity' => $other['data'][17],
		'id_user' => $other['data'][18]
	);
	
	$returned = planned_downtimes_created($values);
	
	if (!$returned['return'])
		returnError('error_set_planned_downtime', $returned['message'] );
	else
		returnData('string',
			array('type' => 'string', 'data' => $returned['return']));
}

/**
 * Add new items to planned Downtime.
 * 
 * @param $id id of planned downtime.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent1;id_agent2;id_agent3;....id_agentn;>;
 * 	<name_module1;name_module2;name_module3;......name_modulen;> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=planned_downtimes_additem&id=123&other=1;2;3;4|Status;Unkown_modules&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_planned_downtimes_additem ($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$total_agents = explode(';',$other['data'][0]);
	$agents = $total_agents;
	$bad_agents = array();
	$i = 0;
	foreach ($total_agents as $agent_id) {
		$result_agent = agents_check_access_agent($agent_id, "AD");
		if ( !$result_agent ) {
			$bad_agents[] = $agent_id;
			unset($agents[$i]);
		}
		$i++;
	}
	
	if ( isset($other['data'][1]) )
		$name_modules = explode(';',io_safe_output($other['data'][1]));
	else
		$name_modules = false;
	
	if ($name_modules)
		$all_modules = false;
	else
		$all_modules = true;
	
	if ( !empty($agents) )
		$returned = planned_downtimes_add_items($id, $agents, $all_modules, $name_modules);
	
	if ( empty($agents) )
		returnError('error_set_planned_downtime_additem', "No agents to create planned downtime items");
	else{
		
		if ( !empty($returned['bad_modules']) )
			$bad_modules = __("and this modules are doesn't exists or not applicable a this agents: ") . implode(", ",$returned['bad_modules']);
		if ( !empty($returned['bad_agents']) )
			$bad_agent = __("and this agents are generate problems: ") . implode(", ", $returned['bad_agents']) ;
		if ( !empty($bad_agents) )
			$agents_no_exists = __("and this agents with ids are doesn't exists: ") . implode(", ", $bad_agents) ;
		returnData('string',
			array('type' => 'string', 'data' => "Successfully created items " . $bad_agent . " " . $bad_modules . " " . $agents_no_exists));
	}
}

/**
 * Add data module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <module_name>;<id_module_type>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<configuration_data>;
 * <disabled_types_event>;<module_macros>;<ff_threshold>;<each_ff>;<ff_threshold_normal>;
 * <ff_threshold_warning>;<ff_threshold_critical>;<ff_timeout> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=add_data_module_policy&id=1&other=data_module_policy_example_name~2~data%20module%20created%20by%20Api~2~0~0~50.00~10~20~180~~21~35~~1~module_begin%0dmodule_name%20pandora_process%0dmodule_type%20generic_data%0dmodule_exec%20ps%20aux%20|%20grep%20pandora%20|%20wc%20-l%0dmodule_end&other_mode=url_encode_separator_~
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_data_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_data_module_policy', __('Error adding data module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if (enterprise_hook('policies_check_user_policy', array($id)) === false) {
		returnError('forbidden', 'string');
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_add_data_module_policy', __('Error adding data module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_data_module_policy', __('Error adding data module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][16];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['id_tipo_modulo'] = $other['data'][1];
	$values['description'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min'] = $other['data'][4];
	$values['max'] = $other['data'][5];
	$values['post_process'] = $other['data'][6];
	$values['module_interval'] = $other['data'][7];
	$values['min_warning'] = $other['data'][8];
	$values['max_warning'] = $other['data'][9];
	$values['str_warning'] = $other['data'][10];
	$values['min_critical'] = $other['data'][11];
	$values['max_critical'] = $other['data'][12];
	$values['str_critical'] = $other['data'][13];
	$values['history_data'] = $other['data'][14];
	$values['configuration_data'] = $other['data'][15];
	$values['disabled_types_event'] = $disabled_types_event;
	$values['module_macros'] = $other['data'][17];
	$values['min_ff_event'] = $other['data'][18];
	$values['each_ff'] = $other['data'][19];
	$values['min_ff_event_normal'] = $other['data'][20];
	$values['min_ff_event_warning'] = $other['data'][21];
	$values['min_ff_event_critical'] = $other['data'][22];
	$values['ff_timeout'] = $other['data'][23];
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_data_module_policy',
				__('Error adding data module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module',
		array($other['data'][0], $id, 1, $values, false));
	
	if ($success)
		//returnData('string', array('type' => 'string', 'data' => __('Data module added to policy. Is necessary to apply the policy in order to changes take effect.')));		
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_data_module_policy', 'Error adding data module to policy.');	
	
}

/**
 * Update data module in policy. And return id from new module.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<configuration_data>;
 * <disabled_types_event>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_data_module_policy&id=1&other=10~data%20module%20updated%20by%20Api~2~0~0~50.00~10~20~180~~21~35~~1~module_begin%0dmodule_name%20pandora_process%0dmodule_type%20generic_data%0dmodule_exec%20ps%20aux%20|%20grep%20pandora%20|%20wc%20-l%0dmodule_end&other_mode=url_encode_separator_~
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_data_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Id_policy cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error($id, 'string', "AW")) {
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Module doesn\'t exist.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 1) {
		returnError('error_update_data_module_policy',
			__('Error updating data module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_data_module = array(
		'id','description', 'id_module_group', 'min', 'max',
		'post_process', 'module_interval', 'min_warning', 'max_warning',
		'str_warning', 'min_critical', 'max_critical', 'str_critical',
		'history_data', 'configuration_data', 'disabled_types_event',
		'module_macros');
	
	$cont = 0;
	foreach ($fields_data_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	
	$result_update = enterprise_hook('policies_update_module',
		array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_data_module_policy', 'Error updating policy module.');
	else
		returnData('string',
			array('type' => 'string', 'data' => __('Data policy module updated.')));
}

/**
 * Add network module to policy. And return a result message.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <module_name>;<id_module_type>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 * <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros>;
 * <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=add_network_module_policy&id=1&other=network_module_policy_example_name|6|network%20module%20created%20by%20Api|2|0|0|50.00|180|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_network_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if (enterprise_hook('policies_check_user_policy', array($id)) === false) {
		returnError('forbidden', 'string');
		return;
	}

	if ($other['data'][0] == "") {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] < 6 or $other['data'][1] > 18) {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Id_module_type is not correct for network modules.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules',
		array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][21];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['id_tipo_modulo'] = $other['data'][1];
	$values['description'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min'] = $other['data'][4];
	$values['max'] = $other['data'][5];
	$values['post_process'] = $other['data'][6];
	$values['module_interval'] = $other['data'][7];
	$values['min_warning'] = $other['data'][8];
	$values['max_warning'] = $other['data'][9];
	$values['str_warning'] = $other['data'][10];
	$values['min_critical'] = $other['data'][11];
	$values['max_critical'] = $other['data'][12];
	$values['str_critical'] = $other['data'][13];
	$values['history_data'] = $other['data'][14];
	$values['min_ff_event'] = $other['data'][15];
	$values['disabled'] = $other['data'][16];
	$values['tcp_port'] = $other['data'][17];
	$values['snmp_community'] = $other['data'][18];
	$values['snmp_oid'] = $other['data'][19];
	$values['custom_id'] = $other['data'][20];
	$values['disabled_types_event'] = $disabled_types_event;
	$values['module_macros'] = $other['data'][22];
	$values['each_ff'] = $other['data'][23];
	$values['min_ff_event_normal'] = $other['data'][24];
	$values['min_ff_event_warning'] = $other['data'][25];
	$values['min_ff_event_critical'] = $other['data'][26];
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_network_data_module_policy', __('Error adding network module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 2, $values, false)); 
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_network_module_policy', 'Error adding network module to policy.');	
}

/**
 * Update network module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 * <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_network_module_policy&id=1&other=14|network%20module%20updated%20by%20Api|2|0|0|150.00|300|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_network_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Module doesn\'t exist.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 2) {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_network_module = array('id','description',
		'id_module_group', 'min', 'max', 'post_process',
		'module_interval', 'min_warning', 'max_warning', 'str_warning',
		'min_critical', 'max_critical', 'str_critical', 'history_data',
		'min_ff_event', 'disabled', 'tcp_port', 'snmp_community',
		'snmp_oid', 'custom_id', 'disabled_types_event', 'module_macros');
	
	$cont = 0;
	foreach ($fields_network_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module', array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_network_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Network policy module updated.')));		
}

/**
 * Add plugin module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  api.php?op=set&op2=add_plugin_module_policy&id=1&other=example%20plugin%20module%20name|0|1|2|0|0||0|0||15|0|66|||300|50.00|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_plugin_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if (enterprise_hook('policies_check_user_policy', array($id)) === false) {
		returnError('forbidden', 'string');
		return;
	}

	if ($other['data'][0] == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][22] == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Id_plugin cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][25];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['disabled'] = $other['data'][1];
	$values['id_tipo_modulo'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min_warning'] = $other['data'][4];
	$values['max_warning'] = $other['data'][5];
	$values['str_warning'] = $other['data'][6];
	$values['min_critical'] = $other['data'][7];
	$values['max_critical'] = $other['data'][8];
	$values['str_critical'] = $other['data'][9];
	$values['min_ff_event'] = $other['data'][10];
	$values['history_data'] = $other['data'][11];
	$values['tcp_port'] = $other['data'][12];
	$values['snmp_community'] = $other['data'][13];
	$values['snmp_oid'] = $other['data'][14];
	$values['module_interval'] = $other['data'][15];
	$values['post_process'] = $other['data'][16];
	$values['min'] = $other['data'][17];
	$values['max'] = $other['data'][18];
	$values['custom_id'] = $other['data'][19];
	$values['description'] = $other['data'][20];
	$values['id_plugin'] = $other['data'][21]; 
	$values['plugin_user'] = $other['data'][22];
	$values['plugin_pass'] = $other['data'][23];
	$values['plugin_parameter'] = $other['data'][24];
	$values['disabled_types_event'] = $disabled_types_event;
	$values['macros'] = base64_decode ($other['data'][26]);
	$values['module_macros'] = $other['data'][27];
	$values['each_ff'] = $other['data'][28];
	$values['min_ff_event_normal'] = $other['data'][29];
	$values['min_ff_event_warning'] = $other['data'][30];
	$values['min_ff_event_critical'] = $other['data'][31];
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 4, $values, false));
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_plugin_module_policy', 'Error adding plugin module to policy.');
}

/**
 * Update plugin module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_plugin_module_policy&id=1&other=23|0|1|0|0||0|0||15|0|166|||180|150.00|0|0|0|plugin%20module%20updated%20from%20api|2|example_user|pass|-p%20min&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_plugin_module_policy',
			__('Error updating plugin module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_plugin_module_policy',
			__('Error updating plugin module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_updating_plugin_module_policy',
			__('Error updating plugin module in policy. Module doesn\'t exist.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 4) {
		returnError('error_updating_plugin_module_policy',
			__('Error updating plugin module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_plugin_module = array('id','disabled', 'id_module_group',
		'min_warning', 'max_warning', 'str_warning', 'min_critical', 
		'max_critical', 'str_critical', 'min_ff_event', 'history_data',
		'tcp_port', 'snmp_community', 'snmp_oid', 'module_interval',
		'post_process', 'min', 'max', 'custom_id', 'description',
		'id_plugin', 'plugin_user', 'plugin_pass', 'plugin_parameter',
		'disabled_types_event', 'macros', 'module_macros');
	
	$cont = 0;
	foreach ($fields_plugin_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
			
			if( $field === 'macros' ) {
				$values[$field] = base64_decode($values[$field]);
			}
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module',
		array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_plugin_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Plugin policy module updated.')));		
}

/**
 * Add module data configuration into agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param array $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=add_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 * 
 * @return string 0 when success, -1 when error, -2 if already exist
 */
function api_set_add_module_in_conf($id_agent, $module_name, $configuration_data, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) return;

	$new_configuration_data = io_safe_output(urldecode($configuration_data['data']));
	
	// Check if exist a current module with the same name in the conf file
	$old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));
	
	// If exists a module with same name, abort
	if(!empty($old_configuration_data)) {
		returnError('error_adding_module_conf', '-2');
		exit;
	}
	
	$result = enterprise_hook('config_agents_add_module_in_conf', array($id_agent, $new_configuration_data));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_adding_module_conf', '-1');
	}
}


/**
 * Get module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=get&op2=module_from_conf&user=admin&pass=pandora&id=9043&id2=example_name
 * 
 * @return string Module data when success, empty when error
 */
function api_get_module_from_conf($id_agent, $module_name, $thrash2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string')) return;

	$module_name = io_safe_output($module_name);
	$result = enterprise_hook('config_agents_get_module_from_conf',
		array($id_agent, $module_name));

	if ($result !== ENTERPRISE_NOT_HOOK && !empty($result)) {
		returnData('string', array('type' => 'string', 'data' => $result));
	}
	else {
		returnError('error_adding_module_conf', __('Remote config of module %s not available', $module_name));
	}
}

/**
 * Delete module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=delete_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name
 * 
 * @return string 0 when success, -1 when error
 */
function api_set_delete_module_in_conf($id_agent, $module_name, $thrash2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string')) return;
	$result = config_agents_delete_module_in_conf($id_agent, $module_name);
	
	$result = enterprise_hook('config_agents_delete_module_in_conf', array($id_agent, $module_name));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_deleting_module_conf', '-1');
	}
}

/**
 * Update module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param array $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=update_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 * 
 * @return string 0 when success, 1 when no changes, -1 when error, -2 if doesnt exist
 */
function api_set_update_module_in_conf($id_agent, $module_name, $configuration_data_serialized, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string')) return;
	$new_configuration_data = io_safe_output(urldecode($configuration_data_serialized['data']));
	
	// Get current configuration
	$old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));
	
	// If not exists
	if(empty($old_configuration_data)) {
		returnError('error_editing_module_conf', '-2');
		exit;
	}
	
	// If current configuration and new configuration are equal, abort
	if ($new_configuration_data == $old_configuration_data) {
		returnData('string', array('type' => 'string', 'data' => '1'));		
		exit;
	}
	
	$result = enterprise_hook('config_agents_update_module_in_conf', array($id_agent, $old_configuration_data, $new_configuration_data));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_editing_module_conf', '-1');
	}
}

/**
 * Add SNMP module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:  
 * 
 *  api.php?op=set&op2=add_snmp_module_policy&id=1&other=example%20SNMP%20module%20name|0|15|2|0|0||0|0||15|1|66|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_snmp_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if (enterprise_hook('policies_check_user_policy', array($id)) === false) {
		returnError('forbidden', 'string');
		return;
	}

	if ($other['data'][0] == "") {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy.'));
		return;
	}
	
	if ($other['data'][2] < 15 or $other['data'][2] > 18) {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Id_module_type is not correct for SNMP modules.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][28];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][13] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_priv_method doesn\'t exist. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][24] != "authNoPriv" and $other['data'][24] != "authPriv" and $other['data'][24] != "noAuthNoPriv") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_sec_level doesn\'t exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][25] != "MD5" and $other['data'][25] != "SHA") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_auth_method doesn\'t exist. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$values = array(
			'disabled' => $other['data'][1],
			'id_tipo_modulo' => $other['data'][2],
			'id_module_group' => $other['data'][3],
			'min_warning' => $other['data'][4],
			'max_warning' => $other['data'][5],
			'str_warning' => $other['data'][6],
			'min_critical' => $other['data'][7],
			'max_critical' => $other['data'][8],
			'str_critical' => $other['data'][9],
			'min_ff_event' => $other['data'][10],
			'history_data' => $other['data'][11],
			'tcp_port' => $other['data'][12],
			'tcp_send' => $other['data'][13],
			'snmp_community' => $other['data'][14],
			'snmp_oid' => $other['data'][15],
			'module_interval' => $other['data'][16],
			'post_process' => $other['data'][17],
			'min' => $other['data'][18],
			'max' => $other['data'][19],
			'custom_id' => $other['data'][20],
			'description' => $other['data'][21],
			'custom_string_1' => $other['data'][22],
			'custom_string_2' => $other['data'][23],
			'custom_string_3' => $other['data'][24],
			'plugin_parameter' => $other['data'][25],
			'plugin_user' => $other['data'][26],
			'plugin_pass' => $other['data'][27],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][29],
			'min_ff_event_normal' => $other['data'][30],
			'min_ff_event_warning' => $other['data'][31],
			'min_ff_event_critical' => $other['data'][32]
		);
	}
	else {
		$values = array(
			'disabled' => $other['data'][1],
			'id_tipo_modulo' => $other['data'][2],
			'id_module_group' => $other['data'][3],
			'min_warning' => $other['data'][4],
			'max_warning' => $other['data'][5],
			'str_warning' => $other['data'][6],
			'min_critical' => $other['data'][7],
			'max_critical' => $other['data'][8],
			'str_critical' => $other['data'][9],
			'min_ff_event' => $other['data'][10],
			'history_data' => $other['data'][11],
			'tcp_port' => $other['data'][12],
			'tcp_send' => $other['data'][13],
			'snmp_community' => $other['data'][14],
			'snmp_oid' => $other['data'][15],
			'module_interval' => $other['data'][16],
			'post_process' => $other['data'][17],
			'min' => $other['data'][18],
			'max' => $other['data'][19],
			'custom_id' => $other['data'][20],
			'description' => $other['data'][21],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][23],
			'min_ff_event_normal' => $other['data'][24],
			'min_ff_event_warning' => $other['data'][25],
			'min_ff_event_critical' => $other['data'][26]
		);
	}
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 2, $values, false));
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_snmp_module_policy', 'Error adding SNMP module to policy.');

}

/**
 * Update SNMP module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_snmp_module_policy&id=1&other=14|0|2|0|0||0|0||30|1|66|3|nonpublic|.1.3.6.1.2.1.1.1.0|300|150.00|10|60|0|SNMP%20module%20updated%20by%20API|DES|example_priv_passw|authPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Module doesn\'t exist.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 2) {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Module type is not SNMP type.'));
		return;
	}
	
	
	# SNMP version 3
	if ($other['data'][12] == "3") {
		
		if ($other['data'][21] != "AES" and $other['data'][21] != "DES") {
			returnError('error_update_snmp_module_policy',
				__('Error updating SNMP module. snmp3_priv_method doesn\'t exist. Set it to \'AES\' or \'DES\'. '));

			return;
		}
		
		if ($other['data'][23] != "authNoPriv"
			and $other['data'][23] != "authPriv"
			and $other['data'][23] != "noAuthNoPriv") {
				
			returnError('error_update_snmp_module_policy',
				__('Error updating SNMP module. snmp3_sec_level doesn\'t exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));

			return;
		}
		
		if ($other['data'][24] != "MD5" and $other['data'][24] != "SHA") {
			returnError('error_update_snmp_module_policy',
				__('Error updating SNMP module. snmp3_auth_method doesn\'t exist. Set it to \'MD5\' or \'SHA\'. '));

			return;
		}
		
		$fields_snmp_module = array('id','disabled', 'id_module_group',
			'min_warning', 'max_warning', 'str_warning', 'min_critical', 
			'max_critical', 'str_critical', 'min_ff_event',
			'history_data', 'tcp_port', 'tcp_send', 'snmp_community',
			'snmp_oid', 'module_interval', 'post_process', 'min', 'max',
			'custom_id', 'description', 'custom_string_1',
			'custom_string_2', 'custom_string_3', 'plugin_parameter',
			'plugin_user', 'plugin_pass');
	}
	else {
		$fields_snmp_module = array('id','disabled', 'id_module_group',
			'min_warning', 'max_warning', 'str_warning', 'min_critical', 
			'max_critical', 'str_critical', 'min_ff_event',
			'history_data', 'tcp_port', 'tcp_send', 'snmp_community',
			'snmp_oid', 'module_interval', 'post_process', 'min', 'max',
			'custom_id', 'description');
	}
	
	$cont = 0;
	foreach ($fields_snmp_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module',
		array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_snmp_module_policy', 'Error updating policy module.');
	else
		returnData('string',
			array('type' => 'string', 'data' => __('SNMP policy module updated.')));
}

/**
 * Create a new group. And return the id_group of the new group. 
 * 
 * @param string $id Name of the new group.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <icon_name>;<id_group_parent>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (with parent group: Servers) 
 * 
 *  api.php?op=set&op2=create_group&id=example_group_name&other=applications|1&other_mode=url_encode_separator_|
 *  
 * 	example 2 (without parent group)
 * 
 * 	api.php?op=set&op2=create_group&id=example_group_name2&other=computer|&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_group($id, $thrash1, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) return;

	$group_name = $id;

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	if ($id == "") {
		returnError('error_create_group',
			__('Error in group creation. Group_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_create_group',
			__('Error in group creation. Icon_name cannot be left blank.'));
		return;
	}
	
	
	
	$safe_other_data = io_safe_input($other['data']);
	
	if ($safe_other_data[1] != "") {
			$group = groups_get_group_by_id($safe_other_data[1]);
		
		if ($group == false) {
			returnError('error_create_group',
				__('Error in group creation. Id_parent_group doesn\'t exist.'));
			return;
		}
	}
	
	if ($safe_other_data[1] != "") {
		$values = array(
			'icon' => $safe_other_data[0],
			'parent' => $safe_other_data[1],
			'description' => $safe_other_data[2]
		);
	}
	else {
		$values = array(
			'icon' => $safe_other_data[0],
			'description' => $safe_other_data[2]
		);
	}
	$values['propagate'] = $safe_other_data[3];
	$values['disabled'] = $safe_other_data[4];
	$values['custom_id'] =$safe_other_data[5]; 
	$values['contact'] = $safe_other_data[6];
	$values['other'] = $safe_other_data[7];
	
	$id_group = groups_create_group($group_name, $values);
	
	if (is_error($id_group)) {
		// TODO: Improve the error returning more info
		returnError('error_create_group', __('Error in group creation.'));
	}
	else {
		
		if (defined("METACONSOLE")) {
			$servers = db_get_all_rows_sql ("SELECT *
				FROM tmetaconsole_setup
				WHERE disabled = 0");
			
			if ($servers === false)
				$servers = array();
			
			$result = array();
			foreach($servers as $server) {
				// If connection was good then retrieve all data server
				if (metaconsole_connect($server) == NOERR) {
					$values['id_grupo'] = $id_group;
					$id_group_node = groups_create_group($group_name, $values);
				}
				metaconsole_restore_db();
			}
		}
		
		returnData('string', array('type' => 'string', 'data' => $id_group));
	}
}

/**
 * Update a group.
 *
 * @param integer $id Group ID
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <group_name>;<icon_name>;<parent_group_id>;<propagete>;<disabled>;<custom_id>;<description>;<contact>;<other> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *
 * 	api.php?op=set&op2=update_group&id=example_group_id&other=New%20Name|application|2|new%20description|1|0|custom%20id||&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_group($id_group, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	if (db_get_value('id_grupo', 'tgrupo', 'id_grupo', $id_group) === false) {
		returnError('error_set_update_group', __('There is not any group with the id provided'));
		return;
	}

	if (!check_acl($config['id_user'], $id_group, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$name = $other['data'][0];
	$icon = $other['data'][1];
	$parent = $other['data'][2];
	$description = $other['data'][3];
	$propagate = $other['data'][4];
	$disabled = $other['data'][5];
	$custom_id = $other['data'][6];
	$contact = $other['data'][7];
	$other = $other['data'][8];

	$return = db_process_sql_update('tgrupo',
		array('nombre' => $name,
			'icon' => $icon,
			'parent' => $parent,
			'description' => $description,
			'propagate' => $propagate,
			'disabled' => $disabled,
			'custom_id' => $custom_id,
			'contact' => $contact,
			'other' => $other),
		array('id_grupo' => $id_group));

	returnData('string',
		array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Delete a group 
 * 
 * @param integer $id Group ID
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_group($id_group, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$group = db_get_row_filter('tgrupo', array('id_grupo' => $id_group));
	if (!$group) {
		returnError('error_delete', 'Error in delete operation. Id does not exist.');
		return;
	}

	if (!check_acl($config['id_user'], $id_group, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$usedGroup = groups_check_used($id_group);
	if ($usedGroup['return']) {
		returnError('error_delete',
			 'Error in delete operation. The group is not empty (used in ' .
			 implode(', ', $usedGroup['tables']) . ').' );
		return;
	}

	db_process_sql_update('tgrupo', array('parent' => $group['parent']), array('parent' => $id_group));
	db_process_sql_delete('tgroup_stat', array('id_group' => $id_group));

	$result = db_process_sql_delete('tgrupo', array('id_grupo' => $id_group));

	if (!$result)
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Correct Delete')));
}

/**
 * Create a new netflow filter. And return the id_group of the new group. 
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <filter_name>;<group_id>;<filter>;<aggregate_by>;<output_format> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * 
 * Possible values of 'aggregate_by' field: dstip,dstport,none,proto,srcip,srcport
 * Possible values of 'output_format' field: kilobytes,kilobytespersecond,megabytes,megabytespersecond
 * 
 *  example:
 * 
 * 	api.php?op=set&op2=create_netflow_filter&id=Filter name&other=9|host 192.168.50.3 OR host 192.168.50.4 or HOST 192.168.50.6|dstport|kilobytes&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_netflow_filter($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($other['data'][0] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Filter name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Group id cannot be left blank.'));
		return;
	}
	else {
		$group = groups_get_group_by_id($other['data'][1]);
		
		if ($group == false) {
			returnError('error_create_group', __('Error in netflow filter creation. Id_group doesn\'t exist.'));
			return;
		}

		if (!check_acl($config['id_user'], $other['data'][1], "AW")) {
			returnError('forbidden', 'string');
			return;
		}
	}
	
	if ($other['data'][2] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Filter cannot be left blank.'));
		return;
	}
	
	if ($other['data'][3] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Aggregate_by cannot be left blank.'));
		return;
	}
	
	if ($other['data'][4] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Output_format cannot be left blank.'));
		return;
	}
	
	$values = array (
		'id_name'=> $other['data'][0],
		'id_group' => $other['data'][1],
		'advanced_filter'=> $other['data'][2],
		'aggregate'=> $other['data'][3],
		'output'=> $other['data'][4]
	);
	
	// Save filter args
	$values['filter_args'] = netflow_get_filter_arguments ($values);
	
	$id = db_process_sql_insert('tnetflow_filter', $values);
	
	if ($id === false) {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id));
	}
}

/**
 * Get module data in CSV format.
 * 
 * @param integer $id The ID of module in DB. 
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <separator>;<period>;<tstart>;<tend> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=module_data&id=17&other=;|604800|20161201T13:40|20161215T13:40&other_mode=url_encode_separator_|
 *  
 * @param $returnType Don't use.
 */
function api_get_module_data($id, $thrash1, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), $returnType)) return;

	$separator = $other['data'][0];
	$periodSeconds = $other['data'][1];
	$tstart = $other['data'][2];
	$tend = $other['data'][3];

	if (($tstart != "") && ($tend != "")) {
		try {
			$dateStart = explode("T", $tstart);
			$dateYearStart = substr($dateStart[0], 0, 4);
			$dateMonthStart = substr($dateStart[0], 4, 2);
			$dateDayStart = substr($dateStart[0], 6, 2);
			$date_start = $dateYearStart . "-" . $dateMonthStart . "-" . $dateDayStart . " " . $dateStart[1];
			$date_start = new DateTime($date_start);
			$date_start = $date_start->format('U');

			$dateEnd = explode("T", $tend);
			$dateYearEnd = substr($dateEnd[0], 0, 4);
			$dateMonthEnd = substr($dateEnd[0], 4, 2);
			$dateDayEnd = substr($dateEnd[0], 6, 2);
			$date_end = $dateYearEnd . "-" . $dateMonthEnd . "-" . $dateDayEnd . " " . $dateEnd[1];
			$date_end = new DateTime($date_end);
			$date_end = $date_end->format('U');
		}
		catch (Exception $e) {
			returnError('error_query_module_data', 'Error in date format. ');
		}

		$sql = sprintf ("SELECT utimestamp, datos 
			FROM tagente_datos 
			WHERE id_agente_modulo = %d AND utimestamp > %d 
			AND utimestamp < %d 
			ORDER BY utimestamp DESC", $id, $date_start, $date_end);
	}
	else {
		if ($periodSeconds == null) {
			$sql = sprintf ("SELECT utimestamp, datos 
				FROM tagente_datos 
				WHERE id_agente_modulo = %d 
				ORDER BY utimestamp DESC", $id);
		}
		else {
			$sql = sprintf ("SELECT utimestamp, datos 
				FROM tagente_datos 
				WHERE id_agente_modulo = %d AND utimestamp > %d 
				ORDER BY utimestamp DESC", $id, get_system_time () - $periodSeconds);
		}
	}

	$data['type'] = 'array';
	$data['list_index'] = array('utimestamp', 'datos');
	$data['data'] = db_get_all_rows_sql($sql);
	
	if ($data === false) {
		returnError('error_query_module_data', 'Error in the query of module data.');
	}
	else if ($data['data'] == "") {
		returnError('error_query_module_data', 'No data to show.');
	}
	else {
		returnData('csv', $data, $separator);
	}
}

/**
 * Return a image file of sparse graph of module data in a period time.
 * 
 * @param integer $id id of a module data.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <period>;<width>;<height>;<label>;<start_date>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=graph_module_data&id=17&other=604800|555|245|pepito|2009-12-07&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.
 */
function api_get_graph_module_data($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), "string")) return;

	$period = $other['data'][0];
	$width = $other['data'][1];
	$height = $other['data'][2];
	$graph_type = 'sparse';
	$draw_alerts = 0;
	$draw_events = 0;
	$zoom = 1;
	$label = $other['data'][3];
	$start_date = $other['data'][4];
	$date = strtotime($start_date);

	$homeurl = '../';
	$ttl = 1;

	global $config;

	$params =array(
		'agent_module_id'     => $id,
		'period'              => $period,
		'show_events'         => $draw_events,
		'width'               => $width,
		'height'              => $height,
		'show_alerts'         => $draw_alerts,
		'date'                => $date,
		'unit'                => '',
		'baseline'            => 0,
		'return_data'         => 0,
		'show_title'          => true,
		'only_image'          => true,
		'homeurl'             => $homeurl,
		'compare'             => false,
		'show_unknown'        => true,
		'backgroundColor'     => 'white',
		'percentil'           => null,
		'type_graph'          => $config['type_module_charts'],
		'fullscale'           => false,
		'return_img_base_64'  => true
	);

	$image = grafico_modulo_sparse($params);

	header('Content-type: text/html');
	returnData('string', array('type' => 'string', 'data' => '<img src="data:image/jpeg;base64,' . $image . '">'));
}

/**
 * Create new user.
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;
 *  <email>;<phone>;<languages>;<comments> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_user&id=md&other=miguel|de%20dios|matias|kkk|pandora|md@md.com|666|es|descripcion%20y%20esas%20cosas&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function api_set_new_user($id, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "UM")) {
		returnError('forbidden', 'string');
		return;
	}

	$values = array ();
	$values['fullname'] = $other['data'][0];
	$values['firstname'] = $other['data'][1];
	$values['lastname'] = $other['data'][2];
	$values['middlename'] = $other['data'][3];
	$password = $other['data'][4];
	$values['email'] = $other['data'][5];
	$values['phone'] = $other['data'][6];
	$values['language'] = $other['data'][7];
	$values['comments'] = $other['data'][8];
	
	if (!create_user ($id, $password, $values))
		returnError('error_create_user', 'Error create user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Create user.')));
}

/**
 * Update new user.
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;<password>;
 *  <email>;<phone>;<language>;<comments>;<is_admin>;<block_size>;in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_user&id=example_user_name&other=example_fullname||example_lastname||example_new_passwd|example_email||example_language|example%20comment|1|30|&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function api_set_update_user($id, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "UM")) {
		returnError('forbidden', 'string');
		return;
	}

	$fields_user = array('fullname',
		'firstname',
		'lastname',
		'middlename',
		'password',
		'email',
		'phone',
		'language',
		'comments',
		'is_admin',
		'block_size'
	);

	if ($id == "") {
		returnError('error_update_user',
			__('Error updating user. Id_user cannot be left blank.'));
		return;
	}

	$result_user = users_get_user_by_id($id);
	
	if (!$result_user) {
		returnError('error_update_user',
			__('Error updating user. Id_user doesn\'t exist.'));
		return;
	}
	
	$cont = 0;
	foreach ($fields_user as $field) {
		if ($other['data'][$cont] != "" and $field != "password") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	// If password field has data
	if ($other['data'][4] != "") {
		if (!update_user_password($id, $other['data'][4])) {
			returnError('error_update_user', __('Error updating user. Password info incorrect.'));
			return;
		}
	}
	
	if (!update_user ($id, $values))
		returnError('error_create_user', 'Error updating user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Updated user.')));
}

/**
 * Enable/disable user given an id
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <enable/disable value> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (Disable user 'example_name') 
 * 
 *  api.php?op=set&op2=enable_disable_user&id=example_name&other=0&other_mode=url_encode_separator_|
 *  
 * 	example 2 (Enable user 'example_name')
 * 
 *  api.php?op=set&op2=enable_disable_user&id=example_name&other=1&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_enable_disable_user ($id, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, "UM")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id == "") {
		returnError('error_enable_disable_user',
			__('Error enable/disable user. Id_user cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] != "0" and $other['data'][0] != "1") {
		returnError('error_enable_disable_user',
			__('Error enable/disable user. Enable/disable value cannot be left blank.'));
		return;
	}
	
	if (users_get_user_by_id($id) == false) {
		returnError('error_enable_disable_user',
			__('Error enable/disable user. The user doesn\'t exist.'));
		return;
	}
	
	$result = users_disable($id, $other['data'][0]);
	
	if (is_error($result)) {
		// TODO: Improve the error returning more info
		returnError('error_enable_disable_user',
			__('Error in user enabling/disabling.'));
	}
	else {
		if ($other['data'][0] == "0") {
			returnData('string',
				array('type' => 'string', 'data' => __('Enabled user.')));
		}
		else {
			returnData('string',
			array('type' => 'string', 'data' => __('Disabled user.')));
		}
	}
}


function otherParameter2Filter($other, $return_as_array = false) {
	$filter = array();
	
	if (isset($other['data'][1]) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
		$filter['criticity'] = $other['data'][1];
	}
	
	$idAgent = null;
	if (isset($other['data'][2]) && $other['data'][2] != '') {
		$idAgents = agents_get_agent_id_by_alias($other['data'][2]);
		
		if (!empty($idAgent)) {

			$filter[] = "id_agente IN (" . explode(",", $idAgents) .")";
		}
		else {
			$filter['sql'] = "1=0";
		}
	}
	
	$idAgentModulo = null;
	if (isset($other['data'][3]) && $other['data'][3] != '') {
		$filterModule = array('nombre' => $other['data'][3]);
		if ($idAgent != null) {
			$filterModule['id_agente'] = $idAgent;
		}
		$idAgentModulo = db_get_value_filter('id_agente_modulo', 'tagente_modulo', $filterModule);
		if ($idAgentModulo !== false) {
			$filter['id_agentmodule'] = $idAgentModulo;
		}
	}
	
	if (isset($other['data'][4]) && $other['data'][4] != '') {
		$idTemplate = db_get_value_filter('id', 'talert_templates', array('name' => $other['data'][4]));
		if ($idTemplate !== false) {
			if ($idAgentModulo != null) {
				$idAlert = db_get_value_filter('id', 'talert_template_modules', array('id_agent_module' => $idAgentModulo,  'id_alert_template' => $idTemplate));
				if ($idAlert !== false) {
					$filter['id_alert_am'] = $idAlert;
				}
			}
		}
	}
	
	if (isset($other['data'][5]) && $other['data'][5] != '') {
		$filter['id_usuario'] = $other['data'][5];
	}
	
	$filterString = db_format_array_where_clause_sql ($filter);
	if ($filterString == '') {
		$filterString = '1 = 1';
	}
	
	if (isset($other['data'][6]) && ($other['data'][6] != '') && ($other['data'][6] != -1)) {
		if ($return_as_array) {
			$filter['utimestamp']['>'] = $other['data'][6];
		}
		else {
			$filterString .= ' AND utimestamp >= ' . $other['data'][6];
		}
	}
	
	if (isset($other['data'][7]) && ($other['data'][7] != '') && ($other['data'][7] != -1)) {
		if ($return_as_array) {
			$filter['utimestamp']['<'] = $other['data'][7];
		}
		else {
			$filterString .= ' AND utimestamp <= ' . $other['data'][7];
		}
	}
	
	if (isset($other['data'][8]) && ($other['data'][8] != '')) {
		if ($return_as_array) {
			$filter['estado'] = $other['data'][8];
		}
		else {
			$estado = (int)$other['data'][8];
			
			if ($estado >= 0) {
				$filterString .= ' AND estado = ' . $estado;
			}
		}
	}
	
	if (isset($other['data'][9]) && ($other['data'][9] != '')) {
		if ($return_as_array) {
			$filter['evento'] = $other['data'][9];
		}
		else {
			$filterString .= ' AND evento like "%' . $other['data'][9] . '%"';
		}
	}
	
	if (isset($other['data'][10]) && ($other['data'][10] != '')) {
		if ($return_as_array) {
			$filter['limit'] = $other['data'][10];
		}
		else {
			$filterString .= ' LIMIT ' . $other['data'][10];
		}
	}
	
	if (isset($other['data'][11]) && ($other['data'][11] != '')) {
		if ($return_as_array) {
			$filter['offset'] = $other['data'][11];
		}
		else {
			$filterString .= ' OFFSET ' . $other['data'][11];
		}
	}
	
	if (isset($other['data'][12]) && ($other['data'][12] != '')) {
		if ($return_as_array) {
			$filter['total'] = false;
			$filter['more_criticity'] = false;
			
			if ($other['data'][12] == 'total') {
				$filter['total'] = true;
			}
			
			if ($other['data'][12] == 'more_criticity') {
				$filter['more_criticity'] = true;
			}
		}
		else {
			
		}
	}
	else {
		if ($return_as_array) {
			$filter['total'] = false;
			$filter['more_criticity'] = false;
		}
		else {
			
		}
	}
	
	if (isset($other['data'][13]) && ($other['data'][13] != '')) { 
		if ($return_as_array) {
			$filter['id_group'] = $other['data'][13];
		}
		else {
			$filterString .= ' AND id_grupo =' . $other['data'][13];
		}
	}
	
	if (isset($other['data'][14]) && ($other['data'][14] != '')) {
		
		if ($return_as_array) {
			$filter['tag'] = $other['data'][14];
		}
		else {
			$filterString .= " AND tags LIKE '" . $other['data'][14]."'";
		}
	}
	
	if (isset($other['data'][15]) && ($other['data'][15] != '')) {
		if ($return_as_array) {
			$filter['event_type'] = $other['data'][15];
		}
		else {
			$event_type = $other['data'][15];
			
			if ($event_type == "not_normal") {
				$filterString .= " AND ( event_type LIKE '%warning%'
					OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ) ";
			}
			else {
				$filterString .= ' AND event_type LIKE "%' . $event_type . '%"';
			}
		}
		
	}
	
	if ($return_as_array) {
		return $filter;
	}
	else {
		return $filterString;
	}
}

/**
 * 
 * @param $id
 * @param $id2
 * @param $other
 * @param $trash1
 */
function api_set_new_alert_template($id, $id2, $other, $trash1) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$idAgent = agents_get_agent_id($id);

		if (!util_api_check_agent_and_print_error($idAgent, 'string', "AW")){
			return;
		}
		
		$row = db_get_row_filter('talert_templates', array('name' => $id2));
		
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idTemplate = $row['id'];
		$idActionTemplate = $row['id_alert_action'];
		
		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$values = array(
			'id_agent_module' => $idAgentModule,
			'id_alert_template' => $idTemplate);
		
		$return = db_process_sql_insert('talert_template_modules', $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
}

function api_set_delete_module($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['type'] == 'string') {
		$simulate = false;
		if ($other['data'] == 'simulate') {
			$simulate = true;
		}
		
		$idAgent = agents_get_agent_id($id);

		if (!util_api_check_agent_and_print_error($idAgent, 'string', "AD")) {
			return;
		}

		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $id2));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if (!$simulate) {
			$return = modules_delete_agent_module($idAgentModule);
		}
		else {
			$return = true;
		}
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
	else {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
}

function api_set_module_data($id, $thrash2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if ($other['type'] == 'array') {
		if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), 'string', 'AW')) {
			return;
		}
		$idAgentModule = $id;
		$data = $other['data'][0];
		$time = $other['data'][1];
		
		if ($time == 'now') $time = time();
		
		$agentModule = db_get_row_filter('tagente_modulo', array('id_agente_modulo' => $idAgentModule));
		if ($agentModule === false) {
			returnError('error_parameter', 'Not found module agent.');
		}
		else {
			$agent = db_get_row_filter('tagente', array('id_agente' => $agentModule['id_agente']));
			
			$xmlTemplate = "<?xml version='1.0' encoding='ISO-8859-1'?>
				<agent_data description='' group='' os_name='%s' " .
				" os_version='%s' interval='%d' version='%s' timestamp='%s' agent_name='%s' timezone_offset='%d'>
					<module>
						<name><![CDATA[%s]]></name>
						<description><![CDATA[%s]]></description>
						<type><![CDATA[%s]]></type>
						<data><![CDATA[%s]]></data>
					</module>
				</agent_data>";
			
			$xml = sprintf($xmlTemplate, io_safe_output(get_os_name($agent['id_os'])),
				io_safe_output($agent['os_version']), $agent['intervalo'],
				io_safe_output($agent['agent_version']), date('Y/m/d H:i:s', $time),
				io_safe_output($agent['nombre']), $agent['timezone_offset'],
				io_safe_output($agentModule['nombre']), io_safe_output($agentModule['descripcion']), modules_get_type_name($agentModule['id_tipo_modulo']), $data);
			
			
			if (false === @file_put_contents($config['remote_config'] . '/' . io_safe_output($agent['nombre']) . '.' . $time . '.data', $xml)) {
				returnError('error_file', 'Can save agent data xml.');
			}
			else {
				returnData('string', array('type' => 'string', 'data' => $xml));
				return;
			}
		}
	}
	else {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
}

function api_set_new_module($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$values = array();
		$values['id_agente'] = agents_get_agent_id($id);
		if (!util_api_check_agent_and_print_error($values['id_agente'], 'string', 'AW')){
			return;
		}
		$values['nombre'] = $id2;
		
		$values['id_tipo_modulo'] = db_get_value_filter('id_tipo', 'ttipo_modulo', array('nombre' => $other['data'][0]));
		if ($values['id_tipo_modulo'] === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if ($other['data'][1] == '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$values['ip_target'] = $other['data'][1];
		
		if (strstr($other['data'][0], 'icmp') === false) {
			if (($other['data'][2] == '') || ($other['data'][2] <= 0 || $other['data'][2] > 65535)) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			
			$values['tcp_port'] = $other['data'][2];
		}
		
		$values['descripcion'] = $other['data'][3];
		
		if ($other['data'][4] != '') {
			$values['min'] = $other['data'][4];
		}
		
		if ($other['data'][5] != '') {
			$values['max'] = $other['data'][5];
		}
		
		if ($other['data'][6] != '') {
			$values['post_process'] = $other['data'][6];
		}
		
		if ($other['data'][7] != '') {
			$values['module_interval'] = $other['data'][7];
		}
		
		if ($other['data'][8] != '') {
			$values['min_warning'] = $other['data'][8];
		}
		
		if ($other['data'][9] != '') {
			$values['max_warning'] = $other['data'][9];
		}
		
		if ($other['data'][10] != '') {
			$values['str_warning'] = $other['data'][10];
		}
		
		if ($other['data'][11] != '') {
			$values['min_critical'] = $other['data'][11];
		}
		
		if ($other['data'][12] != '') {
			$values['max_critical'] = $other['data'][12];
		}
		
		if ($other['data'][13] != '') {
			$values['str_critical'] = $other['data'][13];
		}
		
		if ($other['data'][14] != '') {
			$values['history_data'] = $other['data'][14];
		}
		
		$disabled_types_event = array();
		$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][15];
		$disabled_types_event = json_encode($disabled_types_event);
		$values['disabled_types_event'] = $disabled_types_event;
		
		$values['id_modulo'] = 2; 
		
		$return = modules_create_agent_module($values['id_agente'],
			$values['nombre'], $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
}

/**
 * 
 * @param unknown_type $id
 * @param unknown_type $id2
 * @param unknown_type $other
 * @param unknown_type $trash1
 */
function api_set_alert_actions($id, $id2, $other, $trash1) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")){
		returnError('forbidden', 'string');
		return;
	}

	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$idAgent = agents_get_agent_id($id);
		if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
			return;
		}

		$row = db_get_row_filter('talert_templates', array('name' => $id2));
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		$idTemplate = $row['id'];
		
		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idAlertTemplateModule = db_get_value_filter('id', 'talert_template_modules', array('id_alert_template' => $idTemplate, 'id_agent_module' => $idAgentModule));
		if ($idAlertTemplateModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if ($other['data'][1] != '') {
			$idAction = db_get_value_filter('id', 'talert_actions', array('name' => $other['data'][1]));
			if ($idAction === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		else {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$firesMin = $other['data'][2];
		$firesMax = $other['data'][3];
		
		$values = array('id_alert_template_module' => $idAlertTemplateModule,
			'id_alert_action' => $idAction, 'fires_min' => $firesMin, 'fires_max' => $firesMax);
		
		$return = db_process_sql_insert('talert_template_module_actions', $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
}

function api_set_new_event($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	$time = get_system_time();
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$values = array();
		
		if (($other['data'][0] == null) && ($other['data'][0] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['evento'] = $other['data'][0];
		}
		
		if (($other['data'][1] == null) && ($other['data'][1] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$valuesAvaliable = array('unknown', 'alert_fired', 'alert_recovered',
				'alert_ceased', 'alert_manual_validation',
				'recon_host_detected', 'system','error', 'new_agent',
				'going_up_warning', 'going_up_critical', 'going_down_warning',
				'going_down_normal', 'going_down_critical', 'going_up_normal','configuration_change');
			
			if (in_array($other['data'][1], $valuesAvaliable)) {
				$values['event_type'] = $other['data'][1];
			}
			else {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		
		if (($other['data'][2] == null) && ($other['data'][2] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['estado'] = $other['data'][2];
		}
		
		if (($other['data'][3] == null) && ($other['data'][3] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['id_agente'] = agents_get_agent_id($other['data'][3]);
		}
		
		if (($other['data'][4] == null) && ($other['data'][4] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo',
				array('nombre' => $other['data'][4], 'id_agente' => $values['id_agente']));
		}
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['id_agentmodule'] = $idAgentModule;
		}
		
		if (($other['data'][5] == null) && ($other['data'][5] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			if ($other['data'][5] != 'all') {
				$idGroup = db_get_value_filter('id_grupo', 'tgrupo', array('nombre' => $other['data'][5]));
			}
			else {
				$idGroup = 0;
			}
			
			if ($idGroup === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			else {
				$values['id_grupo'] = $idGroup;
			}
		}
		
		if (($other['data'][6] == null) && ($other['data'][6] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			if (($other['data'][6] >= 0) && ($other['data'][6] <= 4)) {
				$values['criticity'] = $other['data'][6];
			}
			else {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		
		if (($other['data'][7] == null) && ($other['data'][7] == '')) {
			//its optional parameter
		}
		else {
			$idAlert = db_get_value_sql("SELECT t1.id 
				FROM talert_template_modules t1 
					INNER JOIN talert_templates t2 
						ON t1.id_alert_template = t2.id 
				WHERE t1.id_agent_module = 1
					AND t2.name LIKE '" . $other['data'][7] . "'");
			
			if ($idAlert === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			else {
				$values['id_alert_am'] = $idAlert;
			}
		}
	}
	
	$values['timestamp'] = date("Y-m-d H:i:s", $time);
	$values['utimestamp'] = $time;
	
	$return = db_process_sql_insert('tevento', $values);
	
	$data['type'] = 'string';
	if ($return === false) {
		$data['data'] = 0;
	}
	else {
		$data['data'] = $return;
	}
	returnData('string', $data);
	return;
}

function api_set_event_validate_filter_pro($trash1, $trash2, $other, $trash3) {
	global $config;

	if (!check_acl($config['id_user'], 0, "EW")){
		returnError('forbidden', 'string');
		return;
	}

	$table_events = 'tevento';
	if (is_metaconsole()) {
		$table_events = 'tmetaconsole_event';
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$filter = array();
		
		if (($other['data'][1] != null) && ($other['data'][1] != -1)
			&& ($other['data'][1] != '')) {
			
			$filter['criticity'] = $other['data'][1];
		}
		
		if (($other['data'][2] != null) && ($other['data'][2] != -1)
			&& ($other['data'][2] != '')) {
			
			$filter['id_agente'] = $other['data'][2];
		}
		
		if (($other['data'][3] != null) && ($other['data'][3] != -1)
			&& ($other['data'][3] != '')) {
			
			$filter['id_agentmodule'] = $other['data'][3];
		}
		
		if (($other['data'][4] != null) && ($other['data'][4] != -1)
			&& ($other['data'][4] != '')) {
			
			$filter['id_alert_am'] = $other['data'][4];
		}
		
		if (($other['data'][5] != null) && ($other['data'][5] != '')) {
			$filter['id_usuario'] = $other['data'][5];
		}
		
		$filterString = db_format_array_where_clause_sql ($filter);
		if ($filterString == '') {
			$filterString = '1 = 1';
		}
		
		if (($other['data'][6] != null) && ($other['data'][6] != -1)) {
			$filterString .= ' AND utimestamp > ' . $other['data'][6];
		}
		
		if (($other['data'][7] != null) && ($other['data'][7] != -1)) {
			$filterString .= 'AND utimestamp < ' . $other['data'][7];
		}

		if (!users_can_manage_group_all("EW")) {
			$user_groups = implode (',', array_keys(users_get_groups(
				$config['id_user'], "EW", false
			)));
			$filterString .= " AND id_grupo IN ($user_groups) ";
		}
	}

	$count = db_process_sql_update($table_events,
		array('estado' => 1), $filterString);

	returnData('string',
		array('type' => 'string', 'data' => $count));
	return;
}

function api_set_event_validate_filter($trash1, $trash2, $other, $trash3) {
	global $config;

	if (!check_acl($config['id_user'], 0, "EW")){
		returnError('forbidden', 'string');
		return;
	}

	$simulate = false;
	
	$table_events = 'tevento';
	if (is_metaconsole()) {
		$table_events = 'tmetaconsole_event';
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		
		if (($other['data'][8] != null) && ($other['data'][8] != '')) {
			if ($other['data'][8] == 'simulate') {
				$simulate = true;
			}
		}
		
		$filterString = otherParameter2Filter($other);

		if (!users_can_manage_group_all("EW")) {
			$user_groups = implode (',', array_keys(users_get_groups(
				$config['id_user'], "EW", false
			)));
			$filterString .= " AND id_grupo IN ($user_groups) ";
		}
	}
	
	if ($simulate) {
		$rows = db_get_all_rows_filter($table_events, $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		$count = db_process_sql_update(
			$table_events, array('estado' => 1), $filterString);
		
		returnData('string',
			array('type' => 'string', 'data' => $count));
		return;
	}
}

function api_set_validate_events($id_event, $trash1, $other, $return_type, $user_in_db) {
	$text = $other['data'];
	
	// Set off the standby mode when close an event
	$event = events_get_event ($id_event);
	alerts_agent_module_standby ($event['id_alert_am'], 0);
	
	$result = events_change_status ($id_event, EVENT_VALIDATE);
	
	if ($result) {
		if (!empty($text)) {
			//Set the comment for the validation
			events_comment($id_event, $text);
		}
		
		returnData('string',
			array('type' => 'string', 'data' => 'Correct validation'));
	}
	else {
		returnError('Error in validation operation.');
	}
}

function api_get_gis_agent($id_agent, $trash1, $tresh2, $return_type, $user_in_db) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, $return_type)) return;

	$agent_gis_data = db_get_row_sql("
		SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = " . $id_agent);

	if ($agent_gis_data) {
		returnData($return_type,
			array('type' => 'array', 'data' => array($agent_gis_data)));
	}
	else {
		returnError('get_gis_agent', __('There is not gis data for the agent'));
	}
}

function api_set_gis_agent_only_position($id_agent, $trash1, $other, $return_type, $user_in_db) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) return;

	$new_gis_data = $other['data'];
	
	$correct = true;
	
	if (isset($new_gis_data[0])) {
		$latitude = $new_gis_data[0];
	}
	else $correct = false;
	
	if (isset($new_gis_data[1])) {
		$longitude = $new_gis_data[1];
	}
	else $correct = false;
	
	if (isset($new_gis_data[2])) {
		$altitude = $new_gis_data[2];
	}
	else $correct = false;
	
	if (!$config['activate_gis']) {
		$correct = false;
		returnError('error_gis_agent_only_position', __('Gis not activated'));
		return;
	}
	else {
		if ($correct) {
			$correct = agents_update_gis($id_agent, $latitude,
				$longitude, $altitude, 0, 1, date( 'Y-m-d H:i:s'), null,
				1, __('Save by %s Console', get_product_name()),
				__('Update by %s Console', get_product_name()),
				__('Insert by %s Console', get_product_name()));
		} else {
			returnError('error_gis_agent_only_position', __('Missing parameters'));
			return;
		}
	}
	
	$data = array('type' => 'string', 'data' => (int)$correct);
	
	$returnType = 'string';
	returnData($returnType, $data);
}

function api_set_gis_agent($id_agent, $trash1, $other, $return_type, $user_in_db) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) return;

	$new_gis_data = $other['data'];
	
	$correct = true;
	
	if (isset($new_gis_data[0])) {
		$latitude = $new_gis_data[0];
	}
	else $correct = false;
	
	if (isset($new_gis_data[1])) {
		$longitude = $new_gis_data[1];
	}
	else $correct = false;
	
	if (isset($new_gis_data[2])) {
		$altitude = $new_gis_data[2];
	}
	else $correct = false;
	
	if (isset($new_gis_data[3])) {
		$ignore_new_gis_data = $new_gis_data[3];
	}
	else $correct = false;
	
	if (isset($new_gis_data[4])) {
		$manual_placement = $new_gis_data[4];
	}
	else $correct = false;
	
	if (isset($new_gis_data[5])) {
		$start_timestamp = $new_gis_data[5];
	}
	else $correct = false;
	
	if (isset($new_gis_data[6])) {
		$end_timestamp = $new_gis_data[6];
	}
	else $correct = false;
	
	if (isset($new_gis_data[7])) {
		$number_of_packages = $new_gis_data[7];
	}
	else $correct = false;
	
	if (isset($new_gis_data[8])) {
		$description_save_history = $new_gis_data[8];
	}
	else $correct = false;
	
	if (isset($new_gis_data[9])) {
		$description_update_gis = $new_gis_data[9];
	}
	else $correct = false;
	
	if (isset($new_gis_data[10])) {
		$description_first_insert = $new_gis_data[10];
	}
	else $correct = false;
	
	if (!$config['activate_gis']) {
		$correct = false;
		returnError('error_gis_agent_only_position', __('Gis not activated'));
		return;
	}
	else {
		if ($correct) {
			$correct = agents_update_gis($id_agent, $latitude,
				$longitude, $altitude, $ignore_new_gis_data,
				$manual_placement, $start_timestamp, $end_timestamp,
				$number_of_packages, $description_save_history,
				$description_update_gis, $description_first_insert);
		} else {
			returnError('error_set_ig_agent', __('Missing parameters'));
			return;
		}
	}
	
	$data = array('type' => 'string', 'data' => (int)$correct);
	
	$returnType = 'string';
	returnData($returnType, $data);
}

function get_events_with_user($trash1, $trash2, $other, $returnType, $user_in_db) {
	global $config;
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}
	
	//By default.
	$status = 3;
	$search = '';
	$event_type = '';
	$severity = -1;
	$id_agent = -1;
	$id_agentmodule = -1;
	$id_alert_am = -1;
	$id_event = -1;
	$id_user_ack = 0;
	$event_view_hr = 0;
	$tag = '';
	$group_rep = 0;
	$offset = 0;
	$pagination = 40;
	$utimestamp_upper = 0;
	$utimestamp_bottom = 0;
	
	$filter = otherParameter2Filter($other, true);
	
	if (isset($filter['criticity']))
		$severity = $filter['criticity'];
	if (isset($filter['id_agente']))
		$id_agent = $filter['id_agente'];
	if (isset($filter['id_agentmodule']))
		$id_agentmodule = $filter['id_agentmodule'];
	if (isset($filter['id_alert_am']))
		$id_alert_am = $filter['id_alert_am'];
	if (isset($filter['id_usuario']))
		$id_user_ack = $filter['id_usuario'];
	if (isset($filter['estado']))
		$status = $filter['estado'];
	if (isset($filter['evento']))
		$search = $filter['evento'];
	if (isset($filter['limit']))
		$pagination = $filter['limit'];
	if (isset($filter['offset']))
		$offset = $filter['offset'];
	
	
	$id_group = (int)$filter['id_group'];
	
	$user_groups = users_get_groups ($user_in_db, "ER");
	$user_id_groups = array();
	if (!empty($user_groups))
		$user_id_groups = array_keys ($user_groups);
	
	$is_admin = (bool)db_get_value('is_admin', 'tusuario', 'id_user',
		$user_in_db);
	
	if (isset($filter['id_group'])) {
		//The admin can see all groups
		if ($is_admin) {
			if (($id_group !== -1) && ($id_group !== 0))
				$id_groups = array($id_group);
		}
		else {
			if (empty($id_group)) {
				$id_groups = $user_id_groups;
			}
			else {
				if (in_array($id_group, $user_id_groups)) {
					$id_groups = array($id_group);
				}
				else {
					$id_groups = array();
				}
			}
		}
	}
	else {
		if (!$is_admin) {
			$id_groups = $user_id_groups;
		}
	}
	
	if (isset($filter['tag']))
		$tag = $filter['tag'];
	if (isset($filter['event_type']))
		$event_type = $filter['event_type'];
	if ($filter['utimestamp']) {
		if (isset($filter['utimestamp']['>'])) {
			$utimestamp_upper = $filter['utimestamp']['>'];
		}
		if (isset($filter['utimestamp']['<'])) {
			$utimestamp_bottom = $filter['utimestamp']['<'];
		}
	}
	
	
	//TODO MOVE THIS CODE AND THE CODE IN pandora_console/operation/events/events_list.php
	//to a function.
	
	
	
	$sql_post = '';
	
	if (!empty($id_groups)) {
		$sql_post = " AND id_grupo IN (".implode (",", $id_groups).")";
	}
	else {
		//The admin can see all groups
		if (!$is_admin) {
			$sql_post = " AND 1=0";
		}
	}
	
	// Skip system messages if user is not PM
	if (!check_acl ($user_in_db, 0, "PM")) {
		$sql_post .= " AND id_grupo != 0";
	}
	
	switch ($status) {
		case 0:
		case 1:
		case 2:
			$sql_post .= " AND estado = ".$status;
			break;
		case 3:
			$sql_post .= " AND (estado = 0 OR estado = 2)";
			break;
	}
	
	if ($search != "") {
		$sql_post .= " AND evento LIKE '%".io_safe_input($search)."%'";
	}
	
	if ($event_type != "") {
		// If normal, warning, could be several (going_up_warning, going_down_warning... too complex
		// for the user so for him is presented only "warning, critical and normal"
		if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
			$sql_post .= " AND event_type LIKE '%$event_type%' ";
		}
		elseif ($event_type == "not_normal") {
			$sql_post .= " AND ( event_type LIKE '%warning%'
				OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ) ";
		}
		else {
			$sql_post .= " AND event_type = '".$event_type."'";
		}
	
	}
	
	if ($severity != -1)
		$sql_post .= " AND criticity = ".$severity;
	
	if ($id_agent != -1)
		$sql_post .= " AND id_agente = ".$id_agent;
	
	if ($id_agentmodule != -1)
		$sql_post .= " AND id_agentmodule = ".$id_agentmodule;
	
	if ($id_alert_am != -1)
		$sql_post .= " AND id_alert_am = ".$id_alert_am;
	
	if ($id_event != -1)
		$sql_post .= " AND id_evento = " . $id_event;
	
	if ($id_user_ack != "0")
		$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";
	
	if ($utimestamp_upper != 0)
		$sql_post .= " AND utimestamp >= " . $utimestamp_upper;
	
	if ($utimestamp_bottom != 0)
		$sql_post .= " AND utimestamp <= " . $utimestamp_bottom;
	
	if ($event_view_hr > 0) {
		//Put hours in seconds
		$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
		$sql_post .= " AND (utimestamp > " . $unixtime . " OR estado = 2)";
	}
	
	//Search by tag
	if ($tag != "") {
		$sql_post .= " AND tags LIKE '" . io_safe_input($tag) . "'";
	}
	
	//Inject the raw sql
	if (isset($filter['sql'])) {
		$sql_post .= " AND (" . $filter['sql'] . ") ";
	}
	
	
	if ($group_rep == 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				if ($filter['total']) {
					$sql = "SELECT COUNT(*)
						FROM " . $table_events . "
						WHERE 1=1 " . $sql_post;
				}
				else if ($filter['more_criticity']) {
					$sql = "SELECT criticity
						FROM " . $table_events . "
						WHERE 1=1 " . $sql_post . "
						ORDER BY criticity DESC
						LIMIT 1";
				}
				else {
					if (defined ('METACONSOLE')) {
						$sql = "SELECT *,
							(SELECT t2.nombre
								FROM tgrupo t2
								WHERE t2.id_grupo = " . $table_events . ".id_grupo) AS group_name,
							(SELECT t2.icon
								FROM tgrupo t2
								WHERE t2.id_grupo = " . $table_events . ".id_grupo) AS group_icon
							FROM " . $table_events . "
							WHERE 1=1 " . $sql_post . "
							ORDER BY utimestamp DESC
							LIMIT " . $offset . "," . $pagination;
					}
					else {
						$sql = "SELECT *,
							(SELECT t1.alias
								FROM tagente t1
								WHERE t1.id_agente = tevento.id_agente) AS agent_name,
							(SELECT t2.nombre
								FROM tgrupo t2
								WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
							(SELECT t2.icon
								FROM tgrupo t2
								WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
							(SELECT tmodule.name
								FROM tmodule
								WHERE id_module IN (
									SELECT tagente_modulo.id_modulo
									FROM tagente_modulo
									WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
							FROM " . $table_events . "
							WHERE 1=1 " . $sql_post . "
							ORDER BY utimestamp DESC
							LIMIT " . $offset . "," . $pagination;
					}
					
				}
				break;
			case "postgresql":
				//TODO TOTAL
				$sql = "SELECT *,
					(SELECT t1.alias
						FROM tagente t1
						WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre
						FROM tgrupo t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon
						FROM tgrupo t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
					(SELECT tmodule.name
						FROM tmodule
						WHERE id_module IN (
							SELECT tagente_modulo.id_modulo
							FROM tagente_modulo
							WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
					FROM tevento
					WHERE 1=1 " . $sql_post . "
					ORDER BY utimestamp DESC
					LIMIT " . $pagination . " OFFSET " . $offset;
				break;
			case "oracle":
				//TODO TOTAL
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				
				$sql = "SELECT *,
					(SELECT t1.alias
						FROM tagente t1
						WHERE t1.id_agente = tevento.id_agente) AS alias,
					(SELECT t1.nombre
						FROM tagente t1
						WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre
						FROM tgrupo t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon
						FROM tgrupo t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
					(SELECT tmodule.name
						FROM tmodule
						WHERE id_module IN (
							SELECT tagente_modulo.id_modulo
							FROM tagente_modulo
							WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
					FROM tevento
					WHERE 1=1 " . $sql_post . " ORDER BY utimestamp DESC"; 
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				db_process_sql ('SET group_concat_max_len = 9999999');
				
				$sql = "SELECT *, MAX(id_evento) AS id_evento,
						GROUP_CONCAT(DISTINCT user_comment SEPARATOR '') AS user_comment,
						MIN(estado) AS min_estado, MAX(estado) AS max_estado,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM " . $table_events . "
					WHERE 1=1 " . $sql_post . "
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC
					LIMIT " . $offset . "," . $pagination;
				break;
			case "postgresql":
				$sql = "SELECT *, MAX(id_evento) AS id_evento,
						array_to_string(array_agg(DISTINCT user_comment), '') AS user_comment,
						MIN(estado) AS min_estado, MAX(estado) AS max_estado,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM " . $table_events . "
					WHERE 1=1 " . $sql_post . "
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC
					LIMIT " . $pagination . " OFFSET " . $offset;
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				// TODO: Remove duplicate user comments
				$sql = "SELECT a.*, b.event_rep, b.timestamp_rep
					FROM (SELECT *
						FROM tevento
						WHERE 1=1 ".$sql_post.") a, 
					(SELECT MAX (id_evento) AS id_evento,
						to_char(evento) AS evento, id_agentmodule,
						COUNT(*) AS event_rep, MIN(estado) AS min_estado,
						MAX(estado) AS max_estado,
						LISTAGG(user_comment, '') AS user_comment,
						MAX(utimestamp) AS timestamp_rep 
					FROM " . $table_events . " 
					WHERE 1=1 ".$sql_post." 
					GROUP BY to_char(evento), id_agentmodule) b 
					WHERE a.id_evento=b.id_evento AND 
						to_char(a.evento)=to_char(b.evento) AND
						a.id_agentmodule=b.id_agentmodule";
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
	
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
	}
	
	$result = db_get_all_rows_sql ($sql);
	
	if (($result !== false) &&
		(!$filter['total']) &&
		(!$filter['more_criticity'])) {
		
		$urlImage = ui_get_full_url(false);
		
		//Add the description and image
		foreach ($result as $key => $row) {
			if (defined ('METACONSOLE')) {
				$row['agent_name'] = agents_meta_get_name (
					$row['id_agente'],
					"none", $row['server_id']);
				
				$row['module_name'] = meta_modules_get_name(
					$row['id_agentmodule'], $row['server_id']);
			}
			
			//FOR THE TEST THE API IN THE ANDROID
			//$row['evento'] = $row['id_evento'];
			
			$row['description_event'] = events_print_type_description($row["event_type"], true);
			$row['img_description'] = events_print_type_img ($row["event_type"], true, true);
			$row['criticity_name'] = get_priority_name ($row["criticity"]);
			
			switch ($row["criticity"]) {
				default:
				case EVENT_CRIT_MAINTENANCE:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_maintenance.png";
					break;
				case EVENT_CRIT_INFORMATIONAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_informational.png";
					break;
				case EVENT_CRIT_NORMAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_normal.png";
					break;
				case EVENT_CRIT_WARNING:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_warning.png";
					break;
				case EVENT_CRIT_CRITICAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_critical.png";
					break;
			}
			$row['img_criticy'] = $img_sev;
			
			
			$result[$key] = $row;
		}
	}
	
	$data['type'] = 'array';
	$data['data'] = $result;
	
	returnData($returnType, $data, $separator);
	
	if (empty($result))
		return false;
	
	return true;
}

/**
 * 
 * @param $trash1
 * @param $trah2
 * @param $other
 * @param $returnType
 * @param $user_in_db
 */
function api_get_events($trash1, $trash2, $other, $returnType, $user_in_db = null) {
	if ($user_in_db !== null) {
		$correct = get_events_with_user($trash1, $trash2, $other,
			$returnType, $user_in_db);
		
		$last_error = error_get_last();
		if (!$correct && !empty($last_error)) {
			$errors = array(E_ERROR, E_WARNING, E_USER_ERROR,
				E_USER_WARNING);
			if (in_array($last_error['type'], $errors)) {
				returnError('ERROR_API_PANDORAFMS', $returnType);
			}
		}
		return;
	}
	
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		
		$filterString = otherParameter2Filter($other);
	}
	
	
	if (defined ('METACONSOLE')) {
		$dataRows = db_get_all_rows_filter('tmetaconsole_event', $filterString);
	}
	else {
		$dataRows = db_get_all_rows_filter('tevento', $filterString);
	}
	$last_error = error_get_last();
	if (empty($dataRows)) {
		if (!empty($last_error)) {
			returnError('ERROR_API_PANDORAFMS', $returnType);
			
			return;
		}
	}
	
	$data['type'] = 'array';
	$data['data'] = $dataRows;
	
	returnData($returnType, $data, $separator);
	return;
}

/**
 * Delete user.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_user($id, $thrash1, $thrash2, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "UM")) {
		returnError('forbidden', 'string');
		return;
	}

	if (!delete_user($id))
		returnError('error_delete_user', 'Error delete user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Delete user.')));
}

/**
 * Add user to profile and group.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <group>;<profile> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=add_user_profile&id=example_user_name&other=12|4&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_add_user_profile($id, $thrash1, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$group = $other['data'][0];
	$profile = $other['data'][1];

	if (db_get_value('id_grupo', 'tgrupo', 'id_grupo', $group) === false) {
		returnError('error_set_add_user_profile', __('There is not any group with the id provided'));
		return;
	}
	if (db_get_value('id_perfil', 'tperfil', 'id_perfil', $profile) === false) {
		returnError('error_set_add_user_profile', __('There is not any profile with the id provided'));
		return;
	}

	if (!check_acl($config['id_user'], $group, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	if (!profile_create_user_profile ($id, $profile, $group,'API'))
		returnError('error_add_user_profile', 'Error add user profile.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Add user profile.')));
}

/**
 * Deattach user from group and profile.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <group>;<profile> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=delete_user_profile&id=md&other=12|4&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.
 */
function api_set_delete_user_profile($id, $thrash1, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$group = $other['data'][0];
	$profile = $other['data'][1];

	if (db_get_value('id_grupo', 'tgrupo', 'id_grupo', $group) === false) {
		returnError('error_set_add_user_profile', __('There is not any group with the id provided'));
		return;
	}
	if (db_get_value('id_perfil', 'tperfil', 'id_perfil', $profile) === false) {
		returnError('error_set_add_user_profile', __('There is not any profile with the id provided'));
		return;
	}

	if (!check_acl($config['id_user'], $group, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$where = array(
		'id_usuario' => $id,
		'id_perfil' => $profile,
		'id_grupo' => $group);
	$result = db_process_sql_delete('tusuario_perfil', $where);
	if ($return === false)
		returnError('error_delete_user_profile', 'Error delete user profile.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Delete user profile.')));
}

/**
 * Create new incident in Pandora.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <title>;<description>;
 *  <origin>;<priority>;<state>;<group> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as
 *  othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_incident&other=titulo|descripcion%20texto|Logfiles|2|10|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function api_set_new_incident($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "IW")) {
		returnError('forbidden', 'string');
		return;
	}

	$title = $other['data'][0];
	$description = $other['data'][1];
	$origin = $other['data'][2];
	$priority = $other['data'][3];
	$id_creator = 'API';
	$state = $other['data'][4];
	$group = $other['data'][5];
	
	$values = array(
		'inicio' => 'NOW()',
		'actualizacion' => 'NOW()',
		'titulo' => $title,
		'descripcion' => $description,
		'id_usuario' => 'API',
		'origen' => $origin, 
		'estado' => $state,
		'prioridad' => $priority,
		'id_grupo' => $group,
		'id_creator' => $id_creator);
	$idIncident = db_process_sql_insert('tincidencia', $values);
	
	if ($return === false)
		returnError('error_new_incident', 'Error create new incident.');
	else
		returnData('string', array('type' => 'string', 'data' => $idIncident));
}

/**
 * Add note into a incident.
 * 
 * @param $id string Username author of note.
 * @param $id2 integer ID of incident.
 * @param $other string Note.
 * @param $thrash2 Don't use.
 */
function api_set_new_note_incident($id, $id2, $other, $thrash2) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "IW")) {
		returnError('forbidden', 'string');
		return;
	}

	$values = array(
		'id_usuario' => $id,
		'id_incident' => $id2,
		'nota' => $other['data']);
	
	$idNote = db_process_sql_insert('tnota', $values);
	
	if ($idNote === false)
		returnError('error_new_incident', 'Error create new incident.');
	else
		returnData('string', array('type' => 'string', 'data' => $idNote));
}


/**
 * Disable a module, given agent and module name.
 * 
 * @param string $agent_name Name of agent.
 * @param string $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
// http://localhost/pandora_console/include/api.php?op=set&op2=enable_module&id=garfio&id2=Status
 */

function api_set_disable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AD')) {
		return;
	}
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	$result = modules_change_disabled($id_agent_module, 1);
	
	if ($result === NOERR) {
		returnData('string', array('type' => 'string', 'data' => __('Correct module disable')));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Error disabling module')));
	}
}


/**
 * Enable a module, given agent and module name.
 * 
 * @param string $agent_name Name of agent.
 * @param string $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 */

function api_set_enable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	if (defined ('METACONSOLE')) {
		return;
	}

	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AD')) {
		return;
	}
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	$result = modules_change_disabled($id_agent_module, 0);
	
	if ($result === NOERR) {
		returnData('string', array('type' => 'string', 'data' => __('Correct module enable')));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Error enabling module')));
	}
}


/**
 * Disable an alert 
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_alert&id=c2cea5860613e363e25f4ba185b54fe28f869ff8a5e8bb46343288337c903531&id2=Status&other=Warning%20condition
 */

function api_set_disable_alert ($agent_name, $module_name, $template_name, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}
	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AD")) return;
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
	
	$result = db_process_sql("UPDATE talert_template_modules
		SET disabled = 1
		WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	
	if ($result) {
		returnData('string', array('type' => 'string', 'data' => "Correct alert disable"));
	} else {
		returnData('string', array('type' => 'string', 'data' => __('Error alert disable')));
	}
}


/**
 * Disable an alert with alias
 * 
 * @param string $agent_alias Alias of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_alert_alias&id=garfio&id2=Status&other=Warning%20condition
 */

function api_set_disable_alert_alias ($agent_alias, $module_name, $template_name, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}

	$agent_id = agents_get_agent_id_by_alias($agent_alias);
	$result = false;
	foreach ($agent_id as $key => $id_agent) {
		if (!util_api_check_agent_and_print_error($id_agent['id_agente'], 'string', "AD")) {
			continue;
		}
		$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent['id_agente'], 'nombre' => $module_name));
		$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
		
		$result = db_process_sql("UPDATE talert_template_modules
			SET disabled = 1
			WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
		
		if ($result) {
			returnData('string', array('type' => 'string', 'data' => "Correct alert disable"));
			return;
		}
	}
	
	if(!$result){
		returnData('string', array('type' => 'string', 'data' => __('Error alert disable')));
	}
}

/**
 * Enable an alert
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_alert&id=garfio&id2=Status&other=Warning%20condition
 */

function api_set_enable_alert ($agent_name, $module_name, $template_name, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")) {
		returnError('forbidden');
		return;
	}

	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) return;
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
	
	$result = db_process_sql("UPDATE talert_template_modules
		SET disabled = 0
		WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	
	if ($result) {
		returnData('string', array('type' => 'string', 'data' => "Correct alert enable"));
	} else {
		returnData('string', array('type' => 'string', 'data' => __('Error alert enable')));
	}
}

/**
 * Enable an alert with alias
 * 
 * @param string $agent_alias Alias of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_alert_alias&id=garfio&id2=Status&other=Warning%20condition
	*/

function api_set_enable_alert_alias ($agent_alias, $module_name, $template_name, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")) {
		returnError('forbidden');
		return;
	}

	$agent_id = agents_get_agent_id_by_alias($agent_alias);
	$result = false;
	foreach ($agent_id as $key => $id_agent) {
		if (!util_api_check_agent_and_print_error($id_agent['id_agente'], 'string', "AW")) {
			continue;
		}
		$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent['id_agente'], 'nombre' => $module_name));
		$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
		
		$result = db_process_sql("UPDATE talert_template_modules
			SET disabled = 0
			WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
		
		if ($result) {
			returnData('string', array('type' => 'string', 'data' => "Correct alert enable"));
			return;
		}
	}
	
	if(!$result){
		returnData('string', array('type' => 'string', 'data' => __('Error alert enable')));
	}
}


/**
 * Disable all the alerts of one module
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_module_alerts&id=garfio&id2=Status
 */

function api_set_disable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")) {
		returnError('forbidden');
		return;
	}

	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) return;
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 1
		WHERE id_agent_module = $id_agent_module");
	
	returnData('string', array('type' => 'string', 'data' => "Correct alerts disable"));
}

/**
 * Enable all the alerts of one module
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_module_alerts&id=garfio&id2=Status
 */

function api_set_enable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LW")) {
		returnError('forbidden');
		return;
	}

	$id_agent = agents_get_agent_id($agent_name);
	if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) return;
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 0
		WHERE id_agent_module = $id_agent_module");
	
	returnData('string', array('type' => 'string', 'data' => "Correct alerts enable"));
}

function api_get_tags($thrash1, $thrash2, $other, $returnType, $user_in_db) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config["id_user"], 0, "AR")){
		returnError("forbidden", $returnType);
		return;
	}

	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
	}
	
	$tags = tags_get_all_tags();
	
	$data_tags = array();
	foreach ($tags as $id => $tag) {
		$data_tags[] = array($id, $tag);
	}
	
	$data['type'] = 'array';
	$data['data'] = $data_tags;
	
	returnData($returnType, $data, $separator);
}

/**
 * Total modules for a group given
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_modules&id=1&apipass=1234&user=admin&pass=pandora
function api_get_total_modules($id_group, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config["id_user"], 0, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}

	$groups_clause = "1 = 1";
	if (!users_is_admin($config["id_user"])) {
		$user_groups = implode (',', array_keys(users_get_groups()));
		$groups_clause = "(ta.id_grupo IN ($user_groups) OR tasg.id_group IN ($user_groups))";
	}

	$sql = "SELECT COUNT(DISTINCT(id_agente_modulo))
		FROM tagente_modulo tam, tagente ta
		LEFT JOIN tagent_secondary_group tasg
			ON ta.id_agente = tasg.id_agent
		WHERE tam.id_agente = ta.id_agente AND id_module_group = $id_group
			AND delete_pending = 0 AND $groups_clause";

	$total = db_get_value_sql($sql);
	
	$data = array('type' => 'string', 'data' => $total);
	
	returnData($returnType, $data);
}

/**
 * Total modules for a given group
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_agents&id=2&apipass=1234&user=admin&pass=pandora
function api_get_total_agents($id_group, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	// Only for agent reader of specified group
	if (!check_acl($config["id_user"], $id_group, "AR")) {
		returnError('forbidden', $returnType);
		return;
	}

	$total_agents = agents_count_agents_filter(array ('id_grupo' => $id_group));

	$data = array('type' => 'string', 'data' => $total_agents);
	returnData($returnType, $data);
}

/**
 * Agent name for a given id
 * 
 * @param int $id_agent 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=agent_name&id=1&apipass=1234&user=admin&pass=pandora
function api_get_agent_name($id_agent, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error($id_agent, $returnType)) return;

	$sql = sprintf('SELECT nombre
		FROM tagente
		WHERE id_agente = %d', $id_agent);
	$value = db_get_value_sql($sql);

	$data = array('type' => 'string', 'data' => $value);

	returnData($returnType, $data);
}

/**
 *  Return the ID or an hash of IDs of the detected given agents
 * 
 *  @param array or value $data
 * 
 * 
**/
function api_get_agent_id($trash1, $trash2, $data, $returnType) {
	$response;

	if (is_metaconsole()) {
		return;
	}
	if (empty($returnType)) {
		$returnType = "json";
	}

	$response = array();

	if ($data["type"] == "array") {
		$response["type"] = "array";
		$response["data"] = array();

		foreach ($data["data"] as $name) {
			$response["data"][$name] = agents_get_agent_id($name, 1);
		}
	} else {
		$response["type"] = "string";
		$response["data"] = agents_get_agent_id($data["data"], 1);
	}

	returnData($returnType, $response);
}

/**
 * Agent alias for a given id
 * 
 * @param int $id_agent 
 * @param int $id_node Only for metaconsole
 * @param $thrash1 Don't use.
 * @param $returnType
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=agent_alias&id=1&apipass=1234&user=admin&pass=pandora
// http://localhost/pandora_console/enterprise/meta/include/api.php?op=get&op2=agent_alias&id=1&id2=1&apipass=1234&user=admin&pass=pandora
function api_get_agent_alias($id_agent, $id_node, $trash1, $returnType) {
	$table_agent_alias = 'tagente';
	$force_meta=false;

	if (is_metaconsole()) {
		$table_agent_alias = 'tmetaconsole_agent';
		$force_meta = true;
		$id_agent = db_get_value_sql("SELECT id_agente FROM tmetaconsole_agent WHERE id_tagente = $id_agent AND id_tmetaconsole_setup = $id_node");
	}

	if (!util_api_check_agent_and_print_error($id_agent, $returnType, 'AR', $force_meta)) return;

	$sql = sprintf('SELECT alias
		FROM ' . $table_agent_alias . '
		WHERE id_agente = %d', $id_agent);
	$value = db_get_value_sql($sql);

	$data = array('type' => 'string', 'data' => $value);

	returnData($returnType, $data);
}

/**
 * Module name for a given id
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=module_name&id=20&apipass=1234&user=admin&pass=pandora
function api_get_module_name($id_module, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), $returnType)) return;

	$sql = sprintf('SELECT nombre
		FROM tagente_modulo
		WHERE id_agente_modulo = %d', $id_module);
	
	$value = db_get_value_sql($sql);
	
	if ($value === false) {
		returnError('id_not_found', $returnType);
	}
	
	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

// http://localhost/pandora_console/include/api.php?op=get&op2=alert_action_by_group&id=3&id2=1&apipass=1234&user=admin&pass=pandora
function api_get_alert_action_by_group($id_group, $id_action, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], $id_group, "LW")) {
		returnError('forbidden', $returnType);
		return;
	}

	// Get only the user groups
	$filter_groups = "1 = 1";
	if (!users_is_admin($config['id_user'])) {
		$user_groups = implode (',', array_keys(users_get_groups()));
		$filter_groups = "(ta.id_grupo IN ($user_groups) OR tasg.id_group IN ($user_groups))";
	}

	$sql = "SELECT SUM(internal_counter)
		FROM
			talert_template_modules tatm,
			tagente ta LEFT JOIN tagent_secondary_group tasg
				ON ta.id_agente = tasg.id_agent,
			tagente_modulo tam
		WHERE tam.id_agente = ta.id_agente
			AND tatm.id_agent_module = tam.id_agente_modulo
			AND ta.disabled = 0
			AND $filter_groups";

	$value = db_get_value_sql($sql);

	if ($value === false) {
		returnError('data_not_found', __('No alert found'));
		return;
	}
	else if ($value == '') {
		$value = 0;
	}
	
	$data = array('type' => 'string', 'data' => $value);
	returnData($returnType, $data);
}

// http://localhost/pandora_console/include/api.php?op=get&op2=event_info&id=58&apipass=1234&user=admin&pass=pandora
function api_get_event_info($id_event, $trash1, $trash, $returnType) {
	global $config;

	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}

	$sql = "SELECT *
		FROM " . $table_events . "
		WHERE id_evento=$id_event";
	$event_data = db_get_row_sql($sql);

	// Check the access to group
	if (!empty($event_data['id_grupo']) && $event_data['id_grupo'] > 0 && !$event_data['id_agente']) {
		if (!check_acl($config['id_user'], $event_data['id_grupo'], "ER")) {
			returnError('forbidden', $returnType);
			return;
		}
	}
	// Check the access to agent
	if (!empty($event_data['id_agente']) && $event_data['id_agente'] > 0) {
		if (!util_api_check_agent_and_print_error($event_data['id_agente'], $returnType)) return;
	}

	$i = 0;
	foreach ($event_data as $key => $data) {
		$data = strip_tags($data);
		$data = str_replace("\n",' ',$data);
		$data = str_replace(';',' ',$data);
		if ($i == 0)
			$result = $key.': '.$data.'<br>';
		else
			$result .= $key.': '.$data.'<br>'; 
		$i++;
	}
	
	$data = array('type' => 'string', 'data' => $result);
	
	returnData($returnType, $data);
	return;
}

//http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_tag&other=tag_name|tag_description|tag_url|tag_email&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_tag ($id, $trash1, $other, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")){
		returnError('forbidden', 'string');
		return;
	}

	$data = array();
	
	if ($other['type'] == 'string') {
		$data["name"] = $other["data"];
	}
	else if ($other['type'] == 'array') {
		
		$data['name'] = $other["data"][0];
		
		if ($other["data"][1] != '') {
			$data['description'] = $other["data"][1];
		}
		else {
			$data['description'] = "";
		}
		
		if ($other["data"][1] != '') {
			$data['url'] = $other["data"][2];
		}
		else {
			$data['url'] = "";
		}
		
		if ($other["data"][1] != '') {
			$data['email'] = $other["data"][3];
		}
		else {
			$data['email'] = '';
		}
	}
	
	if (tags_create_tag ($data)) {
		returnData('string',
			array('type' => 'string', 'data' => '1'));
	}
	else {
		returnError('error_set_tag_user_profile', 'Error create tag user profile.');
	}
}


//http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_event&id=name_event&other=2|system|3|admin|2|1|10|0|comments||Pandora||critical_inst|warning_inst|unknown_inst|other||&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_event($id, $trash1, $other, $returnType) {
	global $config;

	if (!check_acl($config['id_user'], 0, "EW")){
		returnError('forbidden', 'string');
		return;
	}

	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		
		$values = array();
		
		if ($other['data'][0] != '') {
			$values['event'] = $other['data'][0];
		}
		else {
			returnError('error_parameter', 'Event text required.');
			return;
		}
		
		if ($other['data'][1] != '') {
			if (!check_acl($config['id_user'], $other['data'][1], "AR")) {
				returnError('forbidden', 'string');
				return;
			}
			$values['id_grupo'] = $other['data'][1];
		}
		else {
			returnError('error_parameter', 'Group ID required.');
			return;
		}
		$error_msg ='';
		if ($other['data'][2] != '') {
			$id_agent = $other['data'][2];
			if (is_metaconsole()) {
				// On metaconsole, connect with the node to check the permissions
				$agent_cache = db_get_row('tmetaconsole_agent', 'id_agente', $id_agent);
				if ($agent_cache === false) {
					returnError('id_not_found', 'string');
					return;
				}
				if (!metaconsole_connect(null, $agent_cache['id_tmetaconsole_setup'])) {
					returnError('error_create_event', __("Cannot connect with the agent node."));
					return;
				}
				$id_agent = $agent_cache['id_tagente'];

			}

			$values['id_agente'] = $id_agent;

			if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AR')) {
				if (is_metaconsole()) metaconsole_restore_db();
				return;
			}
			if (is_metaconsole()) metaconsole_restore_db();
		}
		else {
			if($other['data'][19] != ''){
				$values['id_agente'] = db_get_value('id_agente', 'tagente', 'nombre', $other['data'][19]);
				if(!$values['id_agente']){
					if($other['data'][20] == 1){
						$values['id_agente'] = db_process_sql_insert ('tagente', 
												array ( 'nombre'   => $other['data'][19],
														'id_grupo' => $other['data'][1],
														'alias'    => $other['data'][19] )
														);
					}
					else{
						$error_msg = 'name_not_exist';
					}
				}
			}
			else {
				$error_msg = 'none';
			}
		}

		if($error_msg != ''){
			if($error_msg == 'id_not_exist'){
				returnError('error_parameter', 'Agent ID does not exist.');
			} 
			elseif($error_msg == 'name_not_exist'){
				returnError('error_parameter', 'Agent Name does not exist.');	
			} 
			elseif($error_msg == 'none'){
				returnError('error_parameter', 'Agent ID or name required.');
			}
			return;
		}
		
		if ($other['data'][3] != '') {
			$values['status'] = $other['data'][3];
		}
		else {
			$values['status'] = 0;
		}
		
		$values['id_usuario'] = $other['data'][4];
		
		if ($other['data'][5] != '') {
			$values['event_type'] = $other['data'][5];
		}
		else {
			$values['event_type'] = "unknown";
		}
		
		if ($other['data'][6] != '') {
			$values['priority'] = $other['data'][6];
		}
		else {
			$values['priority'] = 0;
		}
		
		if ($other['data'][7] != '') {
			$values['id_agentmodule'] = $other['data'][7];
		}
		else {
			$value['id_agentmodule'] = 0;
		}
		
		if ($other['data'][8] != '') {
			$values['id_alert_am'] = $other['data'][8];
		}
		else {
			$values['id_alert_am'] = 0;
		}
		
		if ($other['data'][9] != '') {
			$values['critical_instructions'] = $other['data'][9];
		}
		else {
			$values['critical_instructions'] = '';
		}
		
		if ($other['data'][10] != '') {
			$values['warning_instructions'] = $other['data'][10];
		}
		else {
			$values['warning_instructions'] = '';
		}
		
		if ($other['data'][11] != '') {
			$values['unknown_instructions'] = $other['data'][11];
		}
		else {
			$values['unknown_instructions'] = '';
		}
		
		if ($other['data'][14] != '') {
			$values['source'] = $other['data'][14];
		}
		else {
			$values['source'] = get_product_name();
		}
		
		if ($other['data'][15] != '') {
			$values['tags'] = $other['data'][15];
		}
		else {
			$values['tags'] = "";
		}
		
		if ($other['data'][16] != '') {
			$values['custom_data'] = $other['data'][16];
		}
		else {
			$values['custom_data'] = "";
		}
		
		if ($other['data'][17] != '') {
			$values['server_id'] = $other['data'][17];
		}
		else {
			$values['server_id'] = 0;
		}
		if ($other['data'][18] != '') {
			$values['id_extra'] = $other['data'][18];
			$sql_validation = 'SELECT id_evento FROM tevento where estado=0 and id_extra ="'. $other['data'][18] .'";';
			$validation = db_get_all_rows_sql($sql_validation);
			if($validation){
				foreach ($validation as $val) {
					api_set_validate_event_by_id($val['id_evento']);
				}
			}
		}
		else {
			$values['id_extra'] = '';
		}
		$return = events_create_event(
			$values['event'], $values['id_grupo'], $values['id_agente'], 
			$values['status'], $values['id_usuario'],
			$values['event_type'], $values['priority'],
			$values['id_agentmodule'], $values['id_alert_am'], 
			$values['critical_instructions'],
			$values['warning_instructions'], 
			$values['unknown_instructions'], $values['source'],
			$values['tags'], $values['custom_data'],
			$values['server_id'], $values['id_extra']);
		
		if ($other['data'][12] != '') { //user comments
			if ($return !== false) { //event successfully created
				$user_comment = $other['data'][12];
				$res = events_comment ($return, $user_comment,
					'Added comment', defined ('METACONSOLE'),
					$config['history_db_enabled']);
				if ($other['data'][13] != '') { //owner user
					if ($res !== false) { //comment added
						$owner_user = $other['data'][13];
						events_change_owner ($return, $owner_user,
							true, defined ('METACONSOLE'),
							$config['history_db_enabled']);
					}
				}
			}
		}
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		
		returnData($returnType, $data);
		return;
	}
}

/**
 * Add event commet.
 *
 * @param $id event id.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *   http://127.0.0.1/pandora_console/include/api.php?op=set&op2=add_event_comment&id=event_id&other=string|&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_add_event_comment($id, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if(!check_acl($config['id_user'], 0, 'EW')) {
		returnError('forbidden', 'string');
		return;
	}
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$comment = io_safe_input($other['data'][0]);
		$meta = $other['data'][1];
		$history = $other['data'][2];
		
		$status = events_comment($id, $comment, 'Added comment', $meta,
			$history);
		if (is_error($status)) {
			returnError('error_add_event_comment',
				__('Error adding event comment.'));
			return;
		}
	}
	
	returnData('string', array('type' => 'string', 'data' => $status));
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=tactical_view&apipass=1234&user=admin&pass=pandora
function api_get_tactical_view($trash1, $trash2, $trash3, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$tactical_info = reporting_get_group_stats();
	
	switch ($returnType) {
		case 'string':
			$i = 0;
			foreach ($tactical_info as $key => $data) {
				if ($i == 0)
					$result = $key . ': ' . $data . '<br>';
				else
					$result .= $key . ': ' . $data . '<br>'; 
				
				$i++;
			}
			
			$data = array('type' => 'string', 'data' => $result);
			break;
		case 'csv':
			$data = array('type' => 'array', 'data' => array($tactical_info));
			break;
	}
	
	returnData($returnType, $data);
	return;
	
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_data&other=1348562410|1348648810|0|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_data ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$interval_length = $params['data'][2];
	$filter = json_decode (base64_decode ($params['data'][3]), true);
	$aggregate = $params['data'][4];
	$max = $params['data'][5];
	$unit = $params['data'][6];
	$address_resolution = $params['data'][7];
	
	// Get netflow data
	$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $aggregate, $max, $unit, '', $address_resolution);
	
	returnData('json', $data);
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_stats&other=1348562410|1348648810|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_stats ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$filter = json_decode (base64_decode ($params['data'][2]), true);
	$aggregate = $params['data'][3];
	$max = $params['data'][4];
	$unit = $params['data'][5];
	$address_resolution = $params['data'][6];
	
	// Get netflow data
	$data = netflow_get_stats ($start_date, $end_date, $filter, $aggregate, $max, $unit, '', $address_resolution);
	
	returnData('json', $data);
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_summary&other=1348562410|1348648810|_base64_encode(json_encode($filter))&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_summary ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$filter = json_decode (base64_decode ($params['data'][2]), true);
	
	// Get netflow data
	$data = netflow_get_summary ($start_date, $end_date, $filter);
	returnData('json', $data);
	return;
}

//http://localhost/pandora_console/include/api.php?op=set&op2=validate_event_by_id&id=23&apipass=1234&user=admin&pass=pandora
function api_set_validate_event_by_id ($id, $trash1, $trash2, $returnType) {
	global $config;
	$data['type'] = 'string';
	$check_id = db_get_value('id_evento', 'tevento', 'id_evento', $id);
	
	if ($check_id) { //event exists
		$status = db_get_value('estado', 'tevento', 'id_evento', $id);
		if ($status == 1) { //event already validated
			$data['data'] = "Event already validated";
		}
		else {
			$ack_utimestamp = time();
			
			events_comment($id, '', "Change status to validated");
			
			$values = array(
				'ack_utimestamp' => $ack_utimestamp,
				'estado' => 1
				);
			
			$result = db_process_sql_update('tevento', $values, array('id_evento' => $id));
			
			if ($result === false) {
				$data['data'] = "Error validating event";
			}
			else {
				$data['data'] = "Event validate";
			}
		}
		
	}
	else {
		$data['data'] = "Event not exists";
	}
	
	returnData($returnType, $data);
	return;
}

/**
 * 
 * @param $trash1
 * @param $trash2
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $returnType
 *
 */
//  http://localhost/pandora_console/include/api.php?op=get&op2=pandora_servers&return_type=csv&apipass=1234&user=admin&pass=pandora
function api_get_pandora_servers($trash1, $trash2, $other, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "AW")) {
		returnError("forbidden", $returnType);
		return;
	}

	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$servers = servers_get_info ();
	
	foreach ($servers as $server) {
		$dd = array (
			'name' => $server["name"],
			'status' => $server["status"],
			'type' => $server["type"],
			'master' => $server["master"],
			'modules' => $server["modules"],
			'modules_total' => $server["modules_total"],
			'lag' => $server["lag"],
			'module_lag' => $server["module_lag"],
			'threads' => $server["threads"],
			'queued_modules' => $server["queued_modules"],
			'keepalive' => $server['keepalive'],
			'id_server' => $server['id_server']
		);
		
		// servers_get_info() returns "<a http:....>servername</a>" for recon server's name.
		// i don't know why and the following line is a temprary workaround... 
		$dd["name"] = preg_replace( '/<[^>]*>/', "", $dd["name"]);
		
		switch ($dd['type']) {
			case "snmp":
			case "event":
				$dd['modules'] = '';
				$dd['modules_total'] = '';
				$dd['lag'] = '';
				$dd['module_lag'] = '';
				break;
			case "export":
				$dd['lag'] = '';
				$dd['module_lag'] = '';
				break;
			default:
				break;
		}
		
		$returnVar[] = $dd;
	}
	
	$data = array('type' => 'array', 'data' => $returnVar);
	
	returnData($returnType, $data, $separator);
	return;
}

/**
 * Enable/Disable agent given an id
 * 
 * @param string $id String Agent ID
 * @param $thrash2 not used.
 * @param array $other it's array, $other as param is <enable/disable value> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (Enable agent 'example_id') 
 * 
 *  api.php?op=set&op2=enable_disable_agent&id=example_id&other=0&other_mode=url_encode_separator_|
 *  
 * 	example 2 (Disable agent 'example_id')
 * 
 *  api.php?op=set&op2=enable_disable_agent&id=example_id16&other=1&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_enable_disable_agent ($id, $thrash2, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_enable_disable_agent',
			__('Error enable/disable agent. Id_agent cannot be left blank.'));
		return;
	}

	if (!util_api_check_agent_and_print_error($id, 'string', "AD")) {
		return;
	}
	
	if ($other['data'][0] != "0" and $other['data'][0] != "1") {
		returnError('error_enable_disable_agent',
			__('Error enable/disable agent. Enable/disable value cannot be left blank.'));
		return;
	}
	
	if (agents_get_name($id) == false) {
		returnError('error_enable_disable_agent',
			__('Error enable/disable agent. The agent doesn\'t exist.'));
		return;
	}
		
	$disabled = ( $other['data'][0] ? 0 : 1 );

	enterprise_hook(
		'config_agents_update_config_token',
		array($id, 'standby', $disabled ? "1" : "0")
	);
	
	$result = db_process_sql_update('tagente',
		array('disabled' => $disabled), array('id_agente' => $id));
	
	if (is_error($result)) {
		// TODO: Improve the error returning more info
		returnError('error_enable_disable_agent', __('Error in agent enabling/disabling.'));
	}
	else {
		if ($disabled == 0) {
			returnData('string',
				array('type' => 'string',
					'data' => __('Enabled agent.')));
		}
		else {
			returnData('string',
				array('type' => 'string',
					'data' => __('Disabled agent.')));
		}
	}
}

/**
 * Validate alert from Pager Duty service. This call will be setted in PagerDuty's service as a Webhook to 
 * validate the alerts of Pandora FMS previously linked to PagertDuty when its were validated from PagerDuty.
 * 
 * This call only have a parameter: id=alert
 * 
 * Call example:
 * 	http://127.0.0.1/pandora_console/include/api.php?op=set&op2=pagerduty_webhook&apipass=1234&user=admin&pass=pandora&id=alert
 * 
 * TODO: Add support to events.
 * 
 */
 
function api_set_pagerduty_webhook($type, $matchup_path, $tresh2, $return_type) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "PM")) {
		returnError('forbidden', 'string');
		return;
	}

	$pagerduty_data = json_decode(file_get_contents('php://input'), true);
	
	foreach($pagerduty_data['messages'] as $pm) {
		$incident = $pm['data']['incident'];
		$incident_type = $pm['type'];
		// incident.acknowledge
		// incident.resolve
		// incident.trigger

		switch($type) {
			case 'alert':
				// Get all the alerts that the user can see
				$id_groups = array_keys(users_get_groups($config["id_user"], 'AR', false));
				$alerts = get_group_alerts($id_groups);

				// When an alert is resolved, the Pandoras alert will be validated
				if ($incident_type != 'incident.resolve') {
					break;
				}

				$alert_id = 0;
				foreach($alerts as $al) {
	    				$key = file_get_contents($matchup_path . '/.pandora_pagerduty_id_' . $al['id']);
					if ($key == $incident['incident_key']) {
						$alert_id = $al['id'];
						break;
					}
				}

				if ($alert_id != 0) {
					alerts_validate_alert_agent_module($alert_id);
				}
				break;
			case 'event':
				break;
		}
	}
}

/**
 * Get special days, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *  api.php?op=get&op2=special_days&other=,;
 *
 */
function api_get_special_days($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'csv');
		return;
	}

	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	
	$special_days = @db_get_all_rows_filter ('talert_special_days', $filter);
	
	if ($special_days !== false) {
		$data['type'] = 'array';
		$data['data'] = $special_days;
	}
	
	if (!$special_days) {
		returnError('error_get_special_days', __('Error getting special_days.'));
	}
	else {
		returnData('csv', $data, $separator);
	}
}

/**
 * Create a special day. And return the id if new special day.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <special_day>;<same_day>;<description>;<id_group>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=create_special_day&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 *
 */
function api_set_create_special_day($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}

	$special_day = $other['data'][0];
	$same_day = $other['data'][1];
	$description = $other['data'][2];
	$idGroup = $other['data'][3];
	
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'date', $special_day);
	
	if ($check_id_special_day) {
		returnError('error_create_special_day', __('Error creating special day. Specified day already exists.'));
		return;
	}
	
	if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $special_day)) {
		returnError('error_create_special_day', __('Error creating special day. Invalid date format.'));
		return;
	}

	if (!isset($idGroup) || $idGroup == '') {
		returnError('error_create_special_day', __('Error creating special day. Group id cannot be left blank.'));
		return;
	}
	else {
		$group = groups_get_group_by_id($idGroup);

		if ($group == false) {
			returnError('error_create_special_day', __('Error creating special day. Id_group doesn\'t exist.'));
			return;
		}

		if (!check_acl($config['id_user'], $idGroup, "LM")) {
			returnError('forbidden', 'string');
			return;
		}
	}
	
	$values = array(
		'description' => $other['data'][2],
		'id_group' => $other['data'][3],
	);
	
	$idSpecialDay = alerts_create_alert_special_day($special_day, $same_day, $values);
	
	if (is_error($idSpecialDay)) {
		returnError('error_create_special_day', __('Error in creation special day.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idSpecialDay));
	}
}

/**
 * Create a service and return service id.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <description>;<id_group>;<critical>;
 * <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 * <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;<quiet>;
 * <cascade_protection>;<evaluate_sla>;
 * in this order and separator char (after text ; ) and separator
 * (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_service&return_type=json
 * &other=test1%7CDescripcion de prueba%7C12%7C1%7C0.5%7C1&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_create_service($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) return;

	$name = $other['data'][0];
	$description = $other['data'][1];
	$id_group = $other['data'][2];
	$critical = $other['data'][3];
	$warning = $other['data'][4];
	$mode = 0;
	$id_agent = $other['data'][5];
	$sla_interval = $other['data'][6];
	$sla_limit = $other['data'][7];
	$id_warning_module_template = $other['data'][8];
	$id_critical_module_template = $other['data'][9];
	$id_unknown_module_template = 0;
	$id_critical_module_sla = $other['data'][10];
	$quiet = $other['data'][11];
	$cascade_protection = $other['data'][12];
	$evaluate_sla = $other['data'][13];

	if(empty($name)){
		returnError('error_create_service', __('Error in creation service. No name'));
		return;
	}
	if(empty($id_group)){
		// By default applications
		$id_group = 12;
	}
	if (!check_acl($config['id_user'], $id_group, "AR")) {
		returnError('forbidden', 'string');
		return;
	}
	if(empty($critical)){
		$critical = 1;
	}
	if(empty($warning)){
		$warning = 0.5;
	}
	if(empty($id_agent)){
		returnError('error_create_service', __('Error in creation service. No agent id'));
		return;
	} else {
		if (!util_api_check_agent_and_print_error($id_agent, 'string', "AR")) {
			return;
		}
	}
	if(empty($sla_interval)){
		// By default one month
		$sla_interval = 2592000;
	}
	if(empty($sla_limit)){
		$sla_limit = 95;
	}
	if(empty($id_warning_module_template)){
		$id_warning_module_template = 0;
	}
	if(empty($id_critical_module_template)){
		$id_critical_module_template = 0;
	}
	if(empty($id_critical_module_sla)){
		$id_critical_module_sla = 0;
	}
	if(empty($quiet)){
		$quiet = 0;
	}
	if(empty($cascade_protection)){
		$cascade_protection = 0;
	}
	if(empty($evaluate_sla)){
		$evaluate_sla = 0;
	}

	$result = services_create_service (
		$name, $description, $id_group,
		$critical, $warning, SECONDS_5MINUTES,
		$mode, $id_agent, $sla_interval, $sla_limit,
		$id_warning_module_template, $id_critical_module_template,
		$id_unknown_module_template, $id_critical_module_sla,
		$quiet, $cascade_protection, $evaluate_sla
	);

	if($result){
		returnData('string', array('type' => 'string', 'data' => $result));
	} else {
		returnError('error_create_service', __('Error in creation service'));
	}
}

/**
 * Update a service.
 *
 * @param $thrash1 service id.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <name>;<description>;<id_group>;<critical>;
 * <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 * <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;<quiet>;
 * <cascade_protection>;<evaluate_sla>;
 * in this order and separator char (after text ; ) and separator
 * (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://172.17.0.1/pandora_console/include/api.php?op=set&op2=update_service&return_type=json
 * &id=4&other=test2%7CDescripcion%7C%7C%7C0.6%7C&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 *
 */
function api_set_update_service($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) return;

	$id_service = $thrash1;
	if(empty($id_service)){
		returnError('error_update_service', __('Error in update service. No service id'));
		return;
	}

	$service = db_get_row('tservice',
		'id', $id_service);

	if (!check_acl($config['id_user'],$service['id_group'], "AD")) {
		returnError('forbidden', 'string');
		return;
	}

	$name = $other['data'][0];
	if(empty($name)){
		$name = $service['name'];
	}
	$description = $other['data'][1];
	if(empty($description)){
		$description = $service['description'];
	}
	$id_group = $other['data'][2];
	if(empty($id_group)){
		$id_group = $service['id_group'];
	} else {
		if (!check_acl($config['id_user'], $id_group, "AD")) {
			returnError('forbidden', 'string');
			return;
		}
	}
	$critical = $other['data'][3];
	if(empty($critical)){
		$critical = $service['critical'];
	}
	$warning = $other['data'][4];
	if(empty($warning)){
		$warning = $service['warning'];
	}

	$mode = 0;

	$id_agent = $other['data'][5];
	if(empty($id_agent)){
		$id_agent = $service['id_agent_module'];
	} else {
		if (!util_api_check_agent_and_print_error($id_agent, 'string', "AR")) {
			return;
		}
	}
	$sla_interval = $other['data'][6];
	if(empty($sla_interval)){
		$sla_interval = $service['sla_interval'];
	}
	$sla_limit = $other['data'][7];
	if(empty($sla_limit)){
		$sla_limit = $service['sla_limit'];
	}
	$id_warning_module_template = $other['data'][8];
	if(empty($id_warning_module_template)){
		$id_warning_module_template = $service['id_template_alert_warning'];
	}
	$id_critical_module_template = $other['data'][9];
	if(empty($id_critical_module_template)){
		$id_critical_module_template = $service['id_template_alert_critical'];
	}

	$id_unknown_module_template = 0;

	$id_critical_module_sla = $other['data'][10];
	if(empty($id_critical_module_sla)){
		$id_critical_module_sla = $service['id_template_alert_critical_sla'];
	}

	$quiet = $other['data'][11];
	if(empty($quiet)){
		$quiet = $service['quiet'];
	}

	$cascade_protection = $other['data'][12];
	if(empty($cascade_protection)){
		$cascade_protection = $service['cascade_protection'];
	}

	$evaluate_sla = $other['data'][13];
	if(empty($evaluate_sla)){
		$evaluate_sla = $service['evaluate_sla'];
	}

	$result = services_update_service (
		$id_service, $name,$description, $id_group,
		$critical, $warning, SECONDS_5MINUTES, $mode,
		$id_agent, $sla_interval, $sla_limit,
		$id_warning_module_template,
		$id_critical_module_template,
		$id_unknown_module_template,
		$id_critical_module_sla,
		$quiet, $cascade_protection,
		$evaluate_sla
	);

	if($result){
		returnData('string', array('type' => 'string', 'data' => $result));
	} else {
		returnError('error_update_service', __('Error in update service'));
	}
}

/**
 * Add elements to service.
 *
 * @param $thrash1 service id.
 * @param $thrash2 Don't use.
 * @param array $other it's a json, $other as param is <description>;<id_group>;<critical>;
 * <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 * <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;
 * in this order and separator char (after text ; ) and separator
 * (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://172.17.0.1/pandora_console/include/api.php?op=set&op2=add_element_service&return_type=json&id=1
 * &other=W3sidHlwZSI6ImFnZW50IiwiaWQiOjIsImRlc2NyaXB0aW9uIjoiamlqaWppIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH0seyJ0eXBlIjoibW9kdWxlIiwiaWQiOjEsImRlc2NyaXB0aW9uIjoiSG9sYSBxdWUgdGFsIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH0seyJ0eXBlIjoic2VydmljZSIsImlkIjozLCJkZXNjcmlwdGlvbiI6ImplamVqZWplIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH1d
 * &other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 *
 */
function api_set_add_element_service($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (is_metaconsole()) return;

	$id = $thrash1;

	if(empty($id)){
		returnError('error_add_service_element', __('Error adding elements to service. No service id'));
		return;
	}

	$service_group = db_get_value('id_group', 'tservice', 'id', $id);

	if (!check_acl($config['id_user'], $service_group, "AD")) {
		returnError('forbidden', 'string');
		return;
	}

	$array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
	if(!empty($array_json)){
		$results = false;
		foreach ($array_json as $key => $element) {
			if($element['id'] == 0){
				continue;
			}
			switch ($element['type']) {
				case 'agent':
					$id_agente_modulo = 0;
					$id_service_child = 0;
					$agent_id = $element['id'];
					if (!agents_check_access_agent($agent_id, "AR")) {
						continue;
					}
					break;

				case 'module':
					$agent_id = 0;
					$id_service_child = 0;
					$id_agente_modulo = $element['id'];
					if (!agents_check_access_agent(modules_get_agentmodule_agent($id_agente_modulo), "AR")) {
						continue;
					}
					break;

				case 'service':
					$agent_id = 0;
					$id_agente_modulo = 0;
					$id_service_child = $element['id'];
					$service_group = db_get_value(
						'id_group', 'tservice', 'id', $id_service_child
					);
					if ($service_group === false || !check_acl($config['id_user'], $service_group, "AD")) {
						continue;
					}
					break;
			}

			$values = array(
				'id_agente_modulo' => $id_agente_modulo,
				'description' => $element['description'],
				'id_service' => $id,
				'weight_critical' => $element['weight_critical'],
				'weight_warning' => $element['weight_warning'],
				'weight_unknown' => $element['weight_unknown'],
				'weight_ok' => $element['weight_ok'],
				'id_agent' => $agent_id,
				'id_service_child' => $id_service_child,
				'id_server_meta' => 0);

			$result = db_process_sql_insert('tservice_element',$values);
			if($result && !$results){
				$results = $result;
			}
		}
	}

	if($results){
		returnData('string', array('type' => 'string', 'data' => 1));
	} else {
		returnError('error_add_service_element', __('Error adding elements to service'));
	}

}
/**
 * Update a special day. And return a message with the result of the operation.
 *
 * @param string $id Id of the special day to update.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <special_day>;<same_day>;<description>;<id_group>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=update_special_day&id=1&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 *
 */
function api_set_update_special_day($id_special_day, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}

	$special_day = $other['data'][0];
	$same_day = $other['data'][1];
	$description = $other['data'][2];
	$idGroup = $other['data'][3];
	
	if ($id_special_day == "") {
		returnError('error_update_special_day', __('Error updating special day. Id cannot be left blank.'));
		return;
		}
	
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'id', $id_special_day);
	
	if (!$check_id_special_day) {
		returnError('error_update_special_day', __('Error updating special day. Id doesn\'t exist.'));
		return;
	}
	
	if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $special_day)) {
		returnError('error_update_special_day', __('Error updating special day. Invalid date format.'));
		return;	
	}
	
	$return = db_process_sql_update('talert_special_days',
		array(
			'date' => $special_day,
			'same_day' => $same_day,
			'description' => $description,
			'id_group' => $idGroup),
		array('id' => $id_special_day));
	
	returnData('string',
		array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Delete a special day. And return a message with the result of the operation.
 *
 * @param string $id Id of the special day to delete.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 *
 * example:
 *  api.php?op=set&op2=delete_special_day&id=1
 *
 */
function api_set_delete_special_day($id_special_day, $thrash2, $thrash3, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (!check_acl($config['id_user'], 0, "LM")) {
		returnError('forbidden', 'string');
		return;
	}

	if ($id_special_day == "") {
		returnError('error_update_special_day', __('Error deleting special day. Id cannot be left blank.'));
		return;
	}
	
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'id', $id_special_day);
	
	if (!$check_id_special_day) {
		returnError('error_delete_special_day', __('Error deleting special day. Id doesn\'t exist.'));
		return;
	}
	
	$return = alerts_delete_alert_special_day ($id_special_day);
	
	if (is_error($return)) {
		returnError('error_delete_special_day', __('Error in deletion special day.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $return));
	}
}

/**
 * Get a module graph image encoded with base64.
 * The value returned by this function will be always a string.
 *
 * @param int $id Id of the module.
 * @param $thrash2 Don't use.
 * @param array $other Array array('type' => 'string', 'data' => '<Graph seconds>').
 * @param $thrash4 Don't use.
 *
 * example:
 *  http://localhost/pandora_console/include/
 *		api.php?op=get&op2=module_graph&id=5&other=40000%7C1&other_mode=url_encode_separator_%7C&apipass=1234
 *		&api=1&user=admin&pass=pandora
 *
 */
function api_get_module_graph($id_module, $thrash2, $other, $thrash4) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	if (is_nan($id_module) || $id_module <= 0) {
		returnError('error_module_graph', __(''));
		return;
	}

	if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string')) return;

	$graph_seconds =
		(!empty($other) && isset($other['data'][0]))
		?
		$other['data'][0]
		:
		SECONDS_1HOUR; // 1 hour by default

	$graph_threshold =
		(!empty($other) && isset($other['data'][2]) && $other['data'][2])
		?
		$other['data'][2]
		:
		0;

	if (is_nan($graph_seconds) || $graph_seconds <= 0) {
		// returnError('error_module_graph', __(''));
		return;
	}

	$params =array(
		'agent_module_id'     => $id_module,
		'period'              => $graph_seconds,
		'show_events'         => false,
		'width'               => $width,
		'height'              => $height,
		'show_alerts'         => false,
		'date'                => time(),
		'unit'                => '',
		'baseline'            => 0,
		'return_data'         => 0,
		'show_title'          => true,
		'only_image'          => true,
		'homeurl'             => ui_get_full_url(false) . '/',
		'compare'             => false,
		'show_unknown'        => true,
		'backgroundColor'     => 'white',
		'percentil'           => null,
		'type_graph'          => $config['type_module_charts'],
		'fullscale'           => false,
		'return_img_base_64'  => true,
		'image_treshold'      => $graph_threshold
	);

	$graph_html = grafico_modulo_sparse($params);

	if($other['data'][1]){
		header('Content-type: text/html');
		returnData('string', array('type' => 'string', 'data' => '<img src="data:image/jpeg;base64,' . $graph_html . '">'));
	} else {
		returnData('string', array('type' => 'string', 'data' => $graph_html));
	}
}

function api_set_metaconsole_synch($keys) {
	global $config;

	if (defined('METACONSOLE')) {
		if (!check_acl($config['id_user'], 0, "PM")) {
			returnError('forbidden', 'string');
			return;
		}
		$data['keys'] = array('customer_key'=>$keys);
		foreach ($data['keys'] as $key => $value) {
			db_process_sql_update(
				'tupdate_settings',
				array(db_escape_key_identifier('value') => $value),
				array(db_escape_key_identifier('key') => $key));
		}

		// Validate update the license in nodes:
		enterprise_include_once('include/functions_metaconsole.php');
		$array_metaconsole_update = metaconsole_update_all_nodes_license();
		if ($array_metaconsole_update[0] === 0) {
			ui_print_success_message(__('Metaconsole and all nodes license updated'));
		}
		else {
			ui_print_error_message(__('Metaconsole license updated but %d of %d node synchronization failed', $array_metaconsole_update[0], $array_metaconsole_update[1]));
		}
	}
	else{
		echo __('This function is only for metaconsole');
	}
}

function api_set_new_cluster($thrash1, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$name = $other['data'][0];
	$cluster_type = $other['data'][1];
	$description = $other['data'][2];
	$idGroup = $other['data'][3];

	
	if(!check_acl($config['id_user'], $idGroup, "AW")) {
		returnError('forbidden', 'string');
		return;
	}
	
	$name_exist = db_process_sql('select count(name) as already_exist from tcluster as already_exist where name = "'.$name.'"');	

	if($name_exist[0]['already_exist'] > 0){
		returnError('error_set_new_cluster', __('A cluster with this name already exists.'));
		return false;
	}

	$server_name = db_process_sql('select name from tserver where server_type=5 limit 1');	
	
	$server_name_agent = $server_name[0]['name'];
		
	$values_agent = array(
		'nombre' => $name,
		'alias' => $name,
		'comentarios' => $description,
		'id_grupo' => $idGroup,
		'id_os' => 100,
		'server_name' => $server_name_agent,
		'modo' => 1
	);

	if (!isset($name)) { // avoid warnings
		$name = "";
	}
	
	if (trim($name) != "") {
		$id_agent = agents_create_agent($values_agent['nombre'],$values_agent['id_grupo'],300,'',$values_agent);

		if ($id_agent !== false) {	
			// Create cluster
			$values_cluster = array(
				'name' => $name,
				'cluster_type' => $cluster_type,
				'description' => $description,
				'group' => $idGroup,
				'id_agent' => $id_agent
			);
			
			$id_cluster = db_process_sql_insert('tcluster', $values_cluster);

			if ($id_cluster === false) {
				// failed to create cluster, rollback previously created agent
				agents_delete_agent($id_agent, true);
			}
			
			$values_module = array(
				'nombre' => io_safe_input('Cluster status'),
				'id_modulo' => 5,
				'prediction_module' => 5,
				'id_agente' => $id_agent,
				'custom_integer_1' => $id_cluster,
				'id_tipo_modulo' => 1,
				'descripcion' => io_safe_input('Cluster status information module'),
				'min_warning' => 1,
				'min_critical' => 2
			);
			
			$id_module = modules_create_agent_module($id_agent, $values_module['nombre'], $values_module, true);
			if ($id_module === false) {
				db_pandora_audit("Report management", "Failed to create cluster status module in cluster $name (#$id_agent)");
			}
		}
		
		if ($id_cluster !== false)
			db_pandora_audit("Report management", "Created cluster $name (#$id_cluster)");
		else
			db_pandora_audit("Report management", "Failed to create cluster $name");

		if ($id_agent !== false)
			db_pandora_audit("Report management", "Created new cluster agent $name (#$id_agent)");
		else
			db_pandora_audit("Report management", "Failed to create cluster agent $name");
		
		if ($id_cluster !== false)
			returnData('string',
				array('type' => 'string', 'data' => (int)$id_cluster));
		else
			returnError('error_set_new_cluster', __('Failed to create cluster.'));
	} else {
		returnError('error_set_new_cluster', __('Agent name cannot be empty.'));
		return;
	}

	return;
}
	
function api_set_add_cluster_agent($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	$array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
	if(!empty($array_json)){
		foreach ($array_json as $key => $element) {
			$check_cluster_group = clusters_get_group ($element['id']);
			if((!check_acl($config['id_user'], $check_cluster_group, "AW"))
			|| (!agents_check_access_agent($element['id_agent'], "AW"))) {
				continue;
			}
			$tcluster_agent = db_process_sql('insert into tcluster_agent values ('.$element["id"].','.$element["id_agent"].')');
		}
	}
		
	if($tcluster_agent !== false){
		returnData('string', array('type' => 'string', 'data' => 1));
	} else {
		returnError('error_add_cluster_element', __('Error adding elements to cluster'));
	}
	
}

function api_set_add_cluster_item($thrash1, $thrash2, $other, $thrash3) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	$array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
	if (is_array($array_json)) {
		foreach ($array_json as $key => $element) {
			$cluster_group = clusters_get_group ($element['id']);
			if(!check_acl($config['id_user'], $cluster_group, "AW")){
				continue;
			}

			if($element["type"] == "AA"){
				$tcluster_module = db_process_sql_insert('tcluster_item',array('name'=>io_safe_input($element["name"]),'id_cluster'=>$element["id_cluster"],'critical_limit'=>$element["critical_limit"],'warning_limit'=>$element["warning_limit"]));
				
				$id_agent = db_process_sql('select id_agent from tcluster where id = '.$element["id_cluster"]);
				
				$id_parent_modulo = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = ' . $id_agent[0]['id_agent']
					. ' and nombre = "' . io_safe_input("Cluster status") . '"');
				
				$get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical,module_interval from tagente_modulo where nombre = "'.io_safe_input($element["name"]).'" limit 1');
				
				$get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
				$get_module_description_value = $get_module_type[0]['descripcion'];
				$get_module_warning_value = $get_module_type[0]['min_warning'];
				$get_module_critical_value = $get_module_type[0]['min_critical'];
				$get_module_interval_value = $get_module_type[0]['module_interval'];
				
				$values_module = array(
					'nombre' => io_safe_input($element["name"]),
					'id_modulo' => 0,
					'prediction_module' => 6,
					'id_agente' => $id_agent[0]['id_agent'],
					'parent_module_id' => $id_parent_modulo[0]['id_agente_modulo'],
					'custom_integer_1' =>$element["id_cluster"],
					'custom_integer_2' =>$tcluster_module,
					'id_tipo_modulo' =>1,
					'descripcion' => $get_module_description_value,
					'min_warning' => $element["warning_limit"],
					'min_critical' => $element["critical_limit"],
					'tcp_port' => 1,
					'module_interval' => $get_module_interval_value
				);
				
				$id_module = modules_create_agent_module($values_module['id_agente'],$values_module['nombre'],$values_module, true);
				
				$launch_cluster = db_process_sql('update tagente_modulo set flag = 1 where custom_integer_1 = '.$element["id_cluster"]
					. ' and nombre = "' . io_safe_input("Cluster status") . '"');
				
				if ($tcluster_module !== false){	
					db_pandora_audit("Report management", "Module #".$element["name"]." assigned to cluster #".$element["id_cluster"]);
				}
				else{
					db_pandora_audit("Report management", "Failed to assign AA item module to cluster " . $element["name"]);
				}
				
			}
			elseif ($element["type"] == "AP") {
				$id_agent = db_process_sql('select id_agent from tcluster where id = '.$element["id_cluster"]);
				
				$id_parent_modulo = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent[0]['id_agent']
					. ' and nombre = "' . io_safe_input("Cluster status") . '"');
				
				$tcluster_balanced_module = db_process_sql_insert('tcluster_item',array('name'=>$element["name"],'id_cluster'=>$element["id_cluster"],'item_type'=>"AP",'is_critical'=>$element["is_critical"]));
				
				$get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical,module_interval from tagente_modulo where nombre = "'.io_safe_input($element["name"]).'" limit 1');
				
				$get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
				$get_module_description_value = $get_module_type[0]['descripcion'];
				$get_module_warning_value = $get_module_type[0]['min_warning'];
				$get_module_critical_value = $get_module_type[0]['min_critical'];
				$get_module_interval_value = $get_module_type[0]['module_interval'];
				$get_module_type_nombre = db_process_sql('select nombre from ttipo_modulo where id_tipo = '.$get_module_type_value);
				$get_module_type_nombre_value = $get_module_type_nombre[0]['nombre'];
				
				
				if(strpos($get_module_type_nombre_value,'inc') != false){
					$get_module_type_value_normal = 4;
				}
				elseif (strpos($get_module_type_nombre_value,'proc') != false) {
					$get_module_type_value_normal = 2;
				}
				elseif (strpos($get_module_type_nombre_value,'data') != false) {
					$get_module_type_value_normal = 1;
				}
				elseif (strpos($get_module_type_nombre_value,'string') != false) {
					$get_module_type_value_normal = 3;
				}
				else{
					$get_module_type_value_normal = 1;
				}
				
				$values_module = array(
					'nombre' => $element["name"],
					'id_modulo' => 5,
					'prediction_module' => 7,
					'id_agente' => $id_agent[0]['id_agent'],
					'parent_module_id' => $id_parent_modulo[0]['id_agente_modulo'],
					'custom_integer_1' => $element["id_cluster"],
					'custom_integer_2' => $tcluster_balanced_module,
					'id_tipo_modulo' => $get_module_type_value_normal,
					'descripcion' => $get_module_description_value,
					'min_warning' => $get_module_warning_value,
					'min_critical' => $get_module_critical_value,
					'tcp_port' => $element["is_critical"],
					'module_interval' => $get_module_interval_value
				);
				
				$id_module = modules_create_agent_module($values_module['id_agente'],$values_module['nombre'],$values_module, true);
				
				$launch_cluster = db_process_sql('update tagente_modulo set flag = 1 where custom_integer_1 = '.$element["id_cluster"]
					. ' and nombre = "' . io_safe_input("Cluster status") . '"');
										
				if ($tcluster_balanced_module !== false){	
					db_pandora_audit("Report management", "Module #".$element["name"]." assigned to cluster #".$element["id_cluster"]);
				}
				else{
					db_pandora_audit("Report management", "Fail try to assign module to cluster");
				}
			}
		}
	}
	else {
		$id_module = false;
	}

	if($id_module !== false){
		returnData('string', array('type' => 'string', 'data' => 1));
	} else {
		returnError('error_add_cluster_element', __('Error adding elements to cluster'));
	}
	
}


function api_set_delete_cluster($id, $thrash1, $thrast2, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	$cluster_group = clusters_get_group($id);
	if(!check_acl($config['id_user'], $cluster_group, "AD")){
		returnError('error_set_delete_cluster', __('The user cannot access to the cluster'));
		return;
	}

	$temp_id_cluster = db_process_sql('select id_agent from tcluster where id ='.$id);
	
	$tcluster_modules_delete_get = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_1 = '.$id);
			
	foreach ($tcluster_modules_delete_get as $key => $value) {
		$tcluster_modules_delete_get_values[] = $value['id_agente_modulo'];
	}
	
	$tcluster_modules_delete = modules_delete_agent_module($tcluster_modules_delete_get_values);
	$tcluster_items_delete = db_process_sql('delete from tcluster_item where id_cluster = '.$id);
	$tcluster_agents_delete = db_process_sql('delete from tcluster_agent where id_cluster = '.$id);
	$tcluster_delete = db_process_sql('delete from tcluster where id = '.$id);
	$tcluster_agent_delete = agents_delete_agent($temp_id_cluster[0]['id_agent']);
	
	if (($tcluster_modules_delete + $tcluster_items_delete + $tcluster_agents_delete + $tcluster_delete + $tcluster_agent_delete) == 0)
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Successfully deleted')));
}

function api_set_delete_cluster_agents($thrash1, $thrast2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	$id_agent = $other['data'][0];
	$id_cluster = $other['data'][1];

	$target_agents = json_decode(base64_decode(io_safe_output($other['data'][0])), true);

	$cluster_group = clusters_get_group($id_agent);
	if (!users_is_admin($config['id_user'])) {
		if (!$cluster_group
		|| (!check_acl($config['id_user'], $cluster_group, "AW"))
		|| (!agents_check_access_agent($id_agent, "AW"))) {
			returnError('error_set_delete_cluster_agent', __('The user cannot access to the cluster'));
			return;
		}
	}

	$n_agents_deleted = 0;
	$n_agents = 0;

	if (is_array($target_agents)) {
		$target_clusters = array();
		foreach ($target_agents as $data) {
			$n_agents++;
			if (!isset($target_clusters[$data["id"]])) {
				$target_clusters[$data["id"]] = array();
			}
			array_push($target_clusters[$data["id"]], $data["id_agent"]);
		}

		foreach ($target_clusters as $id_cluster => $id_agent_array) {
			$rs = cluster_delete_agents($id_cluster, $id_agent_array);
			if ($rs !== false){
				$n_agents_deleted += $rs;
			}
		}
	}
		
	if ($n_agents > $n_agents_deleted)
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => $n_agents_deleted));

}


function api_set_delete_cluster_item($id, $thrash1, $thrast2, $thrast3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}

	$cluster_group = clusters_get_group($id);
	if(!check_acl($config['id_user'], $cluster_group, "AD")){
		returnError('error_set_delete_cluster_item', __('The user cannot access to the cluster'));
		return;
	}

	$delete_module_aa_get = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$id);
	$delete_module_aa_get_result = modules_delete_agent_module($delete_module_aa_get[0]['id_agente_modulo']);
	$delete_item = db_process_sql('delete from tcluster_item where id = '.$id);
			
	if (!$delete_item)
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Correct Delete')));

}

function api_set_apply_module_template($id_template, $id_agent, $thrash3, $thrash4) {
	global $config;

	if (isset ($id_template)) {

		if (!util_api_check_agent_and_print_error($id_agent, 'string', "AW")) return;
		// Take agent data
		$row = db_get_row ("tagente", "id_agente", $id_agent);
		
		$intervalo = $row["intervalo"];
		$nombre_agente = $row["nombre"];
		$direccion_agente =$row["direccion"];
		$ultima_act = $row["ultimo_contacto"];
		$ultima_act_remota =$row["ultimo_contacto_remoto"];
		$comentarios = $row["comentarios"];
		$id_grupo = $row["id_grupo"];
		$id_os= $row["id_os"];
		$os_version = $row["os_version"];
		$agent_version = $row["agent_version"];
		$disabled= $row["disabled"];

		$id_np = $id_template;
		$name_template = db_get_value ('name', 'tnetwork_profile', 'id_np', $id_np);
		$npc = db_get_all_rows_field_filter ("tnetwork_profile_component", "id_np", $id_np);
		
		if ($npc === false) {
			$npc = array ();
		}
		
		$success_count = $error_count = 0;
		$modules_already_added = array();
		
		foreach ($npc as $row) {
			$nc = db_get_all_rows_field_filter ("tnetwork_component", "id_nc", $row["id_nc"]);

			if ($nc === false) {
				$nc = array ();
			}
			foreach ($nc as $row2) {
				// Insert each module from tnetwork_component into agent
				$values = array(
					'id_agente' => $id_agent,
					'id_tipo_modulo' => $row2["type"],
					'descripcion' => __('Created by template ').$name_template. ' . '.$row2["description"],
					'max' => $row2["max"],
					'min' => $row2["min"],
					'module_interval' => $row2["module_interval"],
					'tcp_port' => $row2["tcp_port"],
					'tcp_send' => $row2["tcp_send"],
					'tcp_rcv' => $row2["tcp_rcv"],
					'snmp_community' => $row2["snmp_community"],
					'snmp_oid' => $row2["snmp_oid"],
					'ip_target' => $direccion_agente,
					'id_module_group' => $row2["id_module_group"],
					'id_modulo' => $row2["id_modulo"], 
					'plugin_user' => $row2["plugin_user"],
					'plugin_pass' => $row2["plugin_pass"],
					'plugin_parameter' => $row2["plugin_parameter"],
					'unit' => $row2["unit"],
					'max_timeout' => $row2["max_timeout"],
					'max_retries' => $row2["max_retries"],
					'id_plugin' => $row2['id_plugin'],
					'post_process' => $row2['post_process'],
					'dynamic_interval' => $row2['dynamic_interval'],
					'dynamic_max' => $row2['dynamic_max'],
					'dynamic_min' => $row2['dynamic_min'],
					'dynamic_two_tailed' => $row2['dynamic_two_tailed'],
					'min_warning' => $row2['min_warning'],
					'max_warning' => $row2['max_warning'],
					'str_warning' => $row2['str_warning'],
					'min_critical' => $row2['min_critical'],
					'max_critical' => $row2['max_critical'],
					'str_critical' => $row2['str_critical'],
					'critical_inverse' => $row2['critical_inverse'],
					'warning_inverse' => $row2['warning_inverse'],
					'critical_instructions' => $row2['critical_instructions'],
					'warning_instructions' => $row2['warning_instructions'],
					'unknown_instructions' => $row2['unknown_instructions'],
					'id_category' => $row2['id_category'],
					'macros' => $row2['macros'],
					'each_ff' => $row2['each_ff'],
					'min_ff_event' => $row2['min_ff_event'],
					'min_ff_event_normal' => $row2['min_ff_event_normal'],
					'min_ff_event_warning' => $row2['min_ff_event_warning'],
					'min_ff_event_critical' => $row2['min_ff_event_critical']
					);
				
				$name = $row2["name"];
				
				// Put tags in array if the component has to add them later
				if(!empty($row2["tags"])) {
					$tags = explode(',', $row2["tags"]);
				}
				else {
					$tags = array();
				}
				
				// Check if this module exists in the agent
				$module_name_check = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('delete_pending' => 0, 'nombre' => $name, 'id_agente' => $id_agent));
				
				if ($module_name_check !== false) {
					$modules_already_added[] = $row2["name"];
					$error_count++;
				}
				else {
					$id_agente_modulo = modules_create_agent_module($id_agent, $name, $values);
										
					if ($id_agente_modulo === false) {
						$error_count++;
					}
					else {
						if(!empty($tags)) {
							// Creating tags
							$tag_ids = array();
							foreach ($tags as $tag_name) {
								$tag_id = tags_get_id($tag_name);
								
								//If tag exists in the system we store to create it
								$tag_ids[] = $tag_id;
							}
							
							tags_insert_module_tag ($id_agente_modulo, $tag_ids);
						}
				
						$success_count++;
					}
				}
			}
		}
		if ($error_count > 0) {
			if (empty($modules_already_added))
				returnError('set_apply_module_template', __('Error adding modules') . sprintf(' (%s)', $error_count));
			else
				returnError('set_apply_module_template', __('Error adding modules. The following errors already exists: ') . implode(', ', $modules_already_added));
		}
		if ($success_count > 0) {
			returnData('string',  array('type' => 'string', 'data' => __('Modules successfully added')));
		}
	}
	
}

function api_get_cluster_status($id_cluster, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	$cluster_group = clusters_get_group($id_cluster);
	if(!check_acl($config['id_user'], $cluster_group, "AR")){
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}

	$sql = 'select ae.estado from tagente_estado ae, tagente_modulo tam, tcluster tc'
		. ' where tam.id_agente=tc.id_agent and ae.id_agente_modulo=tam.id_agente_modulo '
		. ' and tc.id=' . $id_cluster . ' and tam.nombre = "' . io_safe_input("Cluster status") . '" ';
	
	$value = db_get_value_sql($sql);
	
	if ($value === false) {
		returnError('id_not_found', $returnType);
		return;
	}
	
	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

function api_get_cluster_id_by_name($cluster_name, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	$value = cluster_get_id_by_name($cluster_name);
	if(($value === false) || ($value === null)){
		returnError('id_not_found', $returnType);
		return;
	}

	$cluster_group = clusters_get_group($value);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}

	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

function api_get_agents_id_name_by_cluster_id($cluster_id, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	$cluster_group = clusters_get_group($cluster_id);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}
	
	$all_agents = cluster_get_agents_id_name_by_cluster_id($cluster_id);
	
	if ($all_agents !== false) {
		$data = array('type' => 'json', 'data' => $all_agents);
		
		returnData('json', $data, JSON_FORCE_OBJECT);
	}
	else {
		returnError('error_agents', 'No agents retrieved.');
	}
}

function api_get_agents_id_name_by_cluster_name($cluster_name, $trash1, $trash2, $returnType) {
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}

	$value = cluster_get_id_by_name($cluster_name);
	if(($value === false) || ($value === null)){
		returnError('id_not_found', $returnType);
	}

	$cluster_group = clusters_get_group($value);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}
		
	$all_agents = cluster_get_agents_id_name_by_cluster_id($value);
	
	if (count($all_agents) > 0 and $all_agents !== false) {
		$data = array('type' => 'json', 'data' => $all_agents);
		
		returnData('json', $data, JSON_FORCE_OBJECT);
	}
	else {
		returnError('error_agents', 'No agents retrieved.');
	}
}

function api_get_modules_id_name_by_cluster_id ($cluster_id){
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	$cluster_group = clusters_get_group($cluster_id);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}

	$all_modules = cluster_get_modules_id_name_by_cluster_id($cluster_id);	
	
	if (count($all_modules) > 0 and $all_modules !== false) {
		$data = array('type' => 'json', 'data' => $all_modules);
		
		returnData('json', $data);
	}
	else {
		returnError('error_agent_modules', 'No modules retrieved.');
	}
	
}

function api_get_modules_id_name_by_cluster_name ($cluster_name){
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	$value = cluster_get_id_by_name($cluster_name);
	if(($value === false) || ($value === null)){
		returnError('id_not_found', $returnType);
	}

	$cluster_group = clusters_get_group($value);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}

	$all_modules = cluster_get_modules_id_name_by_cluster_id($value);
	
	if (count($all_modules) > 0 and $all_modules !== false) {
		$data = array('type' => 'json', 'data' => $all_modules);
		
		returnData('json', $data);
	}
	else {
		returnError('error_agent_modules', 'No modules retrieved.');
	}
	
}

function api_get_cluster_items ($cluster_id){
	global $config;

	if (defined ('METACONSOLE')) {
		return;
	}
	
	$cluster_group = clusters_get_group($cluster_id);
	if(!check_acl($config['id_user'], $cluster_group, "AR")) {
		returnError('error_get_cluster_status', __('The user cannot access to the cluster'));
		return;
	}

	$all_items = cluster_get_items($cluster_id);
	
	if (count($all_items) > 0 and $all_items !== false) {
		$data = array('type' => 'json', 'data' => $all_items);
		
		returnData('json', $data);
	}
	else {
		returnError('error_cluster_items', 'No items retrieved.');
	}
}

/////////////////////////////////////////////////////////////////////
// AUX FUNCTIONS
/////////////////////////////////////////////////////////////////////

function util_api_check_agent_and_print_error($id_agent, $returnType, $access = "AR", $force_meta = false) {
	global $config;

	$check_agent = agents_check_access_agent($id_agent, $access, $force_meta);
	if ($check_agent === true) return true;

	if ($check_agent === false || !check_acl($config['id_user'], 0, $access)) {
		returnError('forbidden', $returnType);
	} elseif ($check_agent === null) {
		returnError('id_not_found', $returnType);
	}

	return false;
}





?>
