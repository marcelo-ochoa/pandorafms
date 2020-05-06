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

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

$id = (int) get_parameter('id');

$al_action = alerts_get_alert_action($id);
$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

if ($al_action !== false) {
    $own_info = get_user_info($config['id_user']);
    if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
        $own_groups = array_keys(users_get_groups($config['id_user'], 'LM'));
    } else {
        $own_groups = array_keys(users_get_groups($config['id_user'], 'LM', false));
    }

    $is_in_group = in_array($al_action['id_group'], $own_groups);

    // Header
    if (defined('METACONSOLE')) {
        alerts_meta_print_header();
    } else {
        ui_print_page_header(
            __('Alerts').' &raquo; '.__('Configure alert action'),
            'images/gm_alerts.png',
            false,
            'alert_config',
            true
        );
    }
} else {
    // Header
    if (defined('METACONSOLE')) {
        alerts_meta_print_header();
    } else {
        ui_print_page_header(
            __('Alerts').' &raquo; '.__('Configure alert action'),
            'images/gm_alerts.png',
            false,
            'alert_config',
            true
        );
    }
}


$name = '';
$id_command = '';
$group = 0;
// All group is 0
$action_threshold = 0;
// All group is 0
if ($id) {
    $action = alerts_get_alert_action($id);
    $name = $action['name'];
    $id_command = $action['id_alert_command'];

    $group = $action['id_group'];
    $action_threshold = $action['action_threshold'];
}

// Hidden div with help hint to fill with javascript
html_print_div(
    [
        'id'      => 'help_alert_macros_hint',
        'content' => ui_print_help_icon('alert_macros', true),
        'hidden'  => true,
    ]
);

$table = new stdClass();
$table->id = 'table_macros';
$table->width = '100%';
$table->class = 'databox filters';

if (defined('METACONSOLE')) {
    if ($id) {
        $table->head[0] = __('Update Action');
    } else {
        $table->head[0] = __('Create Action');
    }

    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
}

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '20%';
$table->data = [];
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text('name', $name, '', 35, 255, true);
if (io_safe_output($name) == 'Monitoring Event') {
    $table->data[0][1] .= '&nbsp;&nbsp;'.ui_print_help_tip(
        __('This action may stop working, if you change its name.'),
        true,
        'images/header_yellow.png'
    );
}

$table->colspan[0][1] = 2;

$table->data[1][0] = __('Group');

$own_info = get_user_info($config['id_user']);

$table->data[1][1] = html_print_select_groups(false, 'LW', true, 'group', $group, '', '', 0, true);
$table->colspan[1][1] = 2;

$table->data[2][0] = __('Command');
$commands_sql = db_get_all_rows_filter(
    'talert_commands',
    ['id_group' => array_keys(users_get_groups(false, 'LW'))],
    [
        'id',
        'name',
    ],
    'AND',
    false,
    true
);
$table->data[2][1] = html_print_select_from_sql(
    $commands_sql,
    'id_command',
    $id_command,
    '',
    __('None'),
    0,
    true
);
$table->data[2][1] .= ' ';
if (check_acl($config['id_user'], 0, 'PM')) {
    $table->data[2][1] .= __('Create Command');
    $table->data[2][1] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&pure='.$pure.'">';
    $table->data[2][1] .= html_print_image('images/add.png', true);
    $table->data[2][1] .= '</a>';
}

$table->data[2][1] .= '<div id="command_description" style=""></div>';
$table->colspan[2][1] = 2;

$table->data[3][0] = __('Threshold');
$table->data[3][1] = html_print_extended_select_for_time(
    'action_threshold',
    $action_threshold,
    '',
    '',
    '',
    false,
    true,
    false,
    true,
    '',
    false,
    false,
    '',
    false,
    true
);
$table->colspan[3][1] = 2;

$table->data[4][0] = '';
$table->data[4][1] = __('Firing');
$table->data[4][2] = __('Recovery');
$table->cellstyle[4][1] = 'font-weight: bold;';
$table->cellstyle[4][2] = 'font-weight: bold;';

$table->data[5][0] = __('Command preview');
$table->data[5][1] = html_print_textarea(
    'command_preview',
    5,
    30,
    '',
    'disabled="disabled"',
    true
);
$table->data[5][2] = html_print_textarea(
    'command_recovery_preview',
    5,
    30,
    '',
    'disabled="disabled"',
    true
);

for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
    $table->data['field'.$i][0] = html_print_image(
        'images/spinner.gif',
        true
    );
    $table->data['field'.$i][1] = html_print_image(
        'images/spinner.gif',
        true
    );
    $table->data['field'.$i][2] = html_print_image(
        'images/spinner.gif',
        true
    );

    // Store the value in a hidden to keep it on first execution
    $table->data['field'.$i][1] .= html_print_input_hidden(
        'field'.$i.'_value',
        !empty($action['field'.$i]) ? $action['field'.$i] : '',
        true
    );
    $table->data['field'.$i][2] .= html_print_input_hidden(
        'field'.$i.'_recovery_value',
        !empty($action['field'.$i.'_recovery']) ? $action['field'.$i.'_recovery'] : '',
        true
    );
}


echo '<form method="post" action="'.'index.php?sec='.$sec.'&'.'sec2=godmode/alerts/alert_actions&'.'pure='.$pure.'">';
$table_html = html_print_table($table, true);

//
// Hack to hook the bubble dialog of clippy in any place, the intro.js
// fails with new elements in the dom from javascript code
// ----------------------------------------------------------------------
/*
    $table_html = str_replace(
    "</table>",
    "</div>",
    $table_html);
    $table_html = str_replace(
    '<tr id="table_macros-field1" style="" class="datos2">',
    "</tbody></table>
    <div id=\"clippy_fields\">
    <table>
    <tbody>
    <tr id=\"table_macros-field1\" class=\"datos\">",
    $table_html);
*/
//
echo $table_html;

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('id', $id);
    if ($al_action['id_group'] == 0) {
        // then must have "PM" access privileges
        if (check_acl($config['id_user'], 0, 'PM')) {
            html_print_input_hidden('update_action', 1);
            html_print_submit_button(__('Update'), 'create', false, 'class="sub upd"');
        }
    } else {
        html_print_input_hidden('update_action', 1);
        html_print_submit_button(__('Update'), 'create', false, 'class="sub upd"');
    }
} else {
    html_print_input_hidden('create_action', 1);
    html_print_submit_button(__('Create'), 'create', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');

ui_require_javascript_file('pandora_alerts');
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');
?>

<script type="text/javascript">
$(document).ready (function () {
    var original_command;
    var origicommand_descriptionnal_command;

    if (<?php echo (int) $id_command; ?>) {
        original_command = "<?php echo str_replace("\r\n", '<br>', addslashes(io_safe_output(alerts_get_alert_command_command($id_command)))); ?>";
        render_command_preview(original_command);
        command_description = "<?php echo str_replace("\r\n", '<br>', addslashes(io_safe_output(alerts_get_alert_command_description($id_command)))); ?>";
        
        render_command_description(command_description);
    }

    $("#id_command").change (function () {
        values = Array ();
        values.push({
            name: "page",
            value: "godmode/alerts/alert_commands"});
        values.push({
            name: "get_alert_command",
            value: "1"});
        values.push({
            name: "id",
            value: this.value});
        
        jQuery.post (<?php echo "'".ui_get_full_url('ajax.php', false, false, false)."'"; ?>,
            values,
            function (data, status) {
                original_command = data["command"];
                render_command_preview (original_command);
                command_description = data["description"];
                if (command_description != undefined) {
                    render_command_description(command_description);
                } else {
                    render_command_description('');

                }
                
                var max_fields = parseInt('<?php echo $config['max_macro_fields']; ?>');
                
                // Change the selected group
                $("#group option").each(function(index, value) {
                    var current_group = $(value).val();
                });
                if (data.id_group != 0 && $("#group").val() != data.id_group) {
                    $("#group").val(0);
                }

                for (i = 1; i <= max_fields; i++) {
                    var old_value = '';
                    var old_recovery_value = '';
                    var field_row = data["fields_rows"][i];
                    var $table_macros_field = $('#table_macros-field' + i);
                    
                    // If the row is empty, hide it
                    if (field_row == '') {
                        $table_macros_field.hide();
                        continue;
                    }
                    old_value = '';
                    old_recovery_value = '';
                    // Only keep the value if is provided from hidden (first time)
                    if (($("[name=field" + i + "_value]").attr('id'))
                        == ("hidden-field" + i + "_value")) {
                        
                        old_value = $("[name=field" + i + "_value]").val();
                    }
                    
                    if (($("[name=field" + i + "_recovery_value]").attr('id'))
                        == ("hidden-field" + i + "_recovery_value")) {
                        
                        old_recovery_value =
                            $("[name=field" + i + "_recovery_value]").val();
                    }
                    
                    // Replace the old column with the new
                    $table_macros_field.replaceWith(field_row);
                    if (old_value != '' || old_recovery_value != '') {
                        var inputType = $("[name=field" + i + "_value]").attr('type')
                        if (inputType == 'radio') {
                            if(old_value == 'text/plain'){
                                if ($("[name=field" + i + "_value]").val() == 'text/plain') {
                                    $("[name=field" + i + "_value]").attr('checked','checked');
                                }
                            }
                            else{
                                if($("[name=field" + i + "_value]").val() == 'text/html') {
                                    $("[name=field" + i + "_value]").attr('checked','checked');
                                }
                            }
                            if(old_recovery_value == 'text/plain'){
                                if ($("[name=field" + i + "_recovery_value]").val() == 'text/plain') {
                                    $("[name=field" + i + "_recovery_value]").attr('checked','checked');
                                }
                            }
                            else{
                                if ($("[name=field" + i + "_recovery_value]").val() == 'text/html') {
                                    $("[name=field" + i + "_recovery_value]").attr('checked','checked');
                                }
                            }
                        }
                        else {
                            $("[name=field" + i + "_value]").val(old_value);
                            $("[name=field" + i + "_recovery_value]").val(old_recovery_value);
                        }
                    }
                    else {
                        if ($("[name=field" + i + "_value]").val() != 'text/plain') {
                            $("[name=field" + i + "_value]")
                                .val($("[name=field" + i + "_value]")
                                .val());
                            $("[name=field" + i + "_recovery_value]")
                                .val($("[name=field" + i + "_recovery_value]")
                                .val());
                        }
                    }
                    // Add help hint only in first field
                    if (i == 1) {
                        var td_content = $table_macros_field.find('td').eq(0);
                        
                        $(td_content)
                            .html(
                                $(td_content).html() +
                            $('#help_alert_macros_hint').html());
                    }
                    
                    $table_macros_field.show();
                }
                
                tinyMCE.init({
                    selector: 'textarea.tiny-mce-editor',
                    theme : "advanced",
                    plugins : "preview, print, table, searchreplace, nonbreaking, xhtmlxtras, noneditable",
                    theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsize,select",
                    theme_advanced_buttons2 : "search,replace,|,bullist,numlist,|,undo,redo,|,link,unlink,image,|,cleanup,code,preview,|,forecolor,backcolor",
                    theme_advanced_buttons3 : "",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_resizing : true,
                    theme_advanced_statusbar_location : "bottom",
                    force_p_newlines : false,
                    forced_root_block : '',
                    inline_styles : true,
                    valid_children : "+body[style]",
                    element_format : "html"
                });
                
                render_command_preview(original_command);
                render_command_recovery_preview(original_command);
                
                $(".fields").keyup(function() {
                    render_command_preview(original_command);
                });
                $(".fields_recovery").keyup(function() {
                    render_command_recovery_preview(original_command);
                });
                $("select.fields").change(function() {
                    render_command_preview(original_command);
                });
                $("select.fields_recovery").change(function() {
                    render_command_recovery_preview(original_command);
                });
            },
            "json"
        );
    }).change();
});

</script>
