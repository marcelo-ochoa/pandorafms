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
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

enterprise_hook('open_meta_frame');

if (is_ajax()) {
    $get_template_tooltip = (bool) get_parameter('get_template_tooltip');

    if ($get_template_tooltip) {
        $id_template = (int) get_parameter('id_template');
        $template = alerts_get_alert_template($id_template);
        if ($template === false) {
            return;
        }

        echo '<h3>'.$template['name'].'</h3>';
        echo '<strong>'.__('Type').': </strong>';
        echo alerts_get_alert_templates_type_name($template['type']);

        echo '<br />';
        echo ui_print_alert_template_example($template['id'], true);

        echo '<br />';

        if ($template['description'] != '') {
            echo '<strong>'.__('Description').':</strong><br />';
            echo $template['description'];
            echo '<br />';
        }

        echo '<strong>'.__('Priority').':</strong> ';
        echo get_priority_name($template['priority']);
        echo '<br />';

        if ($template['monday'] && $template['tuesday']
            && $template['wednesday'] && $template['thursday']
            && $template['friday'] && $template['saturday']
            && $template['sunday']
        ) {
            // Everyday
            echo '<strong>'.__('Everyday').'</strong><br />';
        } else {
            $days = [
                'monday'    => __('Monday'),
                'tuesday'   => __('Tuesday'),
                'wednesday' => __('Wednesday'),
                'thursday'  => __('Thursday'),
                'friday'    => __('Friday'),
                'saturday'  => __('Saturday'),
                'sunday'    => __('Sunday'),
            ];

            echo '<strong>'.__('Days').'</strong>: '.__('Every').' ';
            $actives = [];
            foreach ($days as $day => $name) {
                if ($template[$day]) {
                    array_push($actives, $name);
                }
            }

            $last = array_pop($actives);
            if (count($actives)) {
                echo implode(', ', $actives);
                echo ' '.__('and').' ';
            }

            echo $last;
            echo '<br />';
        }

        echo '<strong>'.__('Time threshold').': </strong>';
        echo human_time_description_raw($template['time_threshold']);
        echo '<br />';

        if ($template['time_from'] != $template['time_to']) {
            echo '<strong>'.__('From').'</strong> ';
            echo $template['time_from'];
            echo ' <strong>'.__('to').'</strong> ';
            echo $template['time_to'];
            echo '<br />';
        }

        return;
    }

    return;
}

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

$update_template = (bool) get_parameter('update_template');
$delete_template = (bool) get_parameter('delete_template');
$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

// This prevents to duplicate the header in case delete_templete action is performed
if (!$delete_template) {
    // Header
    if (defined('METACONSOLE')) {
        alerts_meta_print_header();
    } else {
        // ~ ui_print_page_header (__('Alerts')." &raquo; ". __('Alert templates'), "images/gm_alerts.png", false, "alerts_config", true);
        ui_print_page_header(
            __('Alerts').' &raquo; '.__('Alert templates'),
            'images/gm_alerts.png',
            false,
            'alert_templates_tab',
            true
        );
    }
}


if ($update_template) {
    $id = (int) get_parameter('id');

    $recovery_notify = (bool) get_parameter('recovery_notify');

    $fields_recovery = [];
    for ($i = 1; $i <= 10; $i++) {
        $values['field'.$i] = (string) get_parameter('field'.$i);
        $values['field'.$i.'_recovery'] = $recovery_notify ? (string) get_parameter('field'.$i.'_recovery') : '';
    }

    $values['recovery_notify'] = $recovery_notify;
    $result = alerts_update_alert_template($id, $values);

    ui_print_result_message(
        $result,
        __('Successfully updated'),
        __('Could not be updated')
    );
}

// If user tries to delete a template with group=ALL then must have "PM" access privileges
if ($delete_template) {
    $id = get_parameter('id');
    $al_template = alerts_get_alert_template($id);

    if ($al_template !== false) {
        // If user tries to delete a template with group=ALL then must have "PM" access privileges
        if ($al_template['id_group'] == 0) {
            if (! check_acl($config['id_user'], 0, 'PM')) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            } else {
                if (defined('METACONSOLE')) {
                    alerts_meta_print_header();
                } else {
                    ui_print_page_header(
                        __('Alerts').' &raquo; '.__('Alert templates'),
                        'images/gm_alerts.png',
                        false,
                        'alerts_config',
                        true
                    );
                }
            }
        } //end if
        else {
            $own_info = get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(users_get_groups($config['id_user'], 'LM'));
            } else {
                $own_groups = array_keys(users_get_groups($config['id_user'], 'LM', false));
            }

            $is_in_group = in_array($al_template['id_group'], $own_groups);
            // Then template group have to be is his own groups
            if ($is_in_group) {
                if (defined('METACONSOLE')) {
                    alerts_meta_print_header();
                } else {
                    ui_print_page_header(
                        __('Alerts').' &raquo; '.__('Alert templates'),
                        'images/gm_alerts.png',
                        false,
                        'alerts_config',
                        true
                    );
                }
            } else {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        }
    } else {
        if (defined('METACONSOLE')) {
            alerts_meta_print_header();
        } else {
            ui_print_page_header(
                __('Alerts').' &raquo; '.__('Alert templates'),
                'images/gm_alerts.png',
                false,
                'alerts_config',
                true
            );
        }
    }

    $result = alerts_delete_alert_template($id);

    if ($result) {
        db_pandora_audit('Template alert management', 'Delete alert template #'.$id);
    } else {
        db_pandora_audit('Template alert management', 'Fail try to delete alert template #'.$id);
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}


$search_string = (string) get_parameter('search_string');
$search_type = (string) get_parameter('search_type');
$url = ui_get_url_refresh(
    [
        'offset'        => false,
        'search_string' => $search_string,
        'search_type'   => $search_type,
    ],
    true,
    false
);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
if (defined('METACONSOLE')) {
    $table->cellspacing = 0;
    $table->cellpadding = 0;
}

$table->data = [];
$table->head = [];
$table->style = [];

$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';


$table->data[0][0] = __('Type');
$table->data[0][1] = html_print_select(
    alerts_get_alert_templates_types(),
    'search_type',
    $search_type,
    '',
    __('All'),
    '',
    true,
    false,
    false
);
$table->data[0][2] = __('Search');
$table->data[0][3] = html_print_input_text(
    'search_string',
    $search_string,
    '',
    25,
    255,
    true
);
$table->data[0][4] = '<div class="action-buttons">';
$table->data[0][4] .= html_print_submit_button(
    __('Search'),
    '',
    false,
    'class="sub search"',
    true
);
$table->data[0][4] .= '</div>';

if (defined('METACONSOLE')) {
    $filter = '<form class="" method="post" action="'.$url.'">';
    $filter .= html_print_table($table, true);
    $filter .= '</form>';
    ui_toggle($filter, __('Show Options'));
} else {
    echo '<form method="post" action="'.$url.'">';
    html_print_table($table);
    echo '</form>';
}

unset($table);

$filter = [];
if ($search_type != '') {
    $filter['type'] = $search_type;
}

if ($search_string) {
    $filter[] = "(name LIKE '%".$search_string."%' OR description LIKE '%".$search_string."%' OR value LIKE '%".$search_string."%')";
}

$total_templates = alerts_get_alert_templates($filter, ['COUNT(*) AS total']);
$total_templates = $total_templates[0]['total'];
$filter['offset'] = (int) get_parameter('offset');
$filter['limit'] = (int) $config['block_size'];
if (!is_user_admin($config['id_user'])) {
    $filter['id_group'] = array_keys(users_get_groups(false, 'LM'));
}

$templates = alerts_get_alert_templates(
    $filter,
    [
        'id',
        'name',
        'description',
        'type',
        'id_group',
    ]
);
if ($templates === false) {
    $templates = [];
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->data = [];
$table->head = [];
$table->head[0] = __('Name');
$table->head[1] = __('Group');
// $table->head[2] = __('Description');
$table->head[3] = __('Type');
$table->head[4] = __('Op.');
$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[4] = '85px';
$table->align = [];
$table->align[1] = 'left';
$table->align[4] = 'left';

$rowPair = true;
$iterator = 0;
foreach ($templates as $template) {
    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $data = [];

    $data[0] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$template['id'].'&pure='.$pure.'">'.$template['name'].'</a>';

    $data[1] = ui_print_group_icon($template['id_group'], true);
    $data[3] = alerts_get_alert_templates_type_name($template['type']);

    if (check_acl($config['id_user'], $template['id_group'], 'LM')) {
        $table->cellclass[][4] = 'action_buttons';
        $data[4] = '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'" style="display: inline; float: left">';
        $data[4] .= html_print_input_hidden('duplicate_template', 1, true);
        $data[4] .= html_print_input_hidden('source_id', $template['id'], true);
        $data[4] .= html_print_input_image('dup', 'images/copy.png', 1, '', true, ['title' => __('Duplicate')]);
        $data[4] .= '</form> ';

        $data[4] .= '<form method="post" style="display: inline; float: right" onsubmit="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
        $data[4] .= html_print_input_hidden('delete_template', 1, true);
        $data[4] .= html_print_input_hidden('id', $template['id'], true);
        $data[4] .= html_print_input_image('del', 'images/cross.png', 1, '', true, ['title' => __('Delete')]);
        $data[4] .= '</form> ';
    } else {
        $data[4] = '';
    }

    array_push($table->data, $data);
}

ui_pagination($total_templates, $url);
if (isset($data)) {
    html_print_table($table);
    ui_pagination($total_templates, $url, 0, 0, false, 'offset', true, 'pagination-bottom');
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alert templates defined') ]);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'">';
html_print_submit_button(__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden('create_alert', 1);
echo '</form>';
echo '</div>';

enterprise_hook('close_meta_frame');
