<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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
enterprise_include_once('include/functions_agents.php');

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

$label    = get_parameter('label');
$label    = base64_decode($label);
$id       = get_parameter('id');
$id_agent = db_get_value ("id_agente","tagente_modulo","id_agente_modulo",$id);
$alias    = db_get_value ("alias","tagente","id_agente",$id_agent);
//$agent = agents_get_agent_with_ip ("192.168.50.31");
//$label = rawurldecode(urldecode(base64_decode(get_parameter('label', ''))));
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
		<title><?php echo __("%s Graph", get_product_name()) . ' (' . $alias . ' - ' . $label; ?>)</title>
		<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
		<link rel="stylesheet" href="../../include/styles/jquery-ui-1.10.0.custom.css" type="text/css" />
		<script type='text/javascript' src='../../include/javascript/pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery-1.9.0.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.jquery-ui-1.10.0.custom.js'></script>
		<?php
			if ($config['flash_charts']) {
				//Include the javascript for the js charts library
				include_once($config["homedir"] . '/include/graphs/functions_flot.php');
				echo include_javascript_dependencies_flot_graph(true, "../");
			}
		?>
		<script type='text/javascript'>
			window.onload = function() {
				// Hack to repeat the init process to period select
				var periodSelectId = $('[name="period"]').attr('class');

				period_select_init(periodSelectId);
			};
		</script>
	</head>
	<body bgcolor="#ffffff" style='background:#ffffff;'>
		<?php

		// Module id
		$id = (int) get_parameter ("id", 0);
		// Agent id
		$agent_id = (int) modules_get_agentmodule_agent($id);

		if (empty($id) || empty($agent_id)) {
			ui_print_error_message(__('There was a problem locating the source of the graph'));
			exit;
		}

		// ACL
		$all_groups = agents_get_all_groups_agent($agent_id);
		if (!check_acl_one_of_groups ($config["id_user"], $all_groups, "AR")) {
			require ($config['homedir'] . "/general/noaccess.php");
			exit;
		}

		$draw_alerts = get_parameter("draw_alerts", 0);

		$period = get_parameter ("period");
		$id     = get_parameter ("id", 0);
		$label = get_parameter ("label", "");
		$label_graph = base64_decode(get_parameter ("label", ""));
		$start_date = get_parameter ("start_date", date("Y/m/d"));
		$start_time = get_parameter ("start_time", date("H:i:s"));
		$draw_events = get_parameter ("draw_events", 0);
		$graph_type = get_parameter ("type", "sparse");
		$zoom = get_parameter ("zoom", $config['zoom_graph']);
		$baseline = get_parameter ("baseline", 0);
		$show_events_graph = get_parameter ("show_events_graph", 0);
		$show_percentil = get_parameter ("show_percentil", 0);
		$time_compare_separated = get_parameter ("time_compare_separated", 0);
		$time_compare_overlapped = get_parameter ("time_compare_overlapped", 0);
		$unknown_graph = get_parameter_checkbox ("unknown_graph", 1);

		$fullscale_sent = get_parameter ("fullscale_sent", 0);
		if(!$fullscale_sent){
			if(!isset($config['full_scale_option']) || $config['full_scale_option'] == 0){
				$fullscale = 0;
			}
			elseif($config['full_scale_option'] == 1){
				$fullscale = 1;
			}
			elseif($config['full_scale_option'] == 2){
				if($graph_type == 'boolean'){
					$fullscale = 1;
				}else{
					$fullscale = 0;
				}
			}
		}
		else{
			$fullscale = get_parameter('fullscale', 0);
		}

		// To avoid the horizontal overflow
		$width -= 20;

		$time_compare = false;

		if ($time_compare_separated) {
			$time_compare = 'separated';
		}
		else if ($time_compare_overlapped) {
			$time_compare = 'overlapped';
		}

		if ($zoom > 1) {
			$height = $height * ($zoom / 2.1);
			$width = $width * ($zoom / 1.4);
		}

		// Build date
		$date = strtotime("$start_date $start_time");
		$now = time();

		if ($date > $now)
			$date = $now;

		$urlImage = ui_get_full_url(false, false, false, false);

		$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $id);

		// log4x doesnt support flash yet
		if ($config['flash_charts'] == 1)
			echo '<div style="margin-left: 65px; padding-top: 10px;">';
		else
			echo '<div style="margin-left: 20px; padding-top: 10px;">';

		$width  = '90%';
		$height = '450';

		switch ($graph_type) {
			case 'boolean':
			case 'sparse':
			case 'string':
				$params =array(
					'agent_module_id'     => $id,
					'period'              => $period,
					'show_events'         => $draw_events,
					'title'               => $label_graph,
					'unit_name'           => $unit,
					'show_alerts'         => $draw_alerts,
					'date'                => $date,
					'unit'                => $unit,
					'baseline'            => $baseline,
					'homeurl'             => $urlImage,
					'adapt_key'           => 'adapter_' . $graph_type,
					'compare'             => $time_compare,
					'show_unknown'        => $unknown_graph,
					'percentil'           => (($show_percentil)? $config['percentil'] : null),
					'type_graph'          => $config['type_module_charts'],
					'fullscale'           => $fullscale,
					'zoom'                => $zoom
				);
				echo grafico_modulo_sparse ($params);
				echo '<br>';
				if ($show_events_graph){
					$width = '500';
					echo graphic_module_events($id, $width, $height,
						$period, $config['homeurl'], $zoom,
						'adapted_' . $graph_type, $date, true);
					}
				break;
			default:
				echo fs_error_image ('../images');
				break;
		}
		echo '</div>';

		////////////////////////////////////////////////////////////////
		// SIDE MENU
		////////////////////////////////////////////////////////////////
		$params = array();
		// TOP TEXT
		//Use the no_meta parameter because this image is only in the base console
		$params['top_text'] =  "<div style='color: white; width: 100%; text-align: center; font-weight: bold; vertical-align: top;'>" . html_print_image('images/wrench_blanco.png', true, array('width' => '16px'), false, false, true) . ' ' . __('Graph configuration menu') . ui_print_help_icon ("graphs",true, $config["homeurl"], "images/help_w.png", true) . "</div>";
		$params['body_text'] = "<div class='menu_sidebar_outer'>";
		$params['body_text'] .=__('Please, make your changes and apply with the <i>Reload</i> button');

		// MENU
		$params['body_text'] .= '<form method="get" action="stat_win.php">';
		$params['body_text'] .= html_print_input_hidden ("id", $id, true);
		$params['body_text'] .= html_print_input_hidden ("label", $label, true);

		if (!empty($server_id))
			$params['body_text'] .= html_print_input_hidden ("server", $server_id, true);

		if (isset($_GET["type"])) {
			$type = get_parameter_get ("type");
			$params['body_text'] .= html_print_input_hidden ("type", $type, true);
		}

		// FORM TABLE
		$table = html_get_predefined_table('transparent', 2);
		$table->width = '98%';
		$table->id = 'stat_win_form_div';
		$table->style[0] = 'text-align:left; padding: 7px;';
		$table->style[1] = 'text-align:left;';
		//$table->size[0] = '50%';
		$table->styleTable = 'border-spacing: 4px;';
		$table->class = 'alternate';

		$data = array();
		$data[0] = __('Refresh time');
		$data[1] = html_print_extended_select_for_time("refresh",
			$refresh, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		$data = array();
		$data[0] = __('Begin date');
		$data[1] = html_print_input_text ("start_date", $start_date,'', 10, 20, true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		$data = array();
		$data[0] = __('Begin time');
		$data[1] = html_print_input_text ("start_time", $start_time,'', 10, 10, true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		if(!modules_is_boolean($id)){
			$data = array();
			$data[0] = __('Zoom');
			$options = array ();
			$options[$zoom] = 'x' . $zoom;
			$options[1] = 'x1';
			$options[2] = 'x2';
			$options[3] = 'x3';
			$options[4] = 'x4';
			$options[5] = 'x5';
			$data[1] = html_print_select ($options, "zoom", $zoom, '', '', 0, true, false, false);
			$table->data[] = $data;
			$table->rowclass[] = '';
		}

		$data = array();
		$data[0] = __('Time range');
		$data[1] = html_print_extended_select_for_time('period',
			$period, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		$data = array();
		$data[0] = __('Show events');
		$disabled = false;
		if (isset($config['event_replication'])) {
			if ($config['event_replication'] && !$config['show_events_in_local']) {
				$disabled = true;
			}
		}
		$data[1] = html_print_checkbox ("draw_events", 1,
			(bool)$draw_events, true, $disabled);
		if ($disabled) {
			$data[1] .= ui_print_help_tip(
				__("'Show events' is disabled because this %s node is set to event replication.", get_product_name()), true);
		}
		$table->data[] = $data;
		$table->rowclass[] = '';

		$data = array();
		$data[0] = __('Show alerts');
		$data[1] = html_print_checkbox ("draw_alerts", 1, (bool) $draw_alerts, true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		/*
		$data = array();
		$data[0] = __('Show event graph');
		$data[1] = html_print_checkbox ("show_events_graph", 1, (bool) $show_events_graph, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		*/

		switch ($graph_type) {
			case 'boolean':
			case 'sparse':
				$data = array();
				$data[0] = __('Show percentil');
				$data[1] = html_print_checkbox ("show_percentil", 1, (bool) $show_percentil, true);
				$table->data[] = $data;
				$table->rowclass[] ='';

				$data = array();
				$data[0] = __('Time compare (Overlapped)');
				$data[1] = html_print_checkbox ("time_compare_overlapped", 1, (bool) $time_compare_overlapped, true);
				$table->data[] = $data;
				$table->rowclass[] = '';

				$data = array();
				$data[0] = __('Time compare (Separated)');
				$data[1] = html_print_checkbox ("time_compare_separated", 1, (bool) $time_compare_separated, true);
				$table->data[] = $data;
				$table->rowclass[] = '';

				$data = array();
				$data[0] = __('Show unknown graph');
				$data[1] = html_print_checkbox ("unknown_graph", 1, (bool) $unknown_graph, true);
				$table->data[] = $data;
				$table->rowclass[] = '';
				break;
		}

		$data = array();
		$data[0] = __('Show full scale graph (TIP)');
		$data[1] = html_print_checkbox ("fullscale", 1, (bool) $fullscale, 
								true, false);
		$table->data[] = $data;
		$table->rowclass[] = '';

		$form_table = html_print_table($table, true);
		$form_table .= '<div style="width:100%; text-align:right;">' .
			html_print_submit_button(__('Reload'), "submit", false,
				'class="sub upd"', true) . "</div>";

		unset($table);

		$table = new stdClass();
		$table->id = 'stat_win_form';
		$table->width = '100%';
		$table->cellspacing = 2;
		$table->cellpadding = 2;
		$table->class = 'databox';

		$data = array();
		$data[0] = html_print_div(array('id' => 'field_list', 'content' => $form_table,
			'style' => 'overflow: auto; height: 220px'), true);
		$table->data[] = $data;
		$table->rowclass[] = '';

		$params['body_text'] .= html_print_table($table, true);
		$params['body_text'] .= '</form>';
		$params['body_text'] .= '</div>'; // outer

		// ICONS
		$params['icon_closed'] = '/images/graphmenu_arrow_hide.png';
		$params['icon_open'] = '/images/graphmenu_arrow.png';

		// SIZE
		$params['width'] = 500;

		// POSITION
		$params['position'] = 'left';

		html_print_side_layer($params);

		// Hidden div to forced title
		html_print_div(array('id' => 'forced_title_layer',
			'class' => 'forced_title_layer', 'hidden' => true));
		?>

	</body>
</html>

<?php
// Echo the script tags of the datepicker and the timepicker
// Modify the user language cause the ui.datepicker language files use - instead _
$custom_user_language = str_replace('_', '-', $user_language);
ui_require_jquery_file("ui.datepicker-" . $custom_user_language, "include/javascript/i18n/", true);
ui_include_time_picker(true);
?>

<script>
	$('#checkbox-time_compare_separated').click(function() {
		$('#checkbox-time_compare_overlapped').removeAttr('checked');
	});
	$('#checkbox-time_compare_overlapped').click(function() {
		$('#checkbox-time_compare_separated').removeAttr('checked');
	});

	// Add datepicker and timepicker
	$("#text-start_date").datepicker({
		dateFormat: "<?php echo DATE_FORMAT_JS; ?>"
	});
	$("#text-start_time").timepicker({
		showSecond: true,
		timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'
	});

	$.datepicker.setDefaults($.datepicker.regional["<?php echo $custom_user_language; ?>"]);

	$(window).ready(function() {
		$("#field_list").css('height', ($(window).height() - 160) + 'px');
	});

	$(window).resize(function() {
		$("#field_list").css('height', ($(document).height() - 160) + 'px');
	});
</script>
