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
// Login check
global $config;

check_login();

// Visual console required.
if (empty($visualConsole)) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

$strict_user = db_get_value(
    'strict_acl',
    'tusuario',
    'id_user',
    $config['id_user']
);

// ACL for the existing visual console
// if (!isset($vconsole_read))
// $vconsole_read = check_acl ($config['id_user'], $visualConsole['id_group'], "VR");
if (!isset($vconsole_write)) {
    $vconsole_write = check_acl(
        $config['id_user'],
        $visualConsole['id_group'],
        'VW'
    );
}

if (!isset($vconsole_manage)) {
    $vconsole_manage = check_acl(
        $config['id_user'],
        $visualConsole['id_group'],
        'VM'
    );
}

if (!$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_visual_map.php';
require_once $config['homedir'].'/include/functions_agents.php';

$table = new stdClass();
$table->id = 'wizard_table';
$table->head = [];
if (!is_metaconsole()) {
    $metaconsole_hack = '';
    $table->width = '100%';
    $table->class = 'databox filters';
} else {
    $metaconsole_hack = '../../';
    $table->width = '100%';
    $table->class = 'databox filters';
    $table->styleTable = ' margin-top:0px';
    include_once $config['homedir'].'/enterprise/meta/include/functions_html_meta.php';
}

$table->style = [];

$table->style[0] = 'font-weight: bold; ';

$table->style[2] = 'font-weight: bold; ';

$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '15%';
$table->size[2] = '15%';
$table->size[3] = '15%';


$table->align = [];
$table->align[0] = 'left';
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';

$table->data = [];

$images_list = [];
$all_images = list_files(
    $config['homedir'].'/images/console/icons/',
    'png',
    1,
    0
);
foreach ($all_images as $image_file) {
    if (strpos($image_file, '_bad')) {
        continue;
    }

    if (strpos($image_file, '_ok')) {
        continue;
    }

    if (strpos($image_file, '_warning')) {
        continue;
    }

    $image_file = substr($image_file, 0, (strlen($image_file) - 4));
    $images_list[$image_file] = $image_file;
}

$type_list = [
    STATIC_GRAPH   => __('Static Graph'),
    PERCENTILE_BAR => __('Percentile Item'),
    MODULE_GRAPH   => __('Module graph'),
    SIMPLE_VALUE   => __('Simple value'),
];


$table->rowstyle['all_0'] = 'display: none;';
$table->data['all_0'][0] = __('Type');
$table->colspan['all_0'][1] = '3';
$table->data['all_0'][1] = html_print_select(
    $type_list,
    'type',
    '',
    'hidden_rows()',
    '',
    '',
    true,
    false,
    false
);


$table->rowstyle['staticgraph'] = 'display: none;';
$table->data['staticgraph'][0] = __('Image');
$table->colspan['staticgraph'][1] = '3';
$table->data['staticgraph'][1] = html_print_select(
    $images_list,
    'image',
    '',
    '',
    '',
    '',
    true
);


$table->rowstyle['all_1'] = 'display: none;';
$table->data['all_1'][0] = __('Range between elements (px)');
$table->colspan['all_1'][1] = '3';
$table->data['all_1'][1] = html_print_input_text(
    'range',
    50,
    '',
    5,
    5,
    true
);


$table->rowstyle['staticgraph_modulegraph'] = 'display: none;';
$table->data['staticgraph_modulegraph'][0] = __('Size (px)');
$table->colspan['staticgraph_modulegraph'][1] = '3';
$table->data['staticgraph_modulegraph'][1] = __('Width').': '.html_print_input_text('width', 0, '', 5, 5, true);
$table->data['staticgraph_modulegraph'][1] .= '&nbsp;&nbsp;&nbsp;'.__('Height').': '.html_print_input_text('height', 0, '', 5, 5, true);

$fontf = [
    'Roboto'       => 'Roboto',
    'lato'         => 'Lato',
    'opensans'     => 'Open Sans',
    'nunito'       => 'Nunito',
    'leaguegothic' => 'League Gothic',
];

$fonts = [
    '4pt'   => '4pt',
    '6pt'   => '6pt',
    '8pt'   => '8pt',
    '10pt'  => '10pt',
    '12pt'  => '12pt',
    '14pt'  => '14pt',
    '18pt'  => '18pt',
    '24pt'  => '24pt',
    '28pt'  => '28pt',
    '36pt'  => '36pt',
    '48pt'  => '48pt',
    '60pt'  => '60pt',
    '72pt'  => '72pt',
    '84pt'  => '84pt',
    '96pt'  => '96pt',
    '116pt' => '116pt',
    '128pt' => '128pt',
    '140pt' => '140pt',
    '154pt' => '154pt',
    '196pt' => '196pt',
];

/*
    $fontf = array('andale mono,times' => 'Andale Mono',
    'arial,helvetica,sans-serif' => 'Arial',
    'arial black,avant garde' => 'Arial Black',
    'comic sans ms,sans-serif' => 'Comic Sans MS',
    'courier new,courier' => 'Courier New',
    'georgia,palatino' => 'Georgia',
    'helvetica,impact' => 'Helvetica',
    'impact,chicago' => 'Impact',
    'symbol' => 'Symbol',
    'tahoma,arial,helvetica,sans-serif' => 'Tahoma',
    'terminal,monaco' => 'Terminal',
    'times new roman,times' => 'Times New Roman',
    'trebuchet ms,geneva' => 'Trebuchet MS',
    'verdana,geneva' => 'Verdana',
    'Webdings' => 'Webdings',
    'Wingdings'  => 'Wingdings'
    );
*/

$table->rowstyle['all_9'] = 'display: none;';
$table->data['all_9'][0] = __('Font');
$table->colspan['all_9'][1] = '3';
$table->data['all_9'][1] = html_print_select(
    $fontf,
    'fontf',
    $fontf['Roboto'],
    '',
    '',
    '',
    true
);

$table->rowstyle['all_10'] = 'display: none;';
$table->data['all_10'][0] = __('Font size');
$table->colspan['all_10'][1] = '3';
$table->data['all_10'][1] = html_print_select(
    $fonts,
    'fonts',
    $fonts['12pt'],
    '',
    '',
    '',
    true
);


$table->rowstyle['modulegraph_simplevalue'] = 'display: none;';
$table->data['modulegraph_simplevalue'][0] = __('Period');
$table->colspan['modulegraph_simplevalue'][1] = '3';
$table->data['modulegraph_simplevalue'][1] = html_print_extended_select_for_time(
    'period',
    '',
    '',
    '',
    '',
    false,
    true
);


$table->rowstyle['simplevalue'] = 'display: none;';
$table->data['simplevalue'][0] = __('Process');
$table->data['simplevalue'][1] = html_print_select(
    [
        PROCESS_VALUE_MIN => __('Min value'),
        PROCESS_VALUE_MAX => __('Max value'),
        PROCESS_VALUE_AVG => __('Avg value'),
    ],
    'process_value',
    PROCESS_VALUE_AVG,
    '',
    __('None'),
    PROCESS_VALUE_NONE,
    true
);


$table->rowstyle['percentileitem_1'] = 'display: none;';
$table->data['percentileitem_1'][0] = __('Width (px)');
$table->data['percentileitem_1'][1] = html_print_input_text('percentileitem_width', 0, '', 5, 5, true);


$table->rowstyle['percentileitem_2'] = 'display: none;';
$table->data['percentileitem_2'][0] = __('Max value');
$table->data['percentileitem_2'][1] = html_print_input_text('max_value', 0, '', 5, 5, true);


$table->rowstyle['percentileitem_3'] = 'display: none;';
$table->data['percentileitem_3'][0] = __('Type');
$table->colspan['percentileitem_3'][1] = '3';
$table->data['percentileitem_3'][1] = __('Percentile').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'type_percentile',
    'percentile',
    '',
    '',
    false,
    '',
    '',
    true
).'&nbsp;&nbsp;';
$table->data['percentileitem_3'][1] .= __('Bubble').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'type_percentile',
    'bubble',
    '',
    '',
    false,
    '',
    '',
    true
).'&nbsp;&nbsp;';

$table->rowstyle['percentileitem_4'] = 'display: none;';
$table->data['percentileitem_4'][0] = __('Value to show');
$table->colspan['percentileitem_4'][1] = '3';
$table->data['percentileitem_4'][1] = __('Percent').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'value_show',
    'percent',
    '',
    '',
    false,
    '',
    '',
    true
).'&nbsp;&nbsp;';
$table->data['percentileitem_4'][1] .= __('Value').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'value_show',
    'value',
    '',
    '',
    false,
    '',
    '',
    true
).'&nbsp;&nbsp;';


if (is_metaconsole()) {
    $table->rowstyle['all_2'] = 'display: none;';
    $table->data['all_2'][0] = __('Servers');
    if ($strict_user) {
        $table->data['all_2'][1] = html_print_select(
            '',
            'servers',
            '',
            'metaconsole_init();',
            __('All'),
            '0',
            true
        );
    } else {
            $sql = 'SELECT id, server_name
        FROM tmetaconsole_setup';
    }

    $table->data['all_2'][1] = html_print_select_from_sql(
        $sql,
        'servers',
        '',
        'metaconsole_init();',
        __('All'),
        '0',
        true
    );
}


$table->rowstyle['all_3'] = 'display: none;';
$table->data['all_3'][0] = __('Groups');
$table->colspan['all_3'][1] = '3';
$table->data['all_3'][1] = html_print_select_groups(
    $config['id_user'],
    'AR',
    true,
    'groups',
    '',
    '',
    '',
    0,
    true
);


$table->rowstyle['all_one_item_per_agent'] = 'display: none';
$table->data['all_one_item_per_agent'][0] = __('One item per agent');
$table->colspan['all_one_item_per_agent'][1] = '3';
$table->data['all_one_item_per_agent'][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'item_per_agent',
    1,
    '',
    '',
    false,
    'item_per_agent_change(1)',
    '',
    true
).'&nbsp;&nbsp;';
$table->data['all_one_item_per_agent'][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended(
    'item_per_agent',
    0,
    '',
    0,
    false,
    'item_per_agent_change(0)',
    '',
    true
);
$table->data['all_one_item_per_agent'][1] .= html_print_input_hidden(
    'item_per_agent_test',
    0,
    true
);


$table->rowstyle['all_4'] = 'display: none;';
$table->data['all_4'][0] = __('Agents').ui_print_help_tip(__('If you select several agents, only the common modules will be displayed'), true);

$agents_list = [];
if (!is_metaconsole()) {
    $agents_list = agents_get_group_agents(
        0,
        false,
        'none',
        false,
        true
    );
}


$table->data['all_4'][1] = html_print_select(
    $agents_list,
    'id_agents[]',
    0,
    false,
    '',
    '',
    true,
    true
);
$table->data['all_4'][2] = ' <span style="vertical-align: top;">'.__('Modules').'</span>';
$table->data['all_4'][3] = html_print_select(
    [],
    'module[]',
    0,
    false,
    __('None'),
    -1,
    true,
    true
);


$table->rowstyle['all_6'] = 'display: none;';
$table->data['all_6'][0] = __('Label');
$label_type = [
    'agent_module' => __('Agent - Module'),
    'module'       => __('Module'),
    'agent'        => __('Agent'),
    'none'         => __('None'),
];
$table->colspan['all_6'][1] = '3';
$table->data['all_6'][1] = html_print_select(
    $label_type,
    'label_type',
    'agent_module',
    '',
    '',
    '',
    true
);


$table->data['all_7'][0] = __('Enable link agent');
$table->colspan['all_7'][1] = '3';
$table->data['all_7'][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended('enable_link', 1, '', 1, false, '', '', true).'&nbsp;&nbsp;';
$table->data['all_7'][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended('enable_link', 0, '', 1, false, '', '', true);


$parents = visual_map_get_items_parents($visualConsole['id']);
if (empty($parents)) {
    $parents = [];
}

$table->data['all_8'][0] = __('Set Parent');
$table->data['all_8'][1] = html_print_select(
    [
        VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP            => __('Item created in the visualmap'),
        VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP => __('Use the agents relationship (from selected agents)'),
    ],
    'kind_relationship',
    0,
    '',
    __('None'),
    VISUAL_MAP_WIZARD_PARENTS_NONE,
    true
);
$table->data['all_8'][2] = '<span id="parent_column_2_item_in_visual_map">'.__('Item in the map').'</span><span id="parent_column_2_relationship">'.ui_print_help_tip(
    __('The parenting relationships in %s will be drawn on the map.', get_product_name()),
    true
).'</span>';
$table->data['all_8'][3] = '<span id="parent_column_3_item_in_visual_map">'.html_print_select(
    $parents,
    'item_in_the_map',
    0,
    '',
    __('None'),
    0,
    true
).'</span>';



if (is_metaconsole()) {
    $pure = get_parameter('pure', 0);

    echo '<form method="post"
		action="index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&tab=wizard&id_visual_console='.$visualConsole['id'].'"
		onsubmit="if (! confirm(\''.__('Are you sure to add many elements\nin visual map?').'\')) return false; else return check_fields();">';
} else {
    echo '<form method="post"
		action="index.php?sec=network&sec2=godmode/reporting/visual_console_builder&tab='.$activeTab.'&id_visual_console='.$visualConsole['id'].'"
		onsubmit="if (! confirm(\''.__('Are you sure to add many elements\nin visual map?').'\')) return false; else return check_fields();">';
}

if (defined('METACONSOLE')) {
    echo "<div class='title_tactical' style='margin-top: 15px; '>".__('Wizard').'</div>';
}

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if (is_metaconsole()) {
    html_print_input_hidden('action2', 'update');
} else {
    html_print_input_hidden('action', 'update');
}

html_print_input_hidden('id_visual_console', $visualConsole['id']);
html_print_submit_button(__('Add'), 'go', false, 'class="sub wizard wand"');
echo '</div>';
echo '</form>';

// Trick for it have a traduct text for javascript.
echo '<span id="any_text" style="display: none;">'.__('Any').'</span>';
echo '<span id="none_text" style="display: none;">'.__('None').'</span>';
echo '<span id="loading_text" style="display: none;">'.__('Loading...').'</span>';
?>
<script type="text/javascript">

var metaconsole_enabled = <?php echo (int) is_metaconsole(); ?>;
var show_only_enabled_modules = true;
var url_ajax = "ajax.php";

if (metaconsole_enabled) {
    url_ajax = "../../ajax.php";
}

$(document).ready (function () {
    var noneText = $("#none_text").html(); //Trick for catch the translate text.
    
    hidden_rows();
    
    $("#process_value").change(function () {
        selected = $("#process_value").val();
        
        if (selected == <?php echo PROCESS_VALUE_NONE; ?>) {
            $("tr", "#wizard_table").filter(function () {
                return /^.*modulegraph_simplevalue.*/.test(this.id);
            }).hide();
        }
        else {
            $("tr", "#wizard_table").filter(function () {
                return /^.*modulegraph_simplevalue.*/.test(this.id);
            }).show();
        }
    });
    
    $("#groups").change (function () {
        $('#module')
            .prop('disabled', true)
            .empty()
            .append($('<option></option>')
                .html(noneText)
                .attr("None", "")
                .attr('value', -1)
                .prop('selected', true));
        
        $('#id_agents')
            .prop('disabled', true)
            .empty ()
            .css ("width", "auto")
            .css ("max-width", "")
            .append ($('<option></option>').html($("#loading_text").html()));
        
        var data_params = {
            page: "include/ajax/agent",
            get_agents_group: 1,
            id_group: $("#groups").val(),
            serialized: 1,
            mode: "json"
        };
        
        if (metaconsole_enabled)
            data_params.id_server = $("#servers").val();
        
        jQuery.ajax ({
            data: data_params,
            type: 'POST',
            url: url_ajax,
            dataType: 'json',
            success: function (data) {
                $('#id_agents').empty();
                
                if (isEmptyObject(data)) {
                    $('#id_agents')
                        .append($('<option></option>')
                            .html(noneText)
                            .attr("None", "")
                            .attr('value', -1)
                            .prop('selected', true));
                }
                else {
                    jQuery.each (data, function (i, val) {
                        var s = js_html_entity_decode(val);
                        $('#id_agents')
                            .append($('<option></option>')
                                .html(s).attr("value", i));
                    });
                }
                
                $('#id_agents').prop('disabled', false);
            }
        });
    });
    
    $("#id_agents").change ( function() {
        if ($("#hidden-item_per_agent_test").val() == 0) {
            var options = {};
            
            if (metaconsole_enabled) {
                options = {
                    'data': {
                        'id_server': 'servers',
                        'metaconsole': 1,
                        'homedir': '../../'
                    }
                };
            }
            
            agent_changed_by_multiple_agents(options);
        }
    });
    
    if (metaconsole_enabled) {
        metaconsole_init();
    }
    
    $("select[name='kind_relationship']").change(function() {
    
        if ($("input[name='item_per_agent']:checked").val() == "0") {
            $("select[name='kind_relationship'] option[value=<?php echo VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP; ?>]")
                .attr('disabled', true)
        }
        
        switch ($("select[name='kind_relationship']").val()) {
            case "<?php echo VISUAL_MAP_WIZARD_PARENTS_NONE; ?>":
                $("#parent_column_2_item_in_visual_map").hide();
                $("#parent_column_3_item_in_visual_map").hide();
                $("#parent_column_2_relationship").hide();
                break;
            case "<?php echo VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP; ?>":
                $("#parent_column_2_relationship").hide();
                $("#parent_column_2_item_in_visual_map").show();
                $("#parent_column_3_item_in_visual_map").show();
                break;
            case "<?php echo VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP; ?>":
                $("#parent_column_2_item_in_visual_map").hide();
                $("#parent_column_3_item_in_visual_map").hide();
                $("#parent_column_2_relationship").show();
                break;
        }
    });
    //Force in the load
    $("select[name='kind_relationship']").trigger('change');
    item_per_agent_change(0);
});

function check_fields() {
    switch ($("#type").val()) {
        case "<?php echo PERCENTILE_BAR; ?>":
        case "<?php echo MODULE_GRAPH; ?>":
        case "<?php echo SIMPLE_VALUE; ?>":
            if (($("#module").val() == "-1") || ($("#module").val() == null)) {
                alert("<?php echo __('Please select any module or modules.'); ?>");
                return false;
            }
            else {
                return true;
            }
            break;
        default:
            return true;
            break;
    }
}

function hidden_rows() {
    $("tr", "#wizard_table").hide(); //Hide all in the form table
    
    //Show the id ".*-all_.*"
    $("tr", "#wizard_table")
        .filter(function () {return /^wizard_table\-all.*/.test(this.id); }).show();
    
    switch ($("#type").val()) {
        case "<?php echo STATIC_GRAPH; ?>":
            $("tr", "#wizard_table").filter(function () {return /^.*staticgraph.*/.test(this.id); }).show();
            break;
        case "<?php echo PERCENTILE_BAR; ?>":
            $("tr", "#wizard_table").filter(function () {return /^.*percentileitem.*/.test(this.id); }).show();
            break;
        case "<?php echo MODULE_GRAPH; ?>":
            $("tr", "#wizard_table").filter(function () {return /^.*modulegraph.*/.test(this.id); }).show();
            break;
        case "<?php echo SIMPLE_VALUE; ?>":
            $("tr", "#wizard_table").filter(function () {return /^.*simplevalue.*/.test(this.id); }).show();
            break;
    }
}

function item_per_agent_change(itemPerAgent) {
    
    // Disable Module select
    if (itemPerAgent == 1) {
        $("select[name='kind_relationship'] option[value=<?php echo VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP; ?>]")
            .attr('disabled', false);
        
        $('#module').empty();
        $('#module')
            .append($('<option></option>')
                .html (<?php echo "'".__('None')."'"; ?>)
                .attr("value", -1));
        $('#module').attr('disabled', true);
        $('#label_type').empty();
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('Agent')."'"; ?>)
                .attr('value', 'agent').prop('selected', true));
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('None')."'"; ?>)
                .attr('value', 'none'));
        
        $('#hidden-item_per_agent_test').val(1);
    }
    else {
        if ($("select[name='kind_relationship']").val() == <?php echo VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP; ?>) {
            $("select[name='kind_relationship']").val(
                <?php echo VISUAL_MAP_WIZARD_PARENTS_NONE; ?>);
        }
        $("select[name='kind_relationship'] option[value=<?php echo VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP; ?>]")
            .attr('disabled', true);
        
        
        $('#module').removeAttr('disabled');
        $('#hidden-item_per_agent_test').val(0);
        $('#label_type').empty();
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('Agent')."'"; ?>)
                .attr('value', 'agent'));
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('Agent - Module')."'"; ?>)
                .attr('value', 'agent_module')
                .prop('selected', true));
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('Module')."'"; ?>)
                .attr('value', 'module'));
        $('#label_type')
            .append($('<option></option>')
                .html(<?php echo "'".__('None')."'"; ?>)
                .attr('value', 'none'));
    
    }
}

function metaconsole_init() {
    $("#groups").change();
}
</script>
<style type="text/css">
    select[name='kind_relationship'] option[disabled='disabled'] {
        color: red;
        text-decoration: line-through;
    }
</style>
