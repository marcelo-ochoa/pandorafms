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

require_once 'include/functions_reports.php';

$linkReport = false;
$searchReports = check_acl($config['id_user'], 0, 'RR');

if (check_acl($config['id_user'], 0, 'IW')) {
    $linkReport = true;
}

if ($reports === false || !$searchReports) {
        echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
} else {
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'databox';

    $table->head = [];
    $table->head[0] = __('Report name');
    $table->head[1] = __('Description');
    $table->head[2] = __('HTML');
    $table->head[3] = __('XML');
    enterprise_hook('load_custom_reporting_1', [$table]);

    $table->align = [];
    $table->align[2] = 'center';
    $table->align[3] = 'center';
    $table->align[4] = 'center';

    $table->data = [];
    foreach ($reports as $report) {
        if ($linkReport) {
            $reportstring = "<a href='?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit&id_report=".$report['id_report']."' title='".__('Edit')."'>".$report['name'].'</a>';
        } else {
            $reportstring = $report['name'];
        }

        $data = [
            $reportstring,
            $report['description'],
            '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'">'.html_print_image('images/reporting.png', true).'</a>',
            '<a href="ajax.php?page=operation/reporting/reporting_xml&id='.$report['id_report'].'">'.html_print_image('images/database_lightning.png', true).'</a>',
        ];
        enterprise_hook('load_custom_reporting_2');

        array_push($table->data, $data);
    }

    echo '<br />';
    ui_pagination($totalReports);
    html_print_table($table);
    unset($table);
    ui_pagination($totalReports);
}
