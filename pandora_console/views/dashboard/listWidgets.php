<?php
/**
 * Dashboards View List Widget Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';

$form = [
    'id'       => 'form-search-widget',
    'action'   => $url,
    'onsubmit' => 'return false;',
    'class'    => 'modal-dashboard',
    'enctype'  => 'multipart/form-data',
    'method'   => 'POST',
];

$inputs = [
    [
        'id'        => 'search_input_widget',
        'arguments' => [
            'type'      => 'text',
            'name'      => 'search-widget',
            'value'     => $search,
            'size'      => '30',
            'class'     => 'search_input',
            'autofocus' => true,
        ],
    ],
];

$html = new HTML();
$html->printForm(
    [
        'form'   => $form,
        'inputs' => $inputs,
    ]
);

ui_pagination($total, '#', $offset, 9);

$output = '<div class="container-list-widgets" >';

foreach ($widgets as $widget) {
    $urlWidgets = $config['homedir'];
    $urlWidgets .= '/include/lib/Dashboard/Widgets/';
    $urlWidgets .= $widget['unique_name'];
    $urlWidgets .= '.php';
    if (\file_exists($urlWidgets) === false) {
        continue;
    }

    $imageWidget = '/images/widgets/'.$widget['unique_name'].'.png';

    $output .= '<div class="list-widgets">';
    $output .= '<div class="list-widgets-image">';
    $output .= \html_print_image(
        $imageWidget,
        true,
        [
            'id'    => 'img-add-widget-'.$widget['id'],
            'class' => 'img-add-widget',
            'alt'   => __('Add widget'),
        ],
        false,
        false,
        false,
        true
    );
    $output .= '</div>';
    $output .= '<div class="list-widgets-description">';
    $output .= $widget['description'];
    $output .= '</div>';
    $output .= '</div>';
}

$output .= '</div>';
echo $output;
