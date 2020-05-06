<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Copyright (c) 2012-2013 Junichi Satoh
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
require_once 'include/functions_alerts.php';

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

ui_require_javascript_file('calendar');

$id = (int) get_parameter('id');
$date = (string) get_parameter('date');

$name = '';
$command = '';
$description = '';
$same_day = '';
$id_group = 0;
if ($id) {
    $special_day = alerts_get_alert_special_day($id);
    $date = str_replace('0001', '*', $special_day['date']);
    $date_orig = $date;
    $same_day = $special_day['same_day'];
    $description = $special_day['description'];
    $id_group = $special_day['id_group'];
    $id_group_orig = $id_group;
}

if ($date == '') {
    $date = date('Y-m-d', get_system_time());
}

// Header
ui_print_page_header(__('Alerts').' &raquo; '.__('Configure special day'), 'images/gm_alerts.png', false, '', true);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '20%';
$table->data = [];
$table->data[0][0] = __('Date');
$table->data[0][1] = html_print_input_text('date', $date, '', 10, 10, true);
$table->data[0][1] .= html_print_image('images/calendar_view_day.png', true, ['alt' => 'calendar', 'onclick' => "scwShow(scwID('text-date'),this);"]);
$table->data[1][0] = __('Group');
$groups = users_get_groups();
$own_info = get_user_info($config['id_user']);
// Only display group "All" if user is administrator or has "LM" privileges
if (users_can_manage_group_all('LM')) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

$table->data[1][1] = html_print_select_groups(false, 'LW', $display_all_group, 'id_group', $id_group, '', '', 0, true);

$table->data[2][0] = __('Same day of the week');
$days = [];
$days['monday'] = __('Monday');
$days['tuesday'] = __('Tuesday');
$days['wednesday'] = __('Wednesday');
$days['thursday'] = __('Thursday');
$days['friday'] = __('Friday');
$days['saturday'] = __('Saturday');
$days['sunday'] = __('Sunday');
$table->data[2][1] = html_print_select($days, 'same_day', $same_day, '', '', 0, true, false, false);

$table->data[3][0] = __('Description');
$table->data[3][1] = html_print_textarea('description', 10, 30, $description, '', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('id', $id);
    html_print_input_hidden('update_special_day', 1);
    html_print_input_hidden('id_group_orig', $id_group_orig);
    html_print_input_hidden('date_orig', $date_orig);
    html_print_submit_button(__('Update'), 'create', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create_special_day', 1);
    html_print_submit_button(__('Create'), 'create', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';
