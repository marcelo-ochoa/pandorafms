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

require_once ("include/functions_agents.php");

require_once ($config["homedir"] . '/include/functions_graph.php');
include_graphs_dependencies();
require_once ($config['homedir'] . '/include/functions_groups.php');
require_once ($config['homedir'] . '/include/functions_ui.php');
require_once ($config['homedir'] .'/include/functions_incidents.php');
include_once ($config['homedir'] .'/include/functions_reporting_html.php');

include_once($config['homedir'] . "/include/functions_clippy.php");

check_login ();

$strict_user = (bool) db_get_value("strict_acl", "tusuario", "id_user", $config['id_user']);

$id_agente = get_parameter_get ("id_agente", -1);

$agent = db_get_row ("tagente", "id_agente", $id_agente);

if (empty($agent['server_name'])) {
	ui_print_error_message(
		__('The agent has not assigned server. Maybe agent does not run fine.'));
}

if ($agent === false) {
	ui_print_error_message(__('There was a problem loading agent'));
	return;
}

$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

if ($is_extra === ENTERPRISE_NOT_HOOK) {
	$is_extra = false;
}

if (! check_acl_one_of_groups ($config["id_user"], $all_groups, "AR") && ! check_acl_one_of_groups ($config["id_user"], $all_groups, "AW") && !$is_extra) {
	db_pandora_audit("ACL Violation", 
		"Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}

// START: TABLE AGENT BUILD
$table_agent = new stdClass();
$table_agent->id = 'agent_details_main';
$table_agent->width = '95%';
$table_agent->cellspacing = 0;
$table_agent->cellpadding = 0;
$table_agent->class = 'databox filters';
$table_agent->style[0] = 'width: 16px; text-align:center; padding: 0px;';
$table_agent->style[5] = 'width: 16px; text-align:center; padding: 0px;';
$table_agent->styleTable = 'padding:0px;';
$table_agent->data = array();
$data = array();

$agent_name = ui_print_agent_name($agent["id_agente"], true, 500, "font-size: medium;font-weight:bold", true);
$in_planned_downtime = db_get_sql('SELECT executed FROM tplanned_downtime 
	INNER JOIN tplanned_downtime_agents 
	ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
	WHERE tplanned_downtime_agents.id_agent = '. $agent["id_agente"] . 
	' AND tplanned_downtime.executed = 1');


if ($agent['disabled']) {
	if ($in_planned_downtime) {
		$agent_name = "<em>" . $agent_name . ui_print_help_tip(__('Disabled'), true);
	}
	else {
		$agent_name = "<em>" . $agent_name . "</em>" . ui_print_help_tip(__('Disabled'), true);
	}
}
else if ($agent['quiet']) {
	if ($in_planned_downtime) {
		$agent_name = "<em'>" . $agent_name . "&nbsp;" . html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => ""));
	}
	else {
		$agent_name = "<em'>" . $agent_name . "&nbsp;" . html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "</em>";
	}
}
else {
	$agent_name = $agent_name;
}

if ($in_planned_downtime && !$agent['disabled'] && !$agent['quiet']) {
	$agent_name .= "<em>" . "&nbsp;" . ui_print_help_tip(__('Agent in planned downtime'), true, 'images/minireloj-16.png') . "</em>";
}
else if (($in_planned_downtime  && !$agent['disabled']) || ($in_planned_downtime  && !$agent['quiet'])) {
	$agent_name .= "&nbsp;" . ui_print_help_tip(__('Agent in planned downtime'), true, 'images/minireloj-16.png') . "</em>";
}

if (!$config["show_group_name"])
	$data[0] = ui_print_group_icon ($agent["id_grupo"], true);
else
	$data[0] = "";
$table_agent->cellstyle[count($table_agent->data)][0] =
	'width: 16px; text-align:center; padding: 0px;';

$data[2] = $agent_name;
$table_agent->colspan[count($table_agent->data)][2] = 3;

$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 100px; word-break: break-all;';


$status_img = agents_detail_view_status_img ($agent["critical_count"],
	$agent["warning_count"], $agent["unknown_count"], $agent["total_count"], 
	$agent["notinit_count"]);
$data[2] .= "&nbsp;&nbsp;" .$status_img;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';


$data = array();

//$data[0] = reporting_tiny_stats ($agent, true, 'agent', '<div style="height: 5px;"></div>');
//$table_agent->rowspan[count($table_agent->data)][0] = 6;

// Fixed width non interactive charts
$status_chart_width = $config["flash_charts"] == false ? 100 : 150;
$graph_width = $config["flash_charts"] == false ? 200 : 150;

$data[0] = '<div style="margin: 0 auto 6px auto; width: 150px;">';
$data[0] .= '<div id="status_pie" style="margin: auto; width: ' . $status_chart_width . 'px;">';
$data[0] .= graph_agent_status ($id_agente, $graph_width, 120, true);
$data[0] .= '</div>';
$data[0] .= '<br>' . reporting_tiny_stats ($agent, true);
$data[0] .= ui_print_help_tip(__('Agent statuses are re-calculated by the server, they are not  shown in real time.'), true);
$data[0] .= '</div>';
$table_agent->rowspan[count($table_agent->data)][0] = 6;
$table_agent->colspan[count($table_agent->data)][0] = 2;
$table_agent->cellstyle[count($table_agent->data)][0] =
	'width: 150px; text-align:center; padding: 0px; vertical-align: top;';


$data[2] = ui_print_os_icon ($agent["id_os"], false, true, true, false, false, false, array('title' => __('OS') . ': ' . get_os_name ($agent["id_os"])));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px;';
$data[3] = empty($agent["os_version"]) ? get_os_name ((int) $agent["id_os"]) : $agent["os_version"];
$table_agent->colspan[count($table_agent->data)][3] = 2;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);

foreach ($addresses as $k => $add) {
	if ($add == $address) {
		unset($addresses[$k]);
	}
}

if (!empty($address)) {
	$data = array();
	$data[2] = html_print_image('images/world.png', true, array('title' => __('IP address')));
	$table_agent->cellstyle[count($table_agent->data)][2] =
		'width: 16px; text-align: right; padding: 0px;';
	$data[3] = '<span style="vertical-align:top; display: inline-block;">';
	$data[3] .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
	$data[3] .= '</span>';
	$table_agent->colspan[count($table_agent->data)][3] = 2;
	$table_agent->data[] = $data;
	$table_agent->rowclass[] = '';
}

$data = array();
$data[2] = html_print_image('images/version.png', true, array('title' => __('Agent Version')));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px;';
$data[3] = '<span style="vertical-align:top; display: inline-block;">';
$data[3] .= empty($agent["agent_version"]) ? '<i>' . __('N/A') . '</i>' : $agent["agent_version"];
$data[3] .= '</span>';
$table_agent->colspan[count($table_agent->data)][3] = 2;
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$data[2] = html_print_image('images/default_list.png', true,
	array('title' => __('Description')));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px;';
$data[3] = '<span style="vertical-align:top; display: inline-block;">';
$data[3] .= empty($agent["comentarios"])
	? '<em>' . __('N/A') . '</em>'
	: $agent["comentarios"];
$data[3] .= '</span>';
$table_agent->colspan[count($table_agent->data)][3] = 2;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

// END: TABLE AGENT BUILD

// START: TABLE CONTACT BUILD
$table_contact = new stdClass();
$table_contact->id = 'agent_contact_main';
$table_contact->width = '100%';
$table_contact->cellspacing = 0;
$table_contact->cellpadding = 0;
$table_contact->class = 'databox data';
$table_contact->style[0] = 'width: 30%;height:30px;';
$table_contact->style[1] = 'width: 70%;';

$table_contact->head[0] = ' <span>' . __('Agent contact') . '</span>';
$table_contact->head_colspan[0] = 2;

$data = array();
$data[0] = '<b>' . __('Interval') . '</b>';
$data[1] = human_time_description_raw ($agent["intervalo"]);
$table_contact->data[] = $data;

$data = array();
$data[0] = '<b>' . __('Last contact') . ' / ' . __('Remote') . '</b>';
$data[1] = ui_print_timestamp ($agent["ultimo_contacto"], true);
$data[1] .=  " / ";

if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
	$data[1] .= __('Never');
}
else {
	$data[1] .= date_w_fixed_tz($agent["ultimo_contacto_remoto"]);
}

$table_contact->data[] = $data;

$data[0] = '<b>' . __('Next contact') . '</b>';
$progress = agents_get_next_contact($id_agente);
$data[1] = progress_bar($progress, 200, 20, '', 1, false, "#666666");

if ($progress > 100) {
	$data[1] .= clippy_context_help("agent_out_of_limits");
}

$table_contact->data[] = $data;

// END: TABLE CONTACT BUILD

// START: TABLE DATA BUILD
$table_data = new stdClass();
$table_data->id = 'agent_data_main';
$table_data->width = '100%';
$table_data->styleTable = 'height:180px';
$table_data->cellspacing = 0;
$table_data->cellpadding = 0;
$table_data->class = 'databox data';
$table_data->style[0] = 'width: 30%;';
$table_data->style[1] = 'width: 40%;';

$table_data->head[0] = ' <span>' . __('Agent info') . '</span>';
$table_data->head_colspan[0] = 3;

$data = array();
$data[0] = '<b>' . __('Group') . '</b>';
$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$agent["id_grupo"].'">'.groups_get_name ($agent["id_grupo"]).'</a>';

// ACCESS RATE GRAPH
$access_agent = db_get_value_sql("SELECT COUNT(id_agent)
	FROM tagent_access
	WHERE id_agent = " . $id_agente);
if ($config["agentaccess"] && $access_agent > 0) {
	$data[2] =
		'<fieldset width=99% class="databox agente" style="">
		<legend>' .
				__('Agent access rate (24h)') .
		'</legend>' .
			graphic_agentaccess($id_agente, '95%', 100, SECONDS_1DAY, true) .
	'</fieldset>';
	$table_data->style[0] = 'width: 20%;';
	$table_data->style[1] = 'width: 30%;';
	$table_data->style[2] = 'width: 50%;';
	$table_data->rowspan[0][2] = 5;
}

$table_data->data[] = $data;

if (!empty($addresses)) {
	$data = array();
	$data[0] = '<b>' . __('Other IP addresses') . '</b>';
	$data[1] = '<div style="max-height: 45px; overflow-y: scroll; height:45px;">' .
		implode('<br>',$addresses) .
		'</div>';
	$table_data->data[] = $data;
}

$data = array();
$data[0] = '<b>' . __('Parent') . '</b>';
if ($agent["id_parent"] == 0) {
	$data[1] = '<em>' . __('N/A') . '</em>';
}
else {
	$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent["id_parent"].'">'.agents_get_alias($agent["id_parent"]).'</a>';
}

$table_data->data[] = $data;

$has_remote_conf = enterprise_hook('config_agents_has_remote_configuration',array($agent["id_agente"]));

if (enterprise_installed()) {
	$data = array();
	$data[0] = '<b>' . __('Remote configuration') . '</b>';
	if (!$has_remote_conf) {
		$data[1] = __('Disabled');
	}
	else {
		$data[1] = __('Enabled');
	}
	
	$table_data->data[] = $data;

	$data = array();
	$data[0] = '<b>' . __('Secondary groups') . '</b>';
	$secondary_groups = enterprise_hook('agents_get_secondary_groups', array($id_agente));
	if (!$secondary_groups) {
		$data[1] = '<em>' . __('N/A') . '</em>';
	}
	else {
		$secondary_links = array();
		foreach ($secondary_groups['for_select'] as $id => $name) {
			$secondary_links[] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id.'">'.$name.'</a>';
		}
		$data[1] = implode(', ', $secondary_links);
	}
	
	$table_data->data[] = $data;
}

if ($config['activate_gis'] || $agent['url_address'] != '') {
	$data = array();
	// Position Information
	if ($config['activate_gis']) {
		$dataPositionAgent =
			gis_get_data_last_position_agent($agent['id_agente']);
		
		$data[0] = '<b>' . __('Position (Long, Lat)') . '</b>';
		
		if ($dataPositionAgent === false) {
			$data[1] = __('There is no GIS data.');
		}
		else {
			$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
			if ($dataPositionAgent['description'] != "")
				$data[1] .= $dataPositionAgent['description'];
			else
				$data[1] .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
			$data[1] .= "</a>";
		}
		$table_data->data[] = $data;
	}
	
	// If the url description is setted
	if ($agent['url_address'] != '') {
		$data = array();
		$data[0] = '<b>' . __('Url address') . '</b>';
		$data[1] = '<a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a>';
		$table_data->data[] = $data;
	}
}

// Timezone Offset
if ($agent['timezone_offset'] != 0) {
	$data = array();
	$data[0] = '<b>' . __('Timezone Offset') . '</b>';
	$data[1] = $agent["timezone_offset"];
	$table->data[] = $data;
}

// Custom fields
$fields = db_get_all_rows_filter(
	'tagent_custom_fields',
	array('display_on_front' => 1));
if ($fields === false) {
	$fields = array ();
}

foreach ($fields as $field) {
	$data = array();
	$data[0] = '<b>' . $field['name'] .
		ui_print_help_tip (__('Custom field'), true) . '</b>';
		$custom_value = db_get_all_rows_sql("select tagent_custom_data.description,tagent_custom_fields.is_password_type from tagent_custom_fields 
			INNER JOIN tagent_custom_data ON tagent_custom_fields.id_field = tagent_custom_data.id_field where tagent_custom_fields.id_field = ".$field['id_field']." and tagent_custom_data.id_agent = ".$id_agente);

	if ($custom_value[0]['description'] === false || $custom_value[0]['description'] == '') {
		$custom_value[0]['description'] = '<i>-'.__('empty').'-</i>';
	}
	else {
		$custom_value[0]['description'] = ui_bbcode_to_html($custom_value[0]['description']);
	}
	
	if($custom_value[0]['is_password_type']){
			$data[1] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
		}
		else{
			$data[1] = $custom_value[0]['description'];	
		}

	$table_data->data[] = $data;
}

// END: TABLE DATA BUILD

// START: TABLE INCIDENTS

$last_incident = db_get_row_sql("
	SELECT * FROM tincidencia
	WHERE estado IN (0,1)
		AND id_agent = $id_agente
	ORDER BY actualizacion DESC");

if ($last_incident != false) {
	
	$table_incident->id = 'agent_incident_main';
	$table_incident->width = '100%';
	$table_incident->cellspacing = 0;
	$table_incident->cellpadding = 0;
	$table_incident->class = 'databox';
	$table_incident->style[0] = 'width: 30%;';
	$table_incident->style[1] = 'width: 70%;';
	
	$table_incident->head[0] = ' <span>' . '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident["id_incidencia"].'">' .__('Active incident on this agent') .'</a>'. '</span>';
	$table_incident->head_colspan[0] = 2;
	
	$data = array();
	$data[0] = '<b>' . __('Author') . '</b>';
	$data[1] = $last_incident["id_creator"];
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Title') . '</b>';
	$data[1] = '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident["id_incidencia"].'">' .$last_incident["titulo"].'</a>';
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Timestamp') . '</b>';
	$data[1] = $last_incident["inicio"];
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Priority') . '</b>';
	$data[1] = incidents_print_priority_img ($last_incident["prioridad"], true);
	$table_incident->data[] = $data;
	
}
// END: TABLE INCIDENTS

// START: TABLE INTERFACES

$network_interfaces_by_agents = agents_get_network_interfaces(array($agent));

$network_interfaces = array();
if (!empty($network_interfaces_by_agents) && !empty($network_interfaces_by_agents[$id_agente])) {
	$network_interfaces = $network_interfaces_by_agents[$id_agente]['interfaces'];
}

if (!empty($network_interfaces)) {
	$table_interface = new stdClass();
	$table_interface->id = 'agent_interface_info';
	$table_interface->class = 'databox data';
	$table_interface->width = '98%';
	$table_interface->style = array();
	$table_interface->style['interface_status'] = 'width: 30px;padding-top:0px;padding-bottom:0px;';
	$table_interface->style['interface_graph'] = 'width: 20px;padding-top:0px;padding-bottom:0px;';
	$table_interface->style['interface_event_graph'] = 'width: 100%;padding-top:0px;padding-bottom:0px;';
	$table_interface->align['interface_event_graph'] = 'right';
	//$table_interface->style['interface_event_graph'] = 'width: 5%;padding-top:0px;padding-bottom:0px;';
	$table_interface->align['interface_event_graph_text'] = 'left';
	$table_interface->style['interface_name'] = 'width: 10%;padding-top:0px;padding-bottom:0px;';
	$table_interface->align['interface_name'] = 'left';
	$table_interface->align['interface_ip'] = 'left';
	$table_interface->align['last_contact'] = 'left';
	$table_interface->style['last_contact'] = 'width: 40%;padding-top:0px;padding-bottom:0px;';
	$table_interface->style['interface_ip'] = 'width: 8%;padding-top:0px;padding-bottom:0px;';
	$table_interface->style['interface_mac'] = 'width: 12%;padding-top:0px;padding-bottom:0px;';

	$table_interface->head = array();
	$options = array(
		"class" => "closed",
		"style" => "vertical-align:righ; cursor:pointer;");
	$table_interface->head[0] = html_print_image("images/graphmenu_arrow.png", true, $options) . "&nbsp;&nbsp;";
	$table_interface->head[0] .= '<span style="vertical-align: middle;">' . __('Interface information') .' (SNMP)</span>';
	$table_interface->head_colspan = array();
	$table_interface->head_colspan[0] = 8;
	$table_interface->data = array();
	$event_text_cont = 0;
	
	foreach ($network_interfaces as $interface_name => $interface) {
		if (!empty($interface['traffic'])) {
			$permission = check_acl_one_of_groups($config['id_user'], $all_groups, "RR");
			
			if ($permission) {
				$params = array(
						'interface_name' => $interface_name,
						'agent_id' => $id_agente,
						'traffic_module_in' => $interface['traffic']['in'],
						'traffic_module_out' => $interface['traffic']['out']
					);
				$params_json = json_encode($params);
				$params_encoded = base64_encode($params_json);
				$win_handle = dechex(crc32($interface['status_module_id'].$interface_name));
				$graph_link = "<a href=\"javascript:winopeng('operation/agentes/interface_traffic_graph_win.php?params=$params_encoded','$win_handle')\">" .
					html_print_image("images/chart_curve.png", true, array("title" => __('Interface traffic'))) . "</a>";
			}
			else {
				$graph_link = "";
			}
		}
		else {
			$graph_link = "";
		}

		$events_limit = 5000;
		$user_groups = users_get_groups($config['id_user'], 'ER');
		$user_groups_ids = array_keys($user_groups);
		if (empty($user_groups)) {
			$groups_condition = ' 1 = 0 ';
		}
		else {
			$groups_condition = ' id_grupo IN (' . implode(',', $user_groups_ids) . ') ';
		}
		if (!check_acl ($config['id_user'], 0, "PM")) {
			$groups_condition .= " AND id_grupo != 0";
		}
		$status_condition = ' AND (estado = 0 OR estado = 1) ';
		$unixtime = get_system_time () - SECONDS_1DAY; //last hour
		$time_condition = 'AND (utimestamp > '.$unixtime.')';
		// Tags ACLS
		if ($id_group > 0 && in_array (0, $user_groups_ids)) {
			$group_array = (array) $id_group;
		}
		else {
			$group_array = $user_groups_ids;
		}
		$acl_tags = tags_get_acl_tags($config['id_user'], $group_array, 'ER',
			'event_condition', 'AND', '', true, array(), true);

		$id_modules_array = array();
		$id_modules_array[] = $interface['status_module_id'];

		$unixtime = get_system_time () - SECONDS_1DAY; //last hour
		$time_condition = 'WHERE (te.utimestamp > '.$unixtime.')';

		$sqlEvents = sprintf('
			SELECT *
			FROM tevento te
			INNER JOIN tagente_estado tae
				ON te.id_agentmodule = tae.id_agente_modulo
					AND tae.id_agente_modulo IN (%s)
			%s
		', implode(',', $id_modules_array), $time_condition);

		$sqlLast_contact = sprintf ('
			SELECT last_try
			FROM tagente_estado
			WHERE id_agente_modulo = ' . $interface['status_module_id']
		);

		$last_contact = db_get_all_rows_sql ($sqlLast_contact);
		$last_contact = array_shift($last_contact);
		$last_contact = array_shift($last_contact);

		$events = db_get_all_rows_sql ($sqlEvents);
		$text_event_header = __('Events info (24hr.)');
		if (!$events) {
			$no_events = array('color' => array('criticity' => 2));
			$e_graph = reporting_get_event_histogram ($no_events, $text_event_header);
		}
		else {
			$e_graph = reporting_get_event_histogram ($events, $text_event_header);
		}
		$data = array();
		$data['interface_name'] = "<strong>" . $interface_name . "</strong>";
		$data['interface_status'] = $interface['status_image'];
		$data['interface_graph'] = $graph_link;
		$data['interface_ip'] = $interface['ip'];
		$data['interface_mac'] = $interface['mac'];
		$data['last_contact'] = __('Last contact: ') . $last_contact;
		$data['interface_event_graph'] = $e_graph;
		if ($event_text_cont == 0) {
			$data['interface_event_graph_text'] = ui_print_help_tip('Module events graph', true);
			$event_text_cont++;
		}
		else {
			$data['interface_event_graph_text'] = "";
		}
		$table_interface->data[] = $data;
	}
	// This javascript piece of code is used to make expandible the body of the table
?>
	<script type="text/javascript">
		$(document).ready (function () {
			$("#agent_interface_info").find("tbody").hide();
			$("#agent_interface_info").find("thead").click (function () {
					var arrow = $("#agent_interface_info").find("thead").find("img");
					if (arrow.hasClass("closed")) {
						arrow.removeClass("closed");
						arrow.prop("src", "images/arrow-down-white.png");
						$("#agent_interface_info").find("tbody").show();
					} else {
						arrow.addClass("closed");
						arrow.prop("src", "images/graphmenu_arrow.png");
						$("#agent_interface_info").find("tbody").hide();
					}
				})
				.css('cursor', 'pointer');
		});
	</script>
<?php
}

// END: TABLE INTERFACES

$table = new stdClass();
$table->id = 'agent_details';
$table->width = '100%';
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->class = 'agents';
$table->style = array_fill(0, 3, 'vertical-align: top;');

$data = array();
$data[0][0] = html_print_table($table_agent, true);
$data[0][0] .=
	'<br /> <table width=95% class="databox agente" style="">
		<tr><th>' .
			__('Events (24h)') .
		'</th></tr>' .
		'<tr><td style="text-align:center;padding-left:20px;padding-right:20px;"><br />' .
		graph_graphic_agentevents ($id_agente, 450, 40, SECONDS_1DAY, '', true, true) . 
		'<br /></td></tr>' . 
	'</table>';

$table->style[0] = 'width:40%; vertical-align:top;';
$data[0][1] = html_print_table($table_contact, true);
$data[0][1] .= empty($table_data->data) ?
	'' :
	'<br>' . html_print_table($table_data, true);
$data[0][1] .= !isset($table_incident) ?
	'' :
	'<br>' . html_print_table($table_incident, true);

$table->rowspan[1][0] = 0;

$data[0][2] = '<div style="width:100%; text-align:right">';
$data[0][2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => '0', "title" => __('Refresh data'), "alt" => "")) . '</a><br>';
if (check_acl_one_of_groups ($config["id_user"], $all_groups, "AW"))
	$data[0][2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force remote checks'), "alt" => "")) . '</a>';
$data[0][2] .= '</div>';

$table->data = $data;
$table->rowclass[] = '';

$table->cellstyle[1][0] = 'text-align:center;';

html_print_table($table);
$data2[1][0] = !isset($table_interface) ?
	'' :
	html_print_table($table_interface, true);
$table->data = $data2;
$table->styleTable = '';
html_print_table($table);

unset($table);
?>
