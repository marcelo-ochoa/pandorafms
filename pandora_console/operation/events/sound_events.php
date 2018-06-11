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

// Real start
session_start ();

// Load global vars
if ((! file_exists("../../include/config.php")) || (! is_readable("../../include/config.php"))) {
	exit;
}

require_once ('../../include/config.php');
require_once ('../../include/functions.php');
require_once ('../../include/functions_db.php');
require_once ('../../include/auth/mysql.php');

global $config;

// Check user
check_login ();
$config["id_user"] = $_SESSION["id_usuario"];

$event_a = check_acl ($config['id_user'], 0, "ER");
$event_w = check_acl ($config['id_user'], 0, "EW");
$event_m = check_acl ($config['id_user'], 0, "EM");
$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));

if (! check_acl ($config['id_user'], 0, "ER") && ! check_acl ($config['id_user'], 0, "EW") && ! check_acl ($config['id_user'], 0, "EM")) {
	db_pandora_audit("ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	
	return;
}

$agents = agents_get_group_agents(0, false, "none", false, true);

echo "<html>";
echo "<head>";
echo "<title>" . __("Sound Events") . "</title>";
?>
<style type='text/css'>
	* {
		margin: 0;
		padding: 0;
	}
	
	img {
		border: 0;
	}
</style>
<?php
echo '<link rel="icon" href="../../' . ui_get_favicon() . '" type="image/ico" />';
echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';
echo "</head>";
echo "<body style='background-color: #494949; max-width: 550px; max-height: 400px; margin-top:40px;'>";
echo "<h1 class='modalheaderh1'>" . __("Sound console"). "</h1>";

$table = null;
$table->width = '100%';
$table->styleTable = 'padding-left:16px; padding-right:16px; padding-top:16px;';
$table->class = ' ';
$table->size[0] = '10%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';
$table->style[1] = 'font-weight: bold; vertical-align: top;';
$table->style[2] = 'font-weight: bold; vertical-align: top;';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, $access, true, 'group', '', 'changeGroup();', '', 0, true, false, true, '', false, 'max-width:200px;') . '<br />' . '<br />';

$table->data[0][2] = __('Type');
$table->data[0][3] = html_print_checkbox('alert_fired', 'alert_fired', true, true, false, 'changeType();') . __('Alert fired') . '<br />' .
	html_print_checkbox('critical', 'critical', true, true, false, 'changeType();') . __('Monitor critical') . '<br />' .
	html_print_checkbox('unknown', 'unknown', true, true, false, 'changeType();') . __('Monitor unknown') . '<br />' .
	html_print_checkbox('warning', 'warning', true, true, false, 'changeType();') . __('Monitor warning') . '<br />';

$table->data[1][0] = __('Agent');
$table->data[1][1] = html_print_select($agents, 'id_agents[]', true, false, '', '', true, true,'','','','width:120px; height:100px','',false,'','',true);

$table->data[1][2] = __('Event');
$table->data[1][3] = html_print_textarea ("events_fired", 200, 20, '', 'readonly="readonly" style="max-height:100px; background: #ddd; resize:none;"', true);

html_print_table($table);

$table = null;
$table->width = '100%';
$table->rowstyle[0] = 'text-align:center;';
$table->styleTable = 'padding-top:16px;padding-bottom:16px;';
$table->class = ' ';
$table->bgcolor = 'white';

$table->data[0][0] = '<a href="javascript: toggleButton();">' .
					 html_print_image("images/play.button.png", true, array("id" => "button")) .
					 '</a>';

$table->data[0][1] .= '<a href="javascript: ok();">' .
					  html_print_image("images/ok.button.png", true, array("style" => "margin-left: 15px;")) . 
					  '</a>';

$table->data[0][2] .= '<a href="javascript: test_sound_button();">' .
					  html_print_image("images/icono_test.png", true, array("id" => "button_try", "style" => "margin-left: 15px;")) . 
					 '</a>';

$table->data[0][3] .= html_print_image("images/tick_sound_events.png", true, array("id" => "button_status", "style" => "margin-left: 15px;"));
					 
html_print_table($table);

?>

<script src="../../include/javascript/jquery.js" type="text/javascript"></script>

<script type="text/javascript">
var group = 0;
var alert_fired = true;
var critical = true;
var warning = true;
var unknown = true;

var running = false;

var id_row = 0;

var button_play_status = "play";

var test_sound = false;

function test_sound_button() {
	if (!test_sound) {
		$("#button_try").attr('src', '../../images/icono_test.png');
		$('body').append("<audio src='../../include/sounds/Star_Trek_emergency_simulation.wav' autoplay='true' hidden='true' loop='false'>");
		test_sound = true;
	}
	else {
		$("#button_try").attr('src', '../../images/icono_test.png');
		$('body audio').remove();
		test_sound = false;
	}
}

function changeGroup() {
	group = $("#group").val();

	jQuery.post ("../../ajax.php",
		{"page" : "include/ajax/agent",
			"get_agents_group": 1,
			"id_group": group
		},
		function (data) {
			$("#id_agents").empty();
			
			jQuery.each (data, function (id, value) {
				if (value != "") {
					$("#id_agents").append('<option value="' + id + '">' + value + '</option>');
				}
			});
		},
		"json"
	);
}

function changeType() {
	alert_fired = $("input[name=alert_fired]").attr('checked');
	critical = $("input[name=critical]").attr('checked');
	warning = $("input[name=warning]").attr('checked');
	unknown = $("input[name=unknown]").attr('checked');
}

function toggleButton() {
	if (button_play_status == 'pause') {
		$("#button").attr('src', '../../images/play.button.png');
		stopSound();
		
		button_play_status = 'play';
	}
	else {
		$("#button").attr('src', '../../images/pause.button.png');
		forgetPreviousEvents();
		startSound();
		
		button_play_status = 'pause';
	}
}

function ok() {
	$('#button_status').attr('src','../../images/tick_sound_events.png');
	$('audio').remove();
	$('#textarea_events_fired').val("");
}

function stopSound() {
	$('audio').remove();
	
	$('body').css('background', '#494949');
	
	running = false;
}

function startSound() {
	running = true;
}

function forgetPreviousEvents() {
	var agents = $("#id_agents").val();

	jQuery.post ("../../ajax.php",
		{"page" : "operation/events/events",
			"get_events_fired": 1,
			"id_group": group,
			"agents[]" : agents,
			"alert_fired": alert_fired,
			"critical": critical,
			"warning": warning,
			"unknown": unknown,
			"id_row": id_row
		},
		function (data) {
			firedId = parseInt(data['fired']);
			if (firedId != 0) {
				id_row = firedId;
			}
			running = true;
		},
		"json"
	);
}

function check_event() {
	var agents = $("#id_agents").val();
	
	if (running) {
		jQuery.post ("../../ajax.php",
			{"page" : "operation/events/events",
				"get_events_fired": 1,
				"id_group": group,
				"agents[]" : agents,
				"alert_fired": alert_fired,
				"critical": critical,
				"warning": warning,
				"unknown": unknown,
				"id_row": id_row
			},
			function (data) {
				firedId = parseInt(data['fired']);
				if (firedId != 0) {
					id_row = firedId;
					var actual_text = $('#textarea_events_fired').val();
					if (actual_text == "") {
						$('#textarea_events_fired').val(data['message'] + "\n");
					}
					else {
						$('#textarea_events_fired').val(actual_text + "\n" + data['message'] + "\n");
					}
					$('#button_status').attr('src','../../images/sound_events_console_alert.gif');
					$('audio').remove();
					$('body').append("<audio src='../../" + data['sound'] + "' autoplay='true' hidden='true' loop='true'>");
				}
				
			},
			"json"
		);
	}
}

$(document).ready (function () {
	setInterval("check_event()", (10 * 1000)); //10 seconds between ajax request
	$("#table1").css("background-color", "#fff");
	$("#table2").css("background-color", "#fff");

	group_width = $("#group").width();
	$("#id_agents").width(group_width + 9);
});

</script>

<?php
echo "</body>";
echo "</html>";
?>
