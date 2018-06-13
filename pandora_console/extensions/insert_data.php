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

global $config;

include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions.php");

function createXMLData($agent, $agentModule, $time, $data) {
	global $config;
	
	$xmlTemplate = "<?xml version='1.0' encoding='UTF-8'?>
		<agent_data description='' group='' os_name='%s' " .
		" os_version='%s' interval='%d' version='%s' timestamp='%s' agent_name='%s' timezone_offset='0'>
			<module>
				<name><![CDATA[%s]]></name>
				<description><![CDATA[%s]]></description>
				<type><![CDATA[%s]]></type>
				<data><![CDATA[%s]]></data>
			</module>
		</agent_data>";
	
	$xml = sprintf(
		$xmlTemplate,
		io_safe_output(get_os_name($agent['id_os'])),
		io_safe_output($agent['os_version']),
		$agent['intervalo'],
		io_safe_output($agent['agent_version']),
		$time,
		io_safe_output($agent['nombre']),
		io_safe_output($agentModule['nombre']),
		io_safe_output($agentModule['descripcion']),
		modules_get_type_name($agentModule['id_tipo_modulo']),
		$data
	);
	
	$file_name = $config["remote_config"] . "/" . io_safe_output($agent["alias"]) . "." . str_replace($time, " ", "_") . ".data";
	return (bool) @file_put_contents($file_name, $xml);
}

function mainInsertData() {
	global $config;
	
	ui_print_page_header (__("Insert data"), "images/extensions.png", false, "", true, "");
	
	if (! check_acl ($config['id_user'], 0, "AW") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	$save = (bool) get_parameter("save");
	$agent_id = (int) get_parameter("agent_id");
	$agent_name = (string) get_parameter("agent_name");
	
	$id_agent_module = (int) get_parameter("id_agent_module");
	$data = (string) get_parameter('data');
	$date = (string) get_parameter('date', date(DATE_FORMAT));
	$time = (string) get_parameter('time', date(TIME_FORMAT));
	if (isset($_FILES['csv'])) {
		if ($_FILES['csv']['error'] != 4) {
			$csv = $_FILES['csv'];
		}
		else {
			$csv = false;
		}
	}
	else {
		$csv = false;
	}
	
	
	if ($save) {
		if (!check_acl($config['id_user'], agents_get_agent_group($agent_id), "AW")) {
			ui_print_error_message(__('You haven\'t privileges for insert data in the agent.'));
		}
		else {
			$agent = db_get_row_filter('tagente', array('id_agente' => $agent_id));
			$agentModule = db_get_row_filter('tagente_modulo', array('id_agente_modulo' => $id_agent_module));
			
			$done = 0;
			$errors = 0;
			if ($csv !== false) {
				$file = file($csv['tmp_name']);
				foreach ($file as $line) {
					$tokens = explode(';', $line);
					
					$utimestamp = strtotime(trim($tokens[0])) - get_fixed_offset();
					$timestamp = date(DATE_FORMAT . " " . TIME_FORMAT, $utimestamp);
					$result = createXMLData($agent, $agentModule, $timestamp, trim($tokens[1]));
					
					if ($result) {
						$done++;
					}
					else {
						$errors++;
					}
				}
			}
			else {
				$utimestamp = strtotime($date . " " . $time) - get_fixed_offset();
				$timestamp = date(DATE_FORMAT . " " . TIME_FORMAT, $utimestamp);
				$result = createXMLData($agent, $agentModule, $timestamp, $data);
				
				if ($result) {
					$done++;
				}
				else {
					$errors++;
				}
			}
		}
		
		if ($errors > 0) {
			$msg = sprintf(__('Can\'t save agent (%s), module (%s) data xml.'), $agent['alias'], $agentModule['nombre']);
			if ($errors > 1) {
				$msg .= " ($errors)";
			}
			ui_print_error_message($msg);
		}
		if ($done > 0) {
			$msg = sprintf(__('Save agent (%s), module (%s) data xml.'), $agent['alias'], $agentModule['nombre']);
			if ($done > 1) {
				$msg .= " ($done)";
			}
			ui_print_success_message($msg);
		}
	}
	
	echo '<div class="notify" style="margin-bottom:15px;">';
	echo sprintf(__("Please check that the directory \"%s\" is writeable by the apache user. <br /><br />The CSV file format is date;value&lt;newline&gt;date;value&lt;newline&gt;... The date in CSV is in format Y/m/d H:i:s."),
		$config['remote_config']);
	echo '</div>';
	
	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'databox filters';
	$table->style = array();
	$table->style[0] = 'font-weight: bolder;';
	
	$table->data = array();
	
	$table->data[0][0] = __('Agent');
	$params = array();
	$params['return'] = true;
	$params['show_helptip'] = true;
	$params['input_name'] = 'agent_name';
	$params['value'] = $agent_name;
	$params['javascript_is_function_select'] = true;
	$params['javascript_name_function_select'] = 'custom_select_function';
	$params['javascript_code_function_select'] = '';
	$params['use_hidden_input_idagent'] = true;
	$params['print_hidden_input_idagent'] = true;
	$params['hidden_input_idagent_id'] = 'hidden-autocomplete_id_agent';
	$params['hidden_input_idagent_name'] = 'agent_id';
	$params['hidden_input_idagent_value'] = $agent_id;
	
	$table->data[0][1] = ui_print_agent_autocomplete_input($params);
	
	$table->data[1][0] = __('Module');
	$modules = array ();
	if ($agent_id){
		$modules = agents_get_modules ($agent_id, false, array("delete_pending" => 0));
	}
	$table->data[1][1] = html_print_select ($modules, 'id_agent_module', $id_agent_module, true,
		__('Select'), 0, true, false, true, '', empty($agent_id));
	$table->data[2][0] = __('Data');
	$table->data[2][1] = html_print_input_text('data', $data, __('Data'), 40, 60, true);
	$table->data[3][0] = __('Date');
	$table->data[3][1] = html_print_input_text ('date', $date, '', 11, 11, true).' ';
	$table->data[3][1] .= html_print_input_text ('time', $time, '', 7, 7, true);
	$table->data[4][0] = __('CSV');
	$table->data[4][1] = html_print_input_file('csv', true);
	
	echo "<form method='post' enctype='multipart/form-data'>";
	
	html_print_table($table);
	
	echo "<div style='text-align: right; width: " . $table->width . "'>";
	html_print_input_hidden('save', 1);
	html_print_submit_button(__('Save'), 'submit', ($id_agent === ''), 'class="sub next"');
	echo "</div>";
	
	echo "</form>";
	
	ui_require_css_file ('datepicker');
	ui_include_time_picker();
	ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");
?>
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready (function () {
		
		$('#text-time').timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
		
		$("#text-date").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
	});
	
	function custom_select_function(agent_name) {
		$('#id_agent_module').empty ();
		var inputs = [];
		var id_agent = $('#hidden-autocomplete_id_agent').val();
		
		inputs.push ("id_agent=" + id_agent);
		inputs.push ("delete_pending=0");
		inputs.push ("get_agent_modules_json=1");
		inputs.push ("page=operation/agentes/ver_agente");
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: action="ajax.php",
			dataType: 'json',
			success: function (data) {
				$('#id_agent_module').append ($('<option></option>').attr ('value', 0).text ("--"));
				jQuery.each (data, function (i, val) {
					s = js_html_entity_decode (val['nombre']);
					$('#id_agent_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
				});
				$('#id_agent_module').enable();
				$('#id_agent_module').fadeIn ('normal');
				
				$('#submit-submit').enable();
				$('#submit-submit').fadeIn ('normal');
			}
		});
	}
	
	/* ]]> */
</script>
<?php
}

extensions_add_godmode_function('mainInsertData');
extensions_add_godmode_menu_option(__('Insert Data'), 'AW', 'gagente', null, "v1r1");
?>
