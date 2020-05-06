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
// Login check
global $config;

check_login();

enterprise_hook('open_meta_frame');
$id_report = (int) get_parameter('id');

if (! $id_report) {
    db_pandora_audit(
        'HACK Attempt',
        'Trying to access report viewer withoud ID'
    );
    include 'general/noaccess.php';
    return;
}

// Include with the functions to calculate each kind of report.
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_reporting_html.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_reporting.php');


if (!reporting_user_can_see_report($id_report)) {
    db_pandora_audit('ACL Violation', 'Trying to access report viewer');
    include 'general/noaccess.php';
    exit;
}

// Get different date to search the report.
$date = (string) get_parameter('date', date(DATE_FORMAT));
$time = (string) get_parameter('time', date(TIME_FORMAT));

$datetime = strtotime($date.' '.$time);

// Calculations in order to modify init date of the report
$date_init_less = (strtotime(date('Y-m-j')) - SECONDS_1DAY);
$date_init = get_parameter('date_init', date(DATE_FORMAT, $date_init_less));
$time_init = get_parameter('time_init', date(TIME_FORMAT, $date_init_less));
$datetime_init = strtotime($date_init.' '.$time_init);
$enable_init_date = get_parameter('enable_init_date', 0);
$pure = (int) get_parameter('pure', 0);

$period = null;
// Calculate new inteval for all reports
if ($enable_init_date) {
    if ($datetime_init >= $datetime) {
        $datetime_init = $date_init_less;
    }

    $period = ($datetime - $datetime_init);
}


// ------------------- INIT HEADER --------------------------------------
$url = "index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report&date=$date&time=$time&pure=$pure";

$options = [];

$options['list_reports'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image(
        'images/report_list.png',
        true,
        ['title' => __('Report list')]
    ).'</a>',
];

if (check_acl($config['id_user'], 0, 'RW')) {
    $options['main']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=edit&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        ['title' => __('Main data')]
    ).'</a>';

    $options['list_items']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/list.png',
        true,
        ['title' => __('List items')]
    ).'</a>';

    $options['item_editor']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=new&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/pen.png',
        true,
        ['title' => __('Item editor')]
    ).'</a>';

    if (enterprise_installed()) {
        $options = reporting_enterprise_add_Tabs($options, $id_report);
    }
}

$options['view'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$id_report.'&pure='.$pure.'">'.html_print_image('images/operation.png', true, ['title' => __('View report')]).'</a>',
];

if (!defined('METACONSOLE')) {
    if ($config['pure'] == 0) {
        $options['screen']['text'] = "<a href='$url&pure=1&enable_init_date=$enable_init_date&date_init=$date_init&time_init=$time_init'>".html_print_image('images/full_screen.png', true, ['title' => __('Full screen mode')]).'</a>';
    } else {
        $options['screen']['text'] = "<a href='$url&pure=0&enable_init_date=$enable_init_date&date_init=$date_init&time_init=$time_init'>".html_print_image('images/normal_screen.png', true, ['title' => __('Back to normal mode')]).'</a>';

        // In full screen, the manage options are not available
        $options = [
            'view'   => $options['view'],
            'screen' => $options['screen'],
        ];
    }
}

// Page header for metaconsole
if (is_metaconsole()) {
    // Bread crumbs
    ui_meta_add_breadcrumb(['link' => 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder', 'text' => __('Reporting')]);

    ui_meta_print_page_header($nav_bar);

    // Print header
    ui_meta_print_header(__('Reporting'), '', $options);
} else {
    ui_print_page_header(
        reporting_get_name($id_report),
        'images/op_reporting.png',
        false,
        '',
        false,
        $options,
        false,
        '',
        55
    );
}

// ------------------- END HEADER ---------------------------------------
// ------------------------ INIT FORM -----------------------------------
$table = new stdClass();
$table->id = 'controls_table';
$table->width = '100%';
$table->class = 'databox';
if (defined('METACONSOLE')) {
    $table->width = '100%';
    $table->class = 'databox filters';

    $table->head[0] = __('View Report');
    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

$table->style = [];
$table->style[0] = 'width: 60px;';
$table->rowspan[0][0] = 2;

// Set initial conditions for these controls, later will be modified by javascript
if (!$enable_init_date) {
    $table->style[1] = 'display: none';
    $table->style[2] = 'display: flex;align-items: baseline;';
    $display_to = 'none';
    $display_item = '';
} else {
    $table->style[1] = 'display: "block"';
    $table->style[2] = 'display: flex;align-items: baseline;';
    $display_to = '';
    $display_item = 'none';
}

$table->size = [];
$table->size[0] = '60px';
$table->colspan[0][1] = 2;
$table->style[0] = 'text-align:center;';
$table->data = [];
$table->data[0][0] = html_print_image(
    'images/reporting32.png',
    true,
    [
        'width'  => '32',
        'height' => '32',
    ]
);

if (reporting_get_description($id_report)) {
    $table->data[0][1] = '<div style="float:left">'.reporting_get_description($id_report).'</div>';
} else {
    $table->data[0][1] = '<div style="float:left">'.reporting_get_name($id_report).'</div>';
}

$table->data[0][1] .= '<div style="text-align:right; width:100%; margin-right:50px">'.__('Set initial date').html_print_checkbox('enable_init_date', 1, $enable_init_date, true);
$html_enterprise = enterprise_hook(
    'reporting_print_button_PDF',
    [$id_report]
);
if ($html_enterprise !== ENTERPRISE_NOT_HOOK) {
    $table->data[0][1] .= $html_enterprise;
}

$table->data[0][1] .= '</div>';

$table->data[1][1] = '<div>'.__('From').': </div>';
$table->data[1][1] .= html_print_input_text('date_init', $date_init, '', 12, 10, true).' ';
$table->data[1][1] .= html_print_input_text('time_init', $time_init, '', 10, 7, true).' ';
$table->data[1][2] = '<div style="display:'.$display_item.'" id="string_items">'.__('Items period before').':</div>';
$table->data[1][2] .= '<div style="display:'.$display_to.'" id="string_to">'.__('to').':</div>';
$table->data[1][2] .= html_print_input_text('date', $date, '', 12, 10, true).' ';
$table->data[1][2] .= html_print_input_text('time', $time, '', 10, 7, true).' ';
$table->data[1][2] .= html_print_submit_button(__('Update'), 'date_submit', false, 'class="sub next"', true);

echo '<form method="post" action="'.$url.'&pure='.$config['pure'].'" style="margin-right: 0px;">';
html_print_table($table);
html_print_input_hidden('id_report', $id_report);
echo '</form>';
// ------------------------ END FORM ------------------------------------
if ($enable_init_date) {
    if ($datetime_init > $datetime) {
        ui_print_error_message(
            __('Invalid date selected. Initial date must be before end date.')
        );
    }
}

$report = reporting_make_reporting_data(
    null,
    $id_report,
    $date,
    $time,
    $period,
    'dinamic'
);
for ($i = 0; $i < sizeof($report['contents']); $i++) {
    $report['contents'][$i]['description'] = str_replace('&#x0d;&#x0a;', '<br/>', $report['contents'][$i]['description']);
}

reporting_html_print_report($report, false, $config['custom_report_info']);


// ----------------------------------------------------------------------
// The rowspan of the first row is only 2 in controls table. Why is used the same code here and in the items??
$table->rowspan[0][0] = 1;

echo '<div id="loading" style="text-align: center;">';
echo html_print_image('images/wait.gif', true, ['border' => '0']);
echo '<strong>'.__('Loading').'...</strong>';
echo '</div>';

/*
 * We must add javascript here. Otherwise, the date picker won't
 * work if the date is not correct because php is returning.
 */

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
    
    $("#loading").slideUp ();
    $("#text-time").timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'});
    
    $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
    
    $("#text-date").datepicker({
        dateFormat: "<?php echo DATE_FORMAT_JS; ?>",
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown"});
    
    
    $('[id^=text-time_init]').timepicker({
        showSecond: true,
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>'});
    
    $('[id^=text-date_init]').datepicker ({
        dateFormat: "<?php echo DATE_FORMAT_JS; ?>",
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown"});
    
    
    $("*", "#controls_table-0").css("display", ""); //Re-show the first row of form.
    
    /* Show/hide begin date reports controls */
    $("#checkbox-enable_init_date").click(function() {
        flag = $("#checkbox-enable_init_date").is(':checked');
        if (flag == true) {
            $("#controls_table-1-1").css("display", "");
            $("#controls_table-1-2").css("display", "");
            $("#string_to").show();
            $("#string_items").hide();
        }
        else {
            $("#controls_table-1-1").css("display", "none");
            $("#controls_table-1-2").css("display", "");
            $("#string_to").hide();
            $("#string_items").show();
        }
    });
    
});
</script>

<?php
if ($datetime === false || $datetime == -1) {
    ui_print_error_message(__('Invalid date selected'));
    return;
}

enterprise_hook('close_meta_frame');

