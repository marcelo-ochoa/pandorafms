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

require_once $config['homedir'].'/include/functions_event_responses.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

$event_responses = event_responses_get_responses();

if (empty($event_responses)) {
    ui_print_info_message(['no_close' => true, 'message' => __('No responses found') ]);
    $event_responses = [];
    return;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->cellpadding = 0;
$table->cellspacing = 0;

$table->size = [];
$table->size[0] = '200px';
$table->size[2] = '100px';
$table->size[3] = '70px';

$table->style[2] = 'text-align:left;';

$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Group');
$table->head[3] = __('Actions');

$table->data = [];

foreach ($event_responses as $response) {
    $data = [];
    $data[0] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'&amp;pure='.$config['pure'].'">'.$response['name'].'</a>';
    $data[1] = $response['description'];
    $data[2] = ui_print_group_icon($response['id_group'], true);
    $table->cellclass[][3] = 'action_buttons';
    $data[3] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&action=delete_response&id_response='.$response['id'].'&amp;pure='.$config['pure'].'">'.html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';
    $data[3] .= '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'&amp;pure='.$config['pure'].'">'.html_print_image('images/pencil.png', true, ['title' => __('Edit')]).'</a>';
    $table->data[] = $data;
}

html_print_table($table);


echo '<div style="width:100%;text-align:right;">';
echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&amp;pure='.$config['pure'].'">';
html_print_submit_button(__('Create response'), 'create_response_button', false, ['class' => 'sub next']);
echo '</form>';
echo '</div>';
