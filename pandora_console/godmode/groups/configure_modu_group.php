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

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access Group Management2');
    include 'general/noaccess.php';
    return;
}

if (!is_metaconsole()) {
    // Header
    ui_print_page_header(__('Module group management'), 'images/module_group.png', false, '', true, '');
}

// Init vars
$icon = '';
$name = '';
$id_parent = 0;
$alerts_disabled = 0;
$custom_id = '';

$create_group = (bool) get_parameter('create_group');
$id_group = (int) get_parameter('id_group');

if ($id_group) {
    $group = db_get_row('tmodule_group', 'id_mg', $id_group);
    if ($group) {
        $name = $group['name'];
    } else {
        ui_print_error_message(__('There was a problem loading group'));
        echo '</table>';
        echo '</div>';
        echo '<div style="clear:both">&nbsp;</div>';
        echo '</div>';
        echo '<div id="foot">';
        include 'general/footer.php';
        echo '</div>';
        echo '</div>';
        exit;
    }
}


$table->width = '100%';
$table->class = 'databox filters';
$table->style[0] = 'font-weight: bold';
$table->data = [];
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text('name', $name, '', 35, 100, true);


echo '</span>';
if (is_metaconsole()) {
    echo '<form name="grupo" method="post" action="index.php?sec=advanced&sec2=advanced/component_management&tab=module_group">';
} else {
    echo '<form name="grupo" method="post" action="index.php?sec=gmodules&sec2=godmode/groups/modu_group_list">';
}

html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_group) {
    html_print_input_hidden('update_group', 1);
    html_print_input_hidden('id_group', $id_group);
    html_print_submit_button(__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create_group', 1);
    html_print_submit_button(__('Create'), 'crtbutton', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');
