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

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_netflow.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

check_login();

enterprise_hook('open_meta_frame');

// Fix: Netflow have to check RW ACL
if (! check_acl($config['id_user'], 0, 'RW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );
    include $config['homedir'].'/general/noaccess.php';
    return;
}

$id = (int) get_parameter('id');
$name = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
$update = (string) get_parameter('update', 0);
$create = (string) get_parameter('create', 0);

$pure = get_parameter('pure', 0);

if ($id) {
    $permission = netflow_check_filter_group($id);
    if (!$permission) {
        // no tiene permisos para acceder a un filtro
        include $config['homedir'].'/general/noaccess.php';
        return;
    }
}

// Header
if (! defined('METACONSOLE')) {
    $buttons['edit']['text'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit">'.html_print_image('images/list.png', true, ['title' => __('Filter list')]).'</a>';

    $buttons['add']['text'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit_form">'.html_print_image('images/add_mc.png', true, ['title' => __('Add filter')]).'</a>';

    ui_print_page_header(
        __('Netflow Filter'),
        'images/gm_netflow.png',
        false,
        'pcap_filter',
        true,
        $buttons
    );
} else {
    $nav_bar = [
        [
            'link' => 'index.php?sec=main',
            'text' => __('Main'),
        ],
        [
            'link' => 'index.php?sec=netf&sec2=godmode/netflow/nf_edit',
            'text' => __('Netflow filters'),
        ],
        [
            'link' => 'index.php?sec=netf&sec2=godmode/netflow/nf_edit_form',
            'text' => __('Add filter'),
        ],
    ];

    ui_meta_print_page_header($nav_bar);

    ui_meta_print_header(__('Netflow filters'));
}

if ($id) {
    $filter = netflow_filter_get_filter($id);
    $assign_group = $filter['id_group'];
    $name = $filter['id_name'];
    $ip_dst = $filter['ip_dst'];
    $ip_src = $filter['ip_src'];
    $dst_port = $filter['dst_port'];
    $src_port = $filter['src_port'];
    $aggregate = $filter['aggregate'];
    $advanced_filter = $filter['advanced_filter'];
} else {
    $name = '';
    $assign_group = '';
    $ip_dst = '';
    $ip_src = '';
    $dst_port = '';
    $src_port = '';
    $aggregate = 'dstip';
    $advanced_filter = '';
}

if ($update) {
    $name = (string) get_parameter('name');
    $assign_group = (int) get_parameter('assign_group');
    $aggregate = get_parameter('aggregate', '');
    $ip_dst = get_parameter('ip_dst', '');
    $ip_src = get_parameter('ip_src', '');
    $dst_port = get_parameter('dst_port', '');
    $src_port = get_parameter('src_port', '');
    $advanced_filter = get_parameter('advanced_filter', '');

    if ($name == '') {
        ui_print_error_message(__('Not updated. Blank name'));
    } else {
        $values = [
            'id_sg'           => $id,
            'id_name'         => $name,
            'id_group'        => $assign_group,
            'aggregate'       => $aggregate,
            'ip_dst'          => $ip_dst,
            'ip_src'          => $ip_src,
            'dst_port'        => $dst_port,
            'src_port'        => $src_port,
            'advanced_filter' => $advanced_filter,
        ];

        // Save filter args
        $values['filter_args'] = netflow_get_filter_arguments($values, true);

        $result = db_process_sql_update('tnetflow_filter', $values, ['id_sg' => $id]);

        ui_print_result_message(
            $result,
            __('Successfully updated'),
            __('Not updated. Error updating data')
        );
    }
}

if ($create) {
    $name = (string) get_parameter('name');
    $assign_group = (int) get_parameter('assign_group');
    $aggregate = get_parameter('aggregate', 'dstip');
    $ip_dst = get_parameter('ip_dst', '');
    $ip_src = get_parameter('ip_src', '');
    $dst_port = get_parameter('dst_port', '');
    $src_port = get_parameter('src_port', '');
    $advanced_filter = (string) get_parameter('advanced_filter', '');

    $values = [
        'id_name'         => $name,
        'id_group'        => $assign_group,
        'ip_dst'          => $ip_dst,
        'ip_src'          => $ip_src,
        'dst_port'        => $dst_port,
        'src_port'        => $src_port,
        'aggregate'       => $aggregate,
        'advanced_filter' => $advanced_filter,
    ];

    // Save filter args
    $values['filter_args'] = netflow_get_filter_arguments($values, true);

    $id = db_process_sql_insert('tnetflow_filter', $values);
    if ($id === false) {
        ui_print_error_message('Error creating filter');
    } else {
        ui_print_success_message('Filter created successfully');
    }
}

$table->id = 'table1';
$table->width = '100%';
$table->border = 0;
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->class = 'databox filters';
$table->style[0] = 'font-weight: bold';

if (defined('METACONSOLE')) {
    if ($id) {
        $table->head[0] = __('Update filter');
    } else {
        $table->head[0] = __('Create filter');
    }

    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

$table->data = [];

$table->data[0][0] = '<b>'.__('Name').'</b>';
$table->data[0][1] = html_print_input_text('name', $name, false, 20, 80, true);

$own_info = get_user_info($config['id_user']);
$table->data[1][0] = '<b>'.__('Group').'</b>';
// Fix: Netflow filters have to check RW ACL
$table->data[1][1] = html_print_select_groups(
    $config['id_user'],
    'RW',
    $own_info['is_admin'],
    'assign_group',
    $assign_group,
    '',
    '',
    -1,
    true,
    false,
    false
);

if ($advanced_filter != '') {
    $filter_type = 1;
} else {
    $filter_type = 0;
}

$table->data[2][0] = '<b>'.__('Filter:').'</b>';
$table->data[2][1] = __('Normal').' '.html_print_radio_button_extended('filter_type', 0, '', $filter_type, false, 'displayNormalFilter();', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Advanced').' '.html_print_radio_button_extended('filter_type', 1, '', $filter_type, false, 'displayAdvancedFilter();', 'style="margin-right: 40px;"', true);

$table->data[3][0] = __('Dst Ip').ui_print_help_tip(__('Destination IP. A comma separated list of destination ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true);
$table->data[3][1] = html_print_input_text('ip_dst', $ip_dst, false, 40, 80, true);

$table->data[4][0] = __('Src Ip').ui_print_help_tip(__('Source IP. A comma separated list of source ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true);
$table->data[4][1] = html_print_input_text('ip_src', $ip_src, false, 40, 80, true);

$table->data[5][0] = __('Dst Port').ui_print_help_tip(__('Destination port. A comma separated list of destination ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true);
$table->data[5][1] = html_print_input_text('dst_port', $dst_port, false, 40, 80, true);

$table->data[6][0] = __('Src Port').ui_print_help_tip(__('Source port. A comma separated list of source ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true);
$table->data[6][1] = html_print_input_text('src_port', $src_port, false, 40, 80, true);

$table->data[7][1] = html_print_textarea('advanced_filter', 4, 40, $advanced_filter, '', true);

$table->data[8][0] = '<b>'.__('Aggregate by').'</b>';
$aggregate_list = [
    'srcip'   => __('Src Ip Address'),
    'dstip'   => __('Dst Ip Address'),
    'srcport' => __('Src Port'),
    'dstport' => __('Dst Port'),
];

$table->data[8][1] = html_print_select($aggregate_list, 'aggregate', $aggregate, '', '', 0, true, false, true, '', false);

echo '<form method="post" action="'.$config['homeurl'].'index.php?sec=netf&sec2=godmode/netflow/nf_edit_form&pure='.$pure.'">';
html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('update', 1);
    html_print_input_hidden('id', $id);
    html_print_submit_button(__('Update'), 'crt', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create', 1);
    html_print_submit_button(__('Create'), 'crt', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');

?>

<script type="text/javascript">
    function displayAdvancedFilter () {
        // Erase the normal filter
        document.getElementById("text-ip_dst").value = '';
        document.getElementById("text-ip_src").value = '';
        document.getElementById("text-dst_port").value = '';
        document.getElementById("text-src_port").value = '';
        
        // Hide the normal filter
        document.getElementById("table1-3").style.display = 'none';
        document.getElementById("table1-4").style.display = 'none';
        document.getElementById("table1-5").style.display = 'none';
        document.getElementById("table1-6").style.display = 'none';
        
        // Show the advanced filter
        document.getElementById("table1-7").style.display = '';
    };
    
    function displayNormalFilter () {
        // Erase the advanced filter
        document.getElementById("textarea_advanced_filter").value = '';
        
        // Hide the advanced filter
        document.getElementById("table1-7").style.display = 'none';
        
        // Show the normal filter
        document.getElementById("table1-3").style.display = '';
        document.getElementById("table1-4").style.display = '';
        document.getElementById("table1-5").style.display = '';
        document.getElementById("table1-6").style.display = '';
    };
    
    var filter_type = <?php echo $filter_type; ?>;
    if (filter_type == 0) {
        displayNormalFilter ();
    }
    else {
        displayAdvancedFilter ();
    }
</script>
