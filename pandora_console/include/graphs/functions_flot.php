<?PHP

// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2007-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

//JQuery 1.6.1 library addition

global $config;


function include_javascript_dependencies_flot_graph($return = false) {
	global $config;

	static $is_include_javascript = false;

	if (!$is_include_javascript) {
		$is_include_javascript = true;

		$metaconsole_hack = '';
		if (defined('METACONSOLE')) {
			$metaconsole_hack = '../../';
		}

		// NOTE: jquery.flot.threshold is not te original file. Is patched to allow multiple thresholds and filled area
		$output = '
			<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="' . ui_get_full_url($metaconsole_hack . '/include/graphs/flot/excanvas.js') . '"></script><![endif]-->
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.time.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack  . '/include/graphs/flot/jquery.flot.pie.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.crosshair.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.stack.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.selection.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.resize.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.threshold.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.threshold.multiple.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.symbol.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.exportdata.pandora.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.axislabels.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/pandora.flot.js') .'"></script>';
		$output .= "
			<script type='text/javascript'>
			var precision_graph = " . $config['graph_precision'] . ";
			function pieHover(event, pos, obj)
			{
				if (!obj)
					return;
				percent = parseFloat(obj.series.percent).toFixed(2);
				$('#hover').html('<span style=\'font-weight: bold; color: '+obj.series.color+'\'>'+obj.series.label+' ('+percent+'%)</span>');
				$('.legendLabel').each(function() {
					if ($(this).html() == obj.series.label) {
						$(this).css('font-weight','bold');
					}
					else {
						$(this).css('font-weight','');
					}
				});
			}

			function pieClick(event, pos, obj)
			{
				if (!obj)
					return;
				percent = parseFloat(obj.series.percent).toFixed(2);
				alert(''+obj.series.label+': '+obj.series.data[0][1]+' ('+percent+'%)');
			}
			</script>";

		if (!$return)
			echo $output;

		return $output;
	}
}

///////////////////////////////
////////// AREA GRAPHS ////////
///////////////////////////////
function flot_area_graph (
	$agent_module_id, $array_data,
	$legend, $series_type, $color, $date_array,
	$data_module_graph, $params, $water_mark,
	$array_events_alerts ) {

	global $config;

	// include_javascript_dependencies_flot_graph();

	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');

	$background_style = '';
	switch ($params['backgroundColor']) {
		case 'white':
			$background_style = ' background: #fff; ';
			break;
		case 'black':
			$background_style = ' background: #000; ';
			break;
		case 'transparent':
			$background_style = '';
			break;
		default:
			$background_style = 'background-color: ' . $params['backgroundColor'];
			break;
	}

	// Parent layer
	$return = "<div class='parent_graph' style='width: " . ($params['width']) . ";" . $background_style . "'>";
	// Set some containers to legend, graph, timestamp tooltip, etc.
	if($params['show_legend']){
		$return .= "<p id='legend_$graph_id' class='legend_graph' style='font-size:" . $params['font_size'] ."pt !important;'></p>";
	}
	if(isset($params['graph_combined']) && $params['graph_combined'] &&
		(!isset($params['from_interface']) || !$params['from_interface']) ){
		$yellow_up      = 0;
		$red_up         = 0;
		$yellow_inverse = false;
		$red_inverse    = false;
	}
	elseif(!isset($params['combined']) || !$params['combined']){
		$yellow_threshold = $data_module_graph['w_min'];
		$red_threshold    = $data_module_graph['c_min'];
		// Get other required module datas to draw warning and critical
		if ($agent_module_id == 0) {
			$yellow_up      = 0;
			$red_up         = 0;
			$yellow_inverse = false;
			$red_inverse    = false;
		} else {
			$yellow_up      = $data_module_graph['w_max'];
			$red_up         = $data_module_graph['c_max'];
			$yellow_inverse = !($data_module_graph['w_inv'] == 0);
			$red_inverse    = !($data_module_graph['c_inv'] == 0);
		}
	}
	elseif(isset($params['from_interface']) && $params['from_interface']){
		if(	isset($params['threshold_data']) && is_array($params['threshold_data'])){
			$yellow_up      = $params['threshold_data']['yellow_up'];
			$red_up         = $params['threshold_data']['red_up'];
			$yellow_inverse = $params['threshold_data']['yellow_inverse'];
			$red_inverse    = $params['threshold_data']['red_inverse'];
		}
		else{
			$yellow_up      = 0;
			$red_up         = 0;
			$yellow_inverse = false;
			$red_inverse    = false;
		}
	}
	else{
		$yellow_up      = 0;
		$red_up         = 0;
		$yellow_inverse = false;
		$red_inverse    = false;
	}

	if ($params['menu']) {
		$return .= menu_graph(
			$yellow_threshold,
			$red_threshold,
			$yellow_up,
			$red_up,
			$yellow_inverse,
			$red_inverse,
			$graph_id,
			$params
		);
	}

	$return .= html_print_input_hidden('line_width_graph', $config['custom_graph_width'], true);
	$return .= "<div id='timestamp_$graph_id'
					class='timestamp_graph'
					style='	font-size:".$params['font_size']."pt;
							display:none; position:absolute;
							background:#fff; border: solid 1px #aaa;
							padding: 2px; z-index:1000;'></div>";
	$return .= "<div id='$graph_id' class='";

	if($params['type'] == 'area_simple'){
		$return .= "noresizevc ";
	}

	$return .= "graph" .$params['adapt_key'] ."'
				style='	width: ".$params['width']."px;
				height: ".$params['height']."px;'></div>";

	if ($params['menu']) {
		$params['height'] = 100;
	}
	else {
		$params['height'] = 1;
	}

	if (!$vconsole){
		$return .= "<div id='overview_$graph_id' class='overview_graph'
						style='margin:0px; margin-top:30px; margin-bottom:50px; display:none; width: ".$params['width']."; height: 200px;'></div>";
	}

	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='" . $water_mark['url'] . "'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}

	foreach($series_type as $k => $v){
		$series_type_unique["data_" . $graph_id . "_" . $k] = $v;
	}

	// Store data series in javascript format
	$extra_width = (int)($params['width'] / 3);
	$return .= "<div id='extra_$graph_id'
					style='font-size: " . $params['font_size'] . "pt;
					display:none; position:absolute; overflow: auto;
					max-height: ".($params['height']+50)."px;
					width: ".$extra_width."px;
					background:#fff; padding: 2px 2px 2px 2px;
					border: solid #000 1px;'></div>";

	if(substr($background_style, -6, 4) == '#fff'){
		$background_color = "#eee";
		$legend_color = "#151515";
	}
	else if(substr($background_style, -6, 4) == '#000'){
		$background_color = "#151515";
		$legend_color = "#BDBDBD";
	}
	else{
		$background_color = "#A4A4A4";
		$legend_color = "#A4A4A4";
	}

	$force_integer = 0;

	// Trick to get translated string from javascript
	$return .= html_print_input_hidden('unknown_text', __('Unknown'), true);

	if (!isset($config["short_module_graph_data"])){
		$config["short_module_graph_data"] = '';
	}

	$short_data = $config["short_module_graph_data"];

	$values              = json_encode($array_data);
	$legend              = json_encode($legend);
	$series_type         = json_encode($series_type);
	$color               = json_encode($color);
	$date_array          = json_encode($date_array);
	$data_module_graph   = json_encode($data_module_graph);
	$params 			 = json_encode($params);
	$array_events_alerts = json_encode($array_events_alerts);

	// Javascript code
	if ($font_size == '') $font_size = '\'\'';
	$return .= "<script type='text/javascript'>";
	$return .= "$(document).ready( function () {";
	$return .= "pandoraFlotArea(" .
		"'$graph_id', \n" .
		"JSON.parse('$values'), \n" .
		"JSON.parse('$legend'), \n" .
		"'$agent_module_id', \n" .
		"JSON.parse('$series_type'), \n" .
		"JSON.parse('$color'), \n" .
		"'$watermark', \n" .
		"JSON.parse('$date_array'), \n" .
		"JSON.parse('$data_module_graph'), \n" .
		"JSON.parse('$params'), \n" .
		"$force_integer, \n" .
		"'$background_color', \n" .
		"'$legend_color', \n" .
		"'$short_data', \n" .
		"JSON.parse('$array_events_alerts')".
	");";
	$return .= "});";
	$return .= "</script>";

	// Parent layer
	$return .= "</div>";

	return $return;
}

function menu_graph(
	$yellow_threshold, $red_threshold,
	$yellow_up, $red_up, $yellow_inverse,
	$red_inverse, $graph_id, $params
){
	$return = '';
	$threshold = false;
	if ($yellow_threshold != $yellow_up || $red_threshold != $red_up) {
		$threshold = true;
	}

	if ( $params['dashboard'] == false AND $params['vconsole'] == false) {
		$return .= "<div id='general_menu_$graph_id' class='menu_graph' style='
						width: 20px;
						height: 150px;
						left:100%;
						position: absolute;
						top: 0px;
						background-color: tranparent;'>";
		$return .= "<div id='menu_$graph_id' " .
			"style='display: none; " .
				"text-align: center;" .
				"position: relative;".
				"border-bottom: 0px;'>
			<a href='javascript:'><img id='menu_cancelzoom_$graph_id' src='".$params['homeurl']."images/zoom_cross_grey.disabled.png' alt='".__('Cancel zoom')."' title='".__('Cancel zoom')."'></a>";
		if ($threshold) {
			$return .= " <a href='javascript:'><img id='menu_threshold_$graph_id' src='".$params['homeurl']."images/chart_curve_threshold.png' alt='".__('Warning and Critical thresholds')."' title='".__('Warning and Critical thresholds')."'></a>";
		}
		if($params['show_overview']){
			$return .= " <a href='javascript:'>
				<img id='menu_overview_$graph_id' class='menu_overview' src='" . $params['homeurl'] . "images/chart_curve_overview.png' alt='" . __('Overview graph') . "' title='".__('Overview graph')."'></a>";
		}
		// Export buttons
		if($params['show_export_csv']){
			$return .= " <a href='javascript:'><img id='menu_export_csv_$graph_id' src='".$params['homeurl']."images/csv_grey.png' alt='".__('Export to CSV')."' title='".__('Export to CSV')."'></a>";
		}
		// Button disabled. This feature works, but seems that is not useful enough to the final users.
		//$return .= " <a href='javascript:'><img id='menu_export_json_$graph_id' src='".$homeurl."images/json.png' alt='".__('Export to JSON')."' title='".__('Export to JSON')."'></a>";

		$return .= "</div>";
		$return .= "</div>";
	}

	if ($params['dashboard']) {
		$return .= "<div id='general_menu_$graph_id' class='menu_graph' style='
						width: 30px;
						height: 250px;
						left: " . $params['width'] . "px;
						position: absolute;
						top: 0px;
						background-color: white;'>";

		$return .= "<div id='menu_$graph_id' " .
			"style='display: none; " .
				"text-align: center;" .
				"position: relative;".
				"border-bottom: 0px;'>
			<a href='javascript:'><img id='menu_cancelzoom_$graph_id' src='".$params['homeurl']."images/zoom_cross_grey.disabled.png' alt='".__('Cancel zoom')."' title='".__('Cancel zoom')."'></a>";

		$return .= "</div>";
		$return .= "</div>";
	}
	return $return;
}

///////////////////////////////
///////////////////////////////
///////////////////////////////

// Prints a FLOT pie chart
function flot_pie_chart ($values, $labels, $width, $height, $water_mark,
	$font = '', $font_size = 8, $legend_position = '', $colors = '',
	$hide_labels = false) {
	
	// include_javascript_dependencies_flot_graph();
	
	$series = sizeof($values);
	if (($series != sizeof ($labels)) || ($series == 0) ) {
		return;
	}
	
	$graph_id = uniqid('graph_');
	
	switch ($legend_position) {
		case 'bottom':
			$height = $height + (count($values) * 24);
			break;
		case 'right':
		default:
			//TODO FOR TOP OR LEFT OR RIGHT
			break;
	}
	
	$return = "<div id='$graph_id' class='graph' style='width: ".$width."px; height: ".$height."px;'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$water_mark = 'true';
	}
	else {
		$water_mark = 'false';
	}
	
	$separator = ';;::;;';
	
	$labels = implode($separator, $labels);
	$values = implode($separator, $values);
	if (!empty($colors)) {
		$colors = implode($separator, $colors);
	}
	
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotPie('$graph_id', '$values', '$labels',
		'$series', '$width', $font_size, $water_mark, '$separator',
		'$legend_position', '$height', '$colors', " . json_encode($hide_labels) . ")";
	
	$return .= "</script>";
	
	return $return;
}

// Prints a FLOT pie chart
function flot_custom_pie_chart ($flash_charts, $graph_values,
		$width, $height, $colors, $module_name_list, $long_index,
		$no_data,$xaxisname, $yaxisname, $water_mark, $fontpath, $font_size,
		$unit, $ttl, $homeurl, $background_color, $legend_position) {
	
	global $config;
	///TODO
	// include_javascript_dependencies_flot_graph();
	
	$total_modules = $graph_values['total_modules'];
	unset($graph_values['total_modules']);
	
	foreach ($graph_values as $label => $value) {
		if ($value['value']) {
			if ($value['value'] > 1000000)
				$legendvalue = sprintf("%sM", remove_right_zeros(number_format($value['value'] / 1000000, $config['graph_precision'])));
			else if ($value['value'] > 1000)
				$legendvalue = sprintf("%sK", remove_right_zeros(number_format($value['value'] / 1000, $config['graph_precision'])));
			else
				$legendvalue = remove_right_zeros(number_format($value['value'], $config['graph_precision']));
		}
		else
			$legendvalue = __('No data');
		$values[] = $value['value'];
		$legend[] = $label .": " . $legendvalue . " " .$value['unit'];
		$labels[] = $label;
	}
	
	$graph_id = uniqid('graph_');
	
	$return = "<div id='$graph_id' class='graph noresizevc' style='width: ".$width."px; height: ".$height."px;'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='".$water_mark["url"]."'></div>";
		$water_mark = 'true';
	}
	else {
		$water_mark = 'false';
	}
	
	$separator = ';;::;;';
	
	$labels = implode($separator, $labels);
	$legend = implode($separator, $legend);
	$values = implode($separator, $values);
	if (!empty($colors)) {
		foreach ($colors as $color) {
			$temp_colors[] = $color['color'];
		}
	}
	$colors = implode($separator, $temp_colors);
	
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotPieCustom('$graph_id', '$values', '$labels',
			'$width', $font_size, '$fontpath', $water_mark,
			'$separator', '$legend_position', '$height', '$colors','$legend','$background_color')";
	
	$return .= "</script>";
	
	return $return;
}

// Returns a 3D column chart
function flot_hcolumn_chart ($graph_data, $width, $height, $water_mark, $font = '', $font_size = 7, $background_color = "white", $tick_color = "white", $val_min=null, $val_max=null) {
	global $config;
	
	// include_javascript_dependencies_flot_graph();
	
	$return = '';
	
	$stacked_str = '';
	$multicolor = true;
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	$graph_id2 = uniqid('graph_');
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return .= "<div id='$graph_id' class='graph' style='width: ".$width."px; height: ".$height."px; padding-left: 20px;'></div>";
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}
	
	// Set a weird separator to serialize and unserialize passing data
	// from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$max = PHP_INT_MIN+1;
	$min = PHP_INT_MAX-1;
	$i = count($graph_data);
	$data = array();
	
	foreach ($graph_data as $label => $values) {
		$labels[] = io_safe_output($label);
		$i--;
		
		foreach ($values as $key => $value) {
			$jsvar = "data_" . $graph_id . "_" . $key;
			
			$data[$jsvar][] = $value;
			
			
			if ($value > $max) {
				$max = $value;
			}

			if ($value < $min) {
				$min = $value;
			}
		}
	}

	if (!is_numeric($val_min)) {
		$val_min = $min;
	}
	if (!is_numeric($val_max)) {
		$val_max = $max;
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$i = 0;
	
	$values2 = array();
	
	foreach ($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
	}
	
	$values = implode($separator2, $values2);
	
	$jsseries = implode(',', $jsseries);
	
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotHBars('$graph_id', '$values', '$labels',
		false, $max, '$water_mark', '$separator', '$separator2', '$font', $font_size, '$background_color', '$tick_color', $val_min, $val_max)";

	$return .= "</script>";
	
	return $return;
}

// Returns a 3D column chart
function flot_vcolumn_chart ($graph_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, $water_mark, $homedir, $font, $font_size, $from_ux, $from_wux, $background_color = 'white', $tick_color = 'white') {
	global $config;
	
	// include_javascript_dependencies_flot_graph();
	
	$stacked_str = '';
	$multicolor = false;
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	$graph_id2 = uniqid('graph_');

	if ($width != 'auto') {
		$width = $width . "px";
	}
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return .= "<div id='$graph_id' class='graph $adapt_key' style='width: ".$width."; height: ".$height."px; padding-left: 20px;'></div>";
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}
	
	$colors = array_map(function ($elem) {
		return $elem['color'] ? $elem['color'] : null;
	}, $color);
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$max = 0;
	$i = count($graph_data);
	foreach ($graph_data as $label => $values) {
		$labels[] = $label;
		$i--;
		
		foreach ($values as $key => $value) {
			$jsvar = "data_" . $graph_id . "_" . $key;
			
			$data[$jsvar][] = $value;
			
			
			if ($value > $max) {
				$max = $value;
			}
		}
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	$colors  = implode($separator, $colors);

	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$i = 0;
	
	$values2 = array();
	
	foreach ($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
	}
	
	$values = implode($separator2, $values2);
	
	$jsseries = implode(',', $jsseries);
	
	// Javascript code
	$return .= "<script type='text/javascript'>";

	if ($from_ux) {
		if($from_wux){
			$return .= "pandoraFlotVBars('$graph_id', '$values', '$labels', '$labels', '$legend', '$colors', false, $max, '$water_mark', '$separator', '$separator2','$font',$font_size, true, true, '$background_color', '$tick_color')";
		}
		else{
			$return .= "pandoraFlotVBars('$graph_id', '$values', '$labels', '$labels', '$legend', '$colors', false, $max, '$water_mark', '$separator', '$separator2','$font',$font_size, true, false, '$background_color', '$tick_color')";
		}
	}
	else {
		$return .= "pandoraFlotVBars('$graph_id', '$values', '$labels', '$labels', '$legend', '$colors', false, $max, '$water_mark', '$separator', '$separator2','$font',$font_size, false, false, '$background_color', '$tick_color')";
	}

	$return .= "</script>";
	
	return $return;
}

function flot_slicesbar_graph ($graph_data, $period, $width, $height, $legend, $colors, $fontpath, $round_corner, $homeurl, $watermark = '', $adapt_key = '', $stat_win = false, $id_agent = 0, $full_legend_date = array()) {
	global $config;
	
	// include_javascript_dependencies_flot_graph();
		
	$stacked_str = 'stack: stack,';
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	if ($stat_win) {
		$return = "<div id='$graph_id' class='noresizevc graph $adapt_key' style='width: ".$width."%; height: ".$height."px; display: inline-block;'></div>";
	}
	else {
		$return = "<div id='$graph_id' class='noresizevc graph $adapt_key' style='width: ".$width."%; height: ".$height."px;'></div>";
	}
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$datacolor = array();
	
	$max = 0;
	
	$i = count($graph_data);
	
	$intervaltick = $period / $i;
	
	$leg_max_length = 0;
	foreach ($legend as $l) {
		if (strlen($l) > $leg_max_length) {
			$leg_max_length = strlen($l);
		}
	}
	
	$fontsize = 7;
	
	$extra_height = 15;
	if (defined("METACONSOLE"))
		$extra_height = 20;
	
	$return .= "<div id='extra_$graph_id' style='font-size: ".$fontsize."pt; display:none; position:absolute; overflow: auto; height: ".$extra_height."px; background:#fff; padding: 2px 2px 2px 2px; border: solid #000 1px;'></div>";
	
	$maxticks = (int) ($width / ($fontsize * $leg_max_length));
	
	$i_aux = $i;
	while(1) {
		if ($i_aux <= $maxticks ) {
			break;
		}
		
		$intervaltick*= 2;
		
		$i_aux /= 2;
	}
	
	$intervaltick = (int) $intervaltick;
	$acumulate = 0;
	$c = 0;
	$acumulate_data = array();
	foreach ($graph_data as $label => $values) {
		$labels[] = $label;
		$i--;
		
		foreach ($values as $key => $value) {
			$jsvar = "d_".$graph_id."_".$i;
			if ($key == 'data') {
				$datacolor[$jsvar] = $colors[$value];
				continue;
			}
			$data[$jsvar][] = $value;
			
			$acumulate_data[$c] = $acumulate;
			$acumulate += $value;
			$c++;
			
			if ($value > $max) {
				$max = $value;
			}
		}
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	$datacolor = implode($separator,$datacolor);
	$legend = io_safe_output(implode($separator,$legend));
	if (!empty($full_legend_date)) {
		$full_legend_date = io_safe_output(implode($separator,$full_legend_date));
	}
	else {
		$full_legend_date = false;
	}
	$acumulate_data = io_safe_output(implode($separator,$acumulate_data));
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$date = get_system_time ();
	$datelimit = ($date - $period) * 1000;
	
	$i = 0;
	
	$values2 = array();
	
	foreach ($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
		$i ++;
	}
	
	$values = implode($separator2, $values2);
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	$return .= "//<![CDATA[\n";
	$return .= "pandoraFlotSlicebar('$graph_id', '$values', '$datacolor', '$labels', '$legend', '$acumulate_data', $intervaltick, false, $max, '$separator', '$separator2', '', $id_agent, '$full_legend_date')";
	$return .= "\n//]]>";
	$return .= "</script>";
	
	return $return;
}
?>
