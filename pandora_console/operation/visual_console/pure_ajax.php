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
global $config;

// Login check
require_once $config['homedir'].'/include/functions_visual_map.php';

check_login();

if (!defined('METACONSOLE')) {
    $id_layout = (int) get_parameter('id');
} else {
    $id_layout = (int) get_parameter('id_visualmap');
}

if ($id_layout) {
    $default_action = 'edit';
} else {
    $default_action = 'new';
}

if (!defined('METACONSOLE')) {
    $action = get_parameterBetweenListValues(
        'action',
        [
            'new',
            'save',
            'edit',
            'update',
            'delete',
        ],
        $default_action
    );
} else {
    $action = get_parameterBetweenListValues(
        'action2',
        [
            'new',
            'save',
            'edit',
            'update',
            'delete',
        ],
        $default_action
    );
}

$refr = (int) get_parameter('refr', $config['vc_refr']);
$graph_javascript = (bool) get_parameter('graph_javascript', false);
$vc_refr = false;

if (isset($config['vc_refr']) and $config['vc_refr'] != 0) {
    $view_refresh = $config['vc_refr'];
} else {
    $view_refresh = '300';
}

// Get input parameter for layout id
if (! $id_layout) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$layout = db_get_row('tlayout', 'id', $id_layout);

if (! $layout) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$id_group = $layout['id_group'];
$layout_name = $layout['name'];
$background = $layout['background'];
$bwidth = $layout['width'];
$bheight = $layout['height'];

$pure_url = '&pure='.$config['pure'];

// ACL
$vconsole_read = check_acl($config['id_user'], $id_group, 'VR');
$vconsole_write = check_acl($config['id_user'], $id_group, 'VW');
$vconsole_manage = check_acl($config['id_user'], $id_group, 'VM');

if (! $vconsole_read && !$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without group access'
    );
    include 'general/noaccess.php';
    exit;
}

// Render map
$options = [];

$options['consoles_list']['text'] = '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder&refr='.$refr.'">'.html_print_image(
    'images/visual_console.png',
    true,
    ['title' => __('Visual consoles list')]
).'</a>';

if ($vconsole_write || $vconsole_manage) {
    $url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';

    $hash = md5($config['dbpass'].$id_layout.$config['id_user']);

    $options['public_link']['text'] = '<a href="'.ui_get_full_url('operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config['id_user']).'" target="_blank">'.html_print_image(
        'images/camera_mc.png',
        true,
        ['title' => __('Show link to public Visual Console')]
    ).'</a>';
    $options['public_link']['active'] = false;

    $options['data']['text'] = '<a href="'.$url_base.$action.'&tab=data&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        ['title' => __('Main data')]
    ).'</a>';
    $options['list_elements']['text'] = '<a href="'.$url_base.$action.'&tab=list_elements&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/list.png',
        true,
        ['title' => __('List elements')]
    ).'</a>';

    if (enterprise_installed()) {
        $options['wizard_services']['text'] = '<a href="'.$url_base.$action.'&tab=wizard_services&id_visual_console='.$id_layout.'">'.html_print_image(
            'images/wand_services.png',
            true,
            ['title' => __('Services wizard')]
        ).'</a>';
    }

    $options['wizard']['text'] = '<a href="'.$url_base.$action.'&tab=wizard&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/wand.png',
        true,
        ['title' => __('Wizard')]
    ).'</a>';
    $options['editor']['text'] = '<a href="'.$url_base.$action.'&tab=editor&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/builder.png',
        true,
        ['title' => __('Builder')]
    ).'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$view_refresh.'">'.html_print_image('images/operation.png', true, ['title' => __('View')]).'</a>';
$options['view']['active'] = true;

if (!is_metaconsole()) {
    if (!$config['pure']) {
        $options['pure']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$refr.'&pure=1">'.html_print_image('images/full_screen.png', true, ['title' => __('Full screen mode')]).'</a>';
        ui_print_page_header($layout_name, 'images/visual_console.png', false, '', false, $options);
    }

    // Set the hidden value for the javascript
    html_print_input_hidden('metaconsole', 0);
} else {
    // Set the hidden value for the javascript
    html_print_input_hidden('metaconsole', 1);
}

        visual_map_print_visual_map(
            $id_layout,
            true,
            true,
            null,
            null,
            '',
            false,
            $graph_javascript
        );

        ?>
    <style type="text/css">
        /* Avoid the main_pure container 1000px height */
        body.pure {
            min-height: 100px;
        }
        div#main_pure {
            height: 100%;
            margin: 0px;
        }
    </style>
    <?php
    ui_require_javascript_file('wz_jsgraphics');
    ui_require_javascript_file('pandora_visual_console');
    $ignored_params['refr'] = '';
    ?>

<script language="javascript" type="text/javascript">
    $(document).ready (function () {
        var refr = <?php echo (int) $refr; ?>;
        var pure = <?php echo (int) $config['pure']; ?>;
        var href = "<?php echo ui_get_url_refresh($ignored_params); ?>";
                
        $(".module_graph .menu_graph").css('display','none');
        
        $(".parent_graph").each(function(){
            
        if($(this).css('background-color') != 'rgb(255, 255, 255)'){
                $(this).css('color', '#999');                
                }
        });            

        $(".overlay").removeClass("overlay").addClass("overlaydisabled");
            
    });
    
    $(window).on('load', function () {
        $('.item:not(.icon) img').each(function(){
            
            
            if($(this).css('float')=='left' || $(this).css('float')=='right'){
            
                
            $(this).css('margin-top',(parseInt($(this).parent().parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
            $(this).css('margin-left','');
            
            }
            else{
                $(this).css('margin-left',(parseInt($(this).parent().parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                $(this).css('margin-top','');
            }
            
        });
        
        $('.item > div').each( function() {
            if ($(this).css('float')=='left' || $(this).css('float')=='right') {
                if($(this).attr('id').indexOf('clock') || $(this).attr('id').indexOf('overlay')){
                    $(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
                }
                else{
                    $(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2-15)+'px');
                }
                $(this).css('margin-left','');
            }
            else {
                $(this).css('margin-left',(parseInt($(this).parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                $(this).css('margin-top','');
            }
        });
        
        $('.item > a > div').each(function(){
            if($(this).css('float')=='left' || $(this).css('float')=='right'){
            
                
            $(this).css('margin-top',(parseInt($(this).parent().parent().css('height'))/2-parseInt($(this).css('height'))/2-5)+'px');
            $(this).css('margin-left','');
            
            }
            else{
                $(this).css('margin-left',(parseInt($(this).parent().parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                $(this).css('margin-top','');
            }
            
        });
        
        $(".graph:not([class~='noresizevc'])").each(function(){
            height = parseInt($(this).css("height")) - 30;
            $(this).css('height', height);
        });
            
    });
</script>