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
check_login();

enterprise_hook('open_meta_frame');

// Include functions code
require_once $config['homedir'].'/include/functions_tags.php';

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Edit Tag');
    include 'general/noaccess.php';

    return;
}

// Get parameters
$action = (string) get_parameter('action', '');
$id_tag = (int) get_parameter('id_tag', 0);
$update_tag = (int) get_parameter('update_tag', 0);
$create_tag = (int) get_parameter('create_tag', 0);
$name_tag = (string) get_parameter('name_tag', '');
$description_tag = (string) get_parameter('description_tag', '');
$description_tag = io_safe_input(strip_tags(io_safe_output($description_tag)));
$url_tag = (string) get_parameter('url_tag', '');
$email_tag = (string) get_parameter('email_tag', '');
$phone_tag = (string) get_parameter('phone_tag', '');
$tab = (string) get_parameter('tab', 'list');

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'gmodules';
}

if (defined('METACONSOLE')) {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec='.$sec.'&sec2=advanced/component_management&tab=tags">'.html_print_image('images/list.png', true, ['title' => __('List tags')]).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;
    // Print header
    ui_meta_print_header(__('Tags'), '', $buttons);
} else {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec='.$sec.'&sec2=godmode/tag/tag&tab=list">'.html_print_image('images/list.png', true, ['title' => __('List tags')]).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;
    // Header
    ui_print_page_header(__('Tags configuration'), 'images/tag.png', false, '', true, $buttons);
}

// Two actions can performed in this page: update and create tags
// Update tag: update an existing tag
if ($update_tag && $id_tag != 0) {
    // Erase comma characters on tag name
    $name_tag = str_replace(',', '', $name_tag);

    $values = [];
    $values['name'] = $name_tag;
    $values['description'] = $description_tag;
    $values['url'] = $url_tag;
    $values['email'] = $email_tag;
    $values['phone'] = $phone_tag;

    $result = false;
    if ($values['name'] != '') {
        $result = tags_update_tag($values, 'id_tag = '.$id_tag);
    }

    if ($result === false) {
        db_pandora_audit('Tag management', "Fail try to update tag #$id_tag");
        ui_print_error_message(__('Error updating tag'));
    } else {
        db_pandora_audit('Tag management', "Update tag #$id_tag");
        ui_print_success_message(__('Successfully updated tag'));
    }
}

// Create tag: creates a new tag
if ($create_tag) {
    $return_create = true;

    // Erase comma characters on tag name
    $name_tag = str_replace(',', '', $name_tag);

    $data = [];
    $data['name'] = $name_tag;
    $data['description'] = $description_tag;
    $data['url'] = $url_tag;
    $data['email'] = $email_tag;
    $data['phone'] = $phone_tag;

    // DB insert
    $return_create = false;
    if ($data['name'] != '') {
        $return_create = tags_create_tag($data);
    }

    if ($return_create === false) {
        db_pandora_audit('Tag management', 'Fail try to create tag');
        ui_print_error_message(__('Error creating tag'));
        $action = 'new';
        // If create action ends successfully then current action is update
    } else {
        db_pandora_audit('Tag management', "Create tag #$return_create");
        ui_print_success_message(__('Successfully created tag'));
        $id_tag = $return_create;
        $action = 'update';
    }
}

// Form fields are filled here
// Get results when update action is performed
if ($action == 'update' && $id_tag != 0) {
    $result_tag = tags_search_tag_id($id_tag);
    $name_tag = $result_tag['name'];
    $description_tag = $result_tag['description'];
    $url_tag = $result_tag['url'];
    $email_tag = $result_tag['email'];
    $phone_tag = $result_tag['phone'];
} //end if
else {
    $name_tag = '';
    $description_tag = '';
    $url_tag = '';
    $email_tag = '';
    $phone_tag = '';
}


// Create/Update tag form
echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/tag/edit_tag&action='.$action.'&id_tag='.$id_tag.'" enctype="multipart/form-data">';

echo '<div align=left style="width: 100%" class="pandora_form">';

echo "<table border=0 cellpadding=4 cellspacing=4 class='databox filters' width=100%>";
if (defined('METACONSOLE')) {
    if ($action == 'update') {
        echo "<th colspan=8 style='text-align:center'>".__('Update Tag').'</th>';
    }

    if ($action == 'new') {
        echo "<th colspan=8 style='text-align:center'>".__('Create Tag').'</th>';
    }
}

    echo '<tr>';
        echo "<td align='left'>";
        echo '<b>'.__('Name').'</b>';
        echo '</td>';
        echo "<td align='left'>";
        html_print_input_text('name_tag', $name_tag);
        echo '</td>';
    echo '</tr>';
    echo '<tr>';
        echo "<td align='left'>";
        echo '<b>'.__('Description').'</b>';
        echo '</td>';
        echo "<td align='left'>";
        html_print_input_text('description_tag', $description_tag);
        echo '</td>';
    echo '</tr>';
    echo '<tr>';
        echo "<td align='left'>";
        echo '<b>'.__('Url').'</b>';
        echo ui_print_help_tip(
            __('Hyperlink to help information that has to exist previously.'),
            true
        );
        echo '</td>';
        echo "<td align='left'>";
        html_print_input_text('url_tag', $url_tag);
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo "<td align='left'>";
        echo '<b>'.__('Email').'</b>';
        echo ui_print_help_tip(
            __('Associated Email direction to use later in alerts associated to Tags.'),
            true
        );
        echo '</td>';
        echo "<td align='left'>";
        html_print_textarea('email_tag', 5, 20, $email_tag);
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo "<td align='left'>";
        echo '<b>'.__('Phone').'</b>';
        echo ui_print_help_tip(
            __('Associated phone number to use later in alerts associated to Tags.'),
            true
        );
        echo '</td>';
        echo "<td align='left'>";
        html_print_textarea('phone_tag', 5, 20, $phone_tag);
        echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '</div>';

        echo "<table border=0 cellpadding=0 cellspacing=0 class='' width=100%>";
        echo '<tr>';
        if ($action == 'update') {
            echo "<td align='center'>";
            html_print_input_hidden('update_tag', 1);
            echo '</td>';
            echo '<td align=right>';
            html_print_submit_button(__('Update'), 'update_button', false, 'class="sub next"');
            echo '</td>';
        }

        if ($action == 'new') {
            echo '<td align=center>';
            html_print_input_hidden('create_tag', 1);
            echo '</td>';
            echo '<td align=right>';
            html_print_submit_button(__('Create'), 'create_button', false, 'class="sub next"');
            echo '</td>';
        }

        echo '</tr>';
        echo '</table>';

        echo '</form>';

        enterprise_hook('close_meta_frame');
