<?php

ob_start(); //HACK TO EAT ANYTHING THAT CORRUPS THE IMAGE FILE

// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once('functions_utils.php');
include_once('../functions_io.php');
include_once('../functions.php');
include_once('../functions_html.php');

/* pChart library inclusions */
include_once("pChart/pData.class.php");
include_once("pChart/pDraw.class.php");
include_once("pChart/pImage.class.php");
include_once("pChart/pPie.class.php");
include_once("pChart/pScatter.class.php");
include_once("pChart/pRadar.class.php");

// Define default fine colors

$default_fine_colors = array();
$default_fine_colors[] = "#2222FF";
$default_fine_colors[] = "#00DD00";
$default_fine_colors[] = "#CC0033";
$default_fine_colors[] = "#9900CC";
$default_fine_colors[] = "#FFCC66";
$default_fine_colors[] = "#999999";

// Default values

$antialiasing = true;
$font = '../fonts/unicode.ttf';
$xaxisname = '';
$yaxisname = '';
$legend = null;
$colors = null;
$font_size = 8;
$force_steps = true; 
$legend_position = null;
$series_type = null;


$graph_type = get_parameter('graph_type', '');

$id_graph = get_parameter('id_graph', false);

$graph_threshold = get_parameter('graph_threshold', false);

$id_module = get_parameter('id_module');


if (!$id_graph) {
	exit;
}

$ttl = get_parameter('ttl', 1);

$graph = unserialize_in_temp($id_graph, true, $ttl);

if (!$graph) {
	exit;
}

$data = $graph['data'];
$width = $graph['width'];
$height = $graph['height'];

if (isset($graph['legend_position'])) {
	$legend_position = $graph['legend_position'];
}
if (isset($graph['color'])) {
	$colors = $graph['color'];
}
if (isset($graph['legend'])) {
	$legend = $graph['legend'];
}
if (isset($graph['xaxisname'])) {
	$xaxisname = $graph['xaxisname'];
}
if (isset($graph['yaxisname'])) { 
	$yaxisname = $graph['yaxisname'];
}
if (isset($graph['round_corner'])) { 
	$round_corner = $graph['round_corner'];
}
if (isset($graph['font'])) {
	if (!empty($graph['font'])) {
		$font = $graph['font'];
	}
}
if (isset($graph['font_size'])) {
	if (!empty($graph['font_size'])) {
		$font_size = $graph['font_size'];
	}
}
if (isset($graph['backgroundColor'])) {
	if (!empty($graph['backgroundColor'])) {
		$backgroundColor = $graph['backgroundColor'];
	}
}
if (isset($graph['antialiasing'])) { 
	$antialiasing = $graph['antialiasing'];
}
$force_height = true;
if (isset($graph['force_height'])) { 
	$force_height = $graph['force_height'];
}
if (isset($graph['period'])) { 
	$period = $graph['period'];
}
if (isset($graph['unit'])){
	$unit = $graph['unit'];
}

if (!$force_height) {
	if ($height < (count($graph['data']) * 14)) {
		$height = (count($graph['data']) * 14);
	}
}

$water_mark = '';
if (isset($graph['water_mark'])) { 
	//"/var/www/pandora_console/images/logo_vertical_water.png";
	$water_mark = $graph['water_mark'];
}

if (isset($graph['force_steps'])) {
	$force_steps = $graph['force_steps'];
}

if (isset($graph['series_type'])) {
	$series_type = $graph['series_type'];
}

if (isset($graph['percentil'])){
	$percentil = $graph['percentil']; 
}

$step = 1;
if ($force_steps) {
	$pixels_between_xdata = 50;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($data);
	if ($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
}

$c = 1;

switch ($graph_type) {
	case 'hbar':
	case 'vbar':
		foreach ($data as $i => $values) {
			foreach ($values as $name => $val) {
				$data_values[$name][] = $val;
			}
			
			$data_keys[] = $i;
			
		}
		$fine_colors = array();
		
		// If is set fine colors we store it or set default
							
		foreach ($colors as $i => $fine_color) {
			$rgb_fine = html_html2rgb($fine_color);
			$fine_colors[$i]['R'] = $rgb_fine[0];
			$fine_colors[$i]['G'] = $rgb_fine[1];
			$fine_colors[$i]['B'] = $rgb_fine[2];
			$fine_colors[$i]['Alpha'] = 100;
		}
		$colors = $fine_colors;
		
		break;
	case 'bullet_chart':
		$anterior = 0;
		foreach ($data as $i => $values) {
			foreach ($values as $key => $val) {
				switch ($key) {
					case 0:
						$name = __("Max");
						break;
					case 1:
						$name = __("Actual");
						break;
					case 2:
						$name = __("Min");
						break;
				}
				$data_values[$name][] = ($val - $anterior);
				$anterior += (($val - $anterior)<0) ? 0 : ($val - $anterior);
			}
			$anterior = 0;
			$data_keys[] = $i;
			
		}
		break;
	case 'progress':
	case 'area':
	case 'stacked_area':
	case 'stacked_line':
	case 'line':
	case 'threshold':
	case 'scatter':
	
		if (!empty($percentil)) {
			$count_percentil = count($percentil);
			for ($j=0; $j < $count_percentil; $j++) {
				$i=0;
				foreach ($data as $key => $value) {
					$data[$key]['percentil' . $j] = $percentil[$j][$i];
					if($graph_type == 'area'){
						$series_type['percentil' . $j] = 'line';
					}
					$i++;
				}
			}
		}
		foreach ($data as $i => $d) {
			$data_values[] = $d;
			
			if (($c % $step) == 0) {
				$data_keys[] = $i;
			}
			else {
				$data_keys[] = "";
			}
			
			$c++;
		}
		
		break;
	case 'slicebar':
	case 'polar':
	case 'radar':
	case 'pie3d':
	case 'pie2d':
	case 'ring3d':
	
		break;
}

switch($graph_type) {
	case 'slicebar':
	case 'polar':
	case 'radar':
	case 'pie3d':
	case 'pie2d':
	case 'ring3d':
	case 'bullet_chart':
		break;
	default:
		if (!is_array(reset($data_values))) {
			$data_values = array($data_values);
			if (is_array($colors) && !empty($colors)) {
				$colors = array($colors);
			}
		}
		break;
}

$rgb_color = array();

if (!isset($colors))
	$colors = array();

if (empty($colors)) {
	$colors = array();
}

foreach ($colors as $i => $color) {
	if (isset ($color['border'])) {
		$rgb['border'] = html_html2rgb($color['border']);
	
		if (isset($rgb['border'])) {
			$rgb_color[$i]['border']['R'] = $rgb['border'][0];
			$rgb_color[$i]['border']['G'] = $rgb['border'][1];
			$rgb_color[$i]['border']['B'] = $rgb['border'][2];
		}
	}

	if (isset ($color['color'])) {
		$rgb['color'] = html_html2rgb($color['color']);

		if (isset($rgb['color'])) {
			$rgb_color[$i]['color']['R'] = $rgb['color'][0];
			$rgb_color[$i]['color']['G'] = $rgb['color'][1];
			$rgb_color[$i]['color']['B'] = $rgb['color'][2];
		}
	}

	if (isset ($color['alpha'])) {
		$rgb_color[$i]['alpha'] = $color['alpha'];
	}
}

//add color for percentil
if($percentil){
	for ($j=0; $j < $count_percentil; $j++) {
		if (isset ($colors[$j]['border'])) {
			$rgb['border'] = html_html2rgb($colors[$j]['border']);
			
			if (isset($rgb['border'])) {
				$rgb_color['percentil' . $j]['border']['R'] = $rgb['border'][0];
				$rgb_color['percentil' . $j]['border']['G'] = $rgb['border'][1];
				$rgb_color['percentil' . $j]['border']['B'] = $rgb['border'][2];
			}
		}

		if (isset ($colors[$j]['color'])) {
			$rgb['color'] = html_html2rgb($colors[$j]['color']);
			
			if (isset($rgb['color'])) {
				$rgb_color['percentil' . $j]['color']['R'] = $rgb['color'][0];
				$rgb_color['percentil' . $j]['color']['G'] = $rgb['color'][1];
				$rgb_color['percentil' . $j]['color']['B'] = $rgb['color'][2];
			}
		}

		if (isset ($colors[$j]['alpha'])) {
			$rgb_color['percentil' . $j]['alpha'] = $colors[$j]['alpha'];
		}
	}
}

//add for report with max 15 modules comparation repeat
$countlegend = count($legend);
if($countlegend > 15){
	$i=16;
	$l=0;
	while ($countlegend > 15){
		$rgb_color[$i] = $rgb_color[$l];
		$l++;
		$i++;
		$countlegend--;
	} 
}

ob_get_clean(); //HACK TO EAT ANYTHING THAT CORRUPS THE IMAGE FILE

switch ($graph_type) {
	case 'ring3d':
		pch_ring_graph($graph_type, array_values($data), $legend,
			$width, $height, $font, $water_mark, $font_size, $legend_position, $colors);
		break;
	case 'bullet_chart':
		pch_bullet_chart($graph_type, $data_values, $legend,
			$width, $height, $font, $water_mark, $font_size, $legend_position, $colors);
		break;
	case 'pie3d':
	case 'pie2d':
		pch_pie_graph($graph_type, array_values($data), array_keys($data),
			$width, $height, $font, $water_mark, $font_size, $legend_position, $colors);
		break;
	case 'slicebar':
		pch_slicebar_graph($graph_type, $data, $period, $width, $height, $colors, $font, $round_corner, $font_size);
		break;
	case 'polar':
	case 'radar':
		pch_kiviat_graph($graph_type, array_values($data), array_keys($data),
			$width, $height, $font, $font_size);
		break;
	case 'hbar':
	case 'vbar':
		pch_bar_graph($graph_type, $data_keys, $data_values, $width, $height,
			$font, $antialiasing, $rgb_color, $xaxisname, $yaxisname, false,
			$legend, $fine_colors, $water_mark, $font_size);
		break;
	default:
	case 'threshold':
		pch_threshold_graph($graph_type, $data_keys, $data_values, $width,
			$height, $font, $antialiasing, $xaxisname, $yaxisname, $title,
			$font_size);
		break;
}

function pch_slicebar_graph ($graph_type, $data, $period, $width, $height, $colors, $font, $round_corner, $font_size) {
	/* CAT:Slicebar charts */
	
	set_time_limit (0);
	
	// Dataset definition
	$myPicture = new pImage($width,$height);
	
	/* Turn of Antialiasing */
	$myPicture->Antialias = 0;
	
	$myPicture->setFontProperties(array("FontName"=> $font, "FontSize"=>$font_size,"R"=>80,"G"=>80,"B"=>80));
	
	// Round corners defined in global setup
	if ($round_corner != 0)
		$radius = ($height > 18) ? 8 : 0;
	else
		$radius = 0;
	
	$thinest_slice = $width / $period;
	
	/* Color stuff */
	$colorsrgb = array();
	foreach($colors as $key => $col) {
		$rgb = html_html2rgb($col);
		$colorsrgb[$key]['R'] = $rgb[0];
		$colorsrgb[$key]['G'] = $rgb[1];
		$colorsrgb[$key]['B'] = $rgb[2];
	}
	
	$i = 0;
	foreach ($data as $d) {
		$color = $d['data'];
		$color = $colorsrgb[$color]; 
		$ratio = $thinest_slice * $d['utimestamp'];
		$myPicture->drawRoundedFilledRectangle ($i, 0, $ratio+$i, 
			$height, $radius,
			array('R' => $color['R'],
				'G' => $color['G'],
				'B' => $color['B'])
			);
		$i+=$ratio;
	}
	
	if ($round_corner) {
		/* Under this value, the rounded rectangle is painted great */
		if ($thinest_slice <= 16) {
			/* Clean a bit of pixels */
			for ($i = 0; $i < 7; $i++) {
				$myPicture->drawLine (0, $i, 6 - $i, $i, array('R' => 255, 'G' => 255, 'B' => 255));
			}
			$end = $height - 1;
			for ($i = 0; $i < 7; $i++) {
				$myPicture->drawLine (0, $end - $i, 5 - $i, $end - $i, array('R' => 255, 'G' => 255, 'B' => 255));
			}
		}
	}
	
	$myPicture->drawRoundedRectangle (0, 0, $width,
		$height - 1, $radius, array('R' => 157, 'G' => 157, 'B' => 157));
	
	$myPicture->Stroke ();
}

function pch_pie_graph ($graph_type, $data_values, $legend_values, $width,
	$height, $font, $water_mark, $font_size, $legend_position, $colors) {
	/* CAT:Pie charts */
	
	/* Create and populate the pData object */
	$MyData = new pData();   
	$MyData->addPoints($data_values,"ScoreA");  
	$MyData->setSerieDescription("ScoreA","Application A");
	
	/* Define the absissa serie */
	$MyData->addPoints($legend_values,"Labels");
	$MyData->setAbscissa("Labels");
	
	/* Create the pChart object */
	$myPicture = new pImage($width,$height,$MyData,TRUE);
	
	/* Set the default font properties */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size,"R"=>80,"G"=>80,"B"=>80));
	
	$water_mark_height = 0;
	$water_mark_width = 0;
	if (!empty($water_mark)) {
		if (is_array($water_mark)) {
			if (!empty($water_mark['file'])) {
				$water_mark = $water_mark['file'];
			}
		}
		
		$size_water_mark = getimagesize($water_mark);
		$water_mark_height = $size_water_mark[1];
		$water_mark_width = $size_water_mark[0];
		
		$myPicture->drawFromPNG(($width - $water_mark_width),
			($height - $water_mark_height) - 50, $water_mark);
	}
	
	
	/* Create the pPie object */ 
	$PieChart = new pPie($myPicture,$MyData);
	foreach ($legend_values as $key => $value) {
		if (isset($colors[$value])) {
			$PieChart->setSliceColor($key, hex_2_rgb($colors[$value]));
		}
	}
	
	/* Draw an AA pie chart */
	switch($graph_type) {
		case "pie2d":
			$PieChart->draw2DPie($width/4,$height/2,array("DataGapAngle"=>0,"DataGapRadius"=>0, "Border"=>FALSE, "BorderR"=>200, "BorderG"=>200, "BorderB"=>200, "Radius"=>$width/4, "ValueR"=>0, "ValueG"=>0, "ValueB"=>0, "WriteValues"=>TRUE));
			break;
		case "pie3d":
			$PieChart->draw3DPie($width/4, $height/2,array("DataGapAngle"=>5,"DataGapRadius"=>6, "Border"=>TRUE, "Radius"=>$width/4, "ValueR"=>0, "ValueG"=>0, "ValueB"=>0, "WriteValues"=>TRUE, "SecondPass"=>FALSE));
			break;
	}
	
	/* Write down the legend next to the 2nd chart*/
		//Calculate the bottom margin from the size of string in each index
	$max_chars = graph_get_max_index($legend_values);
	
	if ($legend_position != 'hidden') {
		// This is a hardcore adjustment to match most of the graphs, please don't alter
		$legend_with_aprox = 32 + (9.5 * $max_chars);
		
		$PieChart->drawPieLegend($width - $legend_with_aprox, 5, array("R"=>255,"G"=>255,"B"=>255, "BoxSize"=>10)); 
	}
	
	/* Enable shadow computing */ 
	$myPicture->setShadow(TRUE,
		array("X" => 3, "Y" => 3, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));
	
	/* Render the picture */
	$myPicture->stroke();
}

function pch_ring_graph ($graph_type, $data_values, $legend_values, $width,
	$height, $font, $water_mark, $font_size, $legend_position, $colors) {
	/* CAT:Ring charts */
	
	/* Create and populate the pData object */
	$MyData = new pData();   
	$MyData->addPoints($data_values,"ScoreA");  
	$MyData->setSerieDescription("ScoreA","Application A");
	
	/* Define the absissa serie */
	$MyData->addPoints($legend_values,"Labels");
	$MyData->setAbscissa("Labels");
	
	/* Create the pChart object */
	$myPicture = new pImage($width,$height,$MyData,TRUE);
	
	/* Set the default font properties */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size,"R"=>80,"G"=>80,"B"=>80));
	
	$water_mark_height = 0;
	$water_mark_width = 0;
	if (!empty($water_mark)) {
		if (is_array($water_mark)) {
			if (!empty($water_mark['file'])) {
				$water_mark = $water_mark['file'];
			}
		}
		
		$size_water_mark = getimagesize($water_mark);
		$water_mark_height = $size_water_mark[1];
		$water_mark_width = $size_water_mark[0];
		
		$myPicture->drawFromPNG(($width - $water_mark_width),
			($height - $water_mark_height) - 50, $water_mark);
	}
	
	
	/* Create the pPie object */ 
	$PieChart = new pPie($myPicture,$MyData);
	foreach ($legend_values as $key => $value) {
		if (isset($colors[$value])) {
			$PieChart->setSliceColor($key, hex_2_rgb($colors[$value]));
		}
	}
	
	/* Draw an AA pie chart */
	$PieChart->draw3DRing($width/3, $height/2,array("InnerRadius"=>100, "InnerRadius"=>10,"DrawLabels"=>TRUE,"LabelStacked"=>FALSE,"Precision"=>2,"Border"=>FALSE,"WriteValues"=>TRUE,"ValueR"=>0,"ValueG"=>0,"ValueB"=>0,"ValuePadding" => 15));
			
	
	/* Write down the legend next to the 2nd chart*/
		//Calculate the bottom margin from the size of string in each index
	$max_chars = graph_get_max_index($legend_values);
	
	if ($legend_position != 'hidden') {
		// This is a hardcore adjustment to match most of the graphs, please don't alter
		$legend_with_aprox = 150 + (4.5 * $max_chars);
		
		$PieChart->drawPieLegend($width - $legend_with_aprox, 10, array("R"=>255,"G"=>255,"B"=>255, "BoxSize"=>10)); 
	}
	
	/* Enable shadow computing */ 
	$myPicture->setShadow(TRUE,
		array("X" => 3, "Y" => 3, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));
	
	/* Render the picture */
	$myPicture->stroke();
}

function pch_kiviat_graph ($graph_type, $data_values, $legend_values, $width,
	$height, $font, $font_size) {
	/* CAT:Radar/Polar charts */
	
	/* Create and populate the pData object */
	$MyData = new pData();   
	$MyData->addPoints($data_values,"ScoreA");  
	$MyData->setSerieDescription("ScoreA","Application A");
	
	/* Define the absissa serie */
	$MyData->addPoints($legend_values,"Labels");
	$MyData->setAbscissa("Labels");
	
	/* Create the pChart object */
	$myPicture = new pImage($width,$height,$MyData,TRUE);
	
	/* Set the default font properties */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size,"R"=>80,"G"=>80,"B"=>80));
	
	/* Create the pRadar object */ 
	$SplitChart = new pRadar();
	
	/* Draw a radar chart */ 
	$myPicture->setGraphArea(20,25,$width-10,$height-10);
	
	/* Draw an AA pie chart */
	switch($graph_type) {
		case "radar":
			$Options = array("SkipLabels"=>0,"LabelPos"=>RADAR_LABELS_HORIZONTAL,
				"LabelMiddle"=>FALSE,"Layout"=>RADAR_LAYOUT_STAR,
				"BackgroundGradient"=>array("StartR"=>255,"StartG"=>255,"StartB"=>255,
				"StartAlpha"=>100,"EndR"=>207,"EndG"=>227,"EndB"=>125,"EndAlpha"=>50), 
				"FontName"=>$font,"FontSize"=>$font_size);
			$SplitChart->drawRadar($myPicture,$MyData,$Options); 
			break;
		case "polar":
			$Options = array("Layout"=>RADAR_LAYOUT_CIRCLE,"BackgroundGradient"=>array("StartR"=>255,"StartG"=>255,"StartB"=>255,"StartAlpha"=>100,"EndR"=>207,"EndG"=>227,"EndB"=>125,"EndAlpha"=>50),
				"FontName"=>$font,"FontSize"=>$font_size); 
			$SplitChart->drawRadar($myPicture,$MyData,$Options); 
			break;
	}
	
	/* Render the picture */
	$myPicture->stroke(); 
}

function pch_bar_graph ($graph_type, $index, $data, $width, $height, $font,
	$antialiasing, $rgb_color = false, $xaxisname = "", $yaxisname = "",
	$show_values = false, $legend = array(), $fine_colors = array(), $water_mark = '', $font_size) {
	/* CAT: Vertical Bar Chart */
	if (!is_array($legend) || empty($legend)) {
		unset($legend);
	}
	
	/* Create and populate the pData object */
	$MyData = new pData();
	$overridePalette = array();
	foreach ($data as $i => $values) {
		$MyData->addPoints($values,$i);
		
		if (!empty($rgb_color)) {
			$MyData->setPalette($i, 
				array("R" => $rgb_color[$i]['color']["R"], 
					"G" => $rgb_color[$i]['color']["G"], 
					"B" => $rgb_color[$i]['color']["B"],
					"BorderR" => $rgb_color[$i]['border']["R"], 
					"BorderG" => $rgb_color[$i]['border']["G"], 
					"BorderB" => $rgb_color[$i]['border']["B"], 
					"Alpha" => $rgb_color[$i]['alpha']));
		}
		
		// Assign cyclic colors to bars if are setted
		if ($fine_colors) {
			$c = 0;
			foreach ($values as $ii => $vv) {
				if (!isset($fine_colors[$c])) {
					$c = 0;
				}
				$overridePalette[$ii] = $fine_colors[$c];
				$c++;
			}
		}
		else {
			$overridePalette = false;
		}
	}
	
	$MyData->setAxisName(0,$yaxisname);
	$MyData->addPoints($index,"Xaxis");
	$MyData->setSerieDescription("Xaxis", $xaxisname);
	$MyData->setAbscissa("Xaxis");
	
	
	/* Create the pChart object */
	$myPicture = new pImage($width,$height,$MyData);
	
	
	
	/* Turn of Antialiasing */
	$myPicture->Antialias = $antialiasing;
	
	/* Add a border to the picture */
	//$myPicture->drawRectangle(0,0,$width,$height,array("R"=>0,"G"=>0,"B"=>0));
	
	/* Turn on shadow computing */ 
	$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>120,"G"=>120,"B"=>120,"Alpha"=>10)); 
	
	$pdf = get_parameter('pdf',false);
	
	if($pdf == true){
		$font_size = $font_size+1;
	}
	
	
	
	/* Set the default font */
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size));
	
	/* Draw the scale */
	// TODO: AvoidTickWhenEmpty = FALSE When the distance between two ticks will be less than 50 px
	// TODO: AvoidTickWhenEmpty = TRUE When the distance between two ticks will be greater than 50 px
	
	//Calculate the top margin from the size of string in each index
	$max_chars = graph_get_max_index($index);
	$margin_top = 10 * $max_chars;
	
	switch($graph_type) {
		case "vbar":
			$scaleSettings = array("AvoidTickWhenEmpty" => FALSE, "AvoidGridWhenEmpty" => FALSE, 
				"GridR"=>1000,"GridG"=>1000,"GridB"=>1000,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE, 
				"Mode"=>SCALE_MODE_START0, "LabelRotation" => 45);
			$margin_left = 40+50;
			$margin_right = 90;
			$margin_top = 10;
			$margin_bottom = (3 * $max_chars)+80;
			break;
		case "hbar":
			$scaleSettings = array("GridR"=>1000,"GridG"=>1000,"GridB"=>1000,"DrawSubTicks"=>TRUE,
				"CycleBackground"=>TRUE, "Mode"=>SCALE_MODE_START0, "Pos"=>SCALE_POS_TOPBOTTOM, 
				"LabelValuesRotation" => 30);
			$margin_left = $font_size * $max_chars;
			$margin_right = 15;
			$margin_top = 40;
			$margin_bottom = 10;
			break;
	}
	
	/* Define the chart area */
	$myPicture->setGraphArea($margin_left, $margin_top, $width - $margin_right, $height - $margin_bottom);
	
	$myPicture->drawScale($scaleSettings);
	
	/* Turn on shadow computing */ 
	$myPicture->setShadow(TRUE,array("X"=>0,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
	
	
	/* Draw the chart */
	$settings = array("ForceTransparency"=>"-1", "Gradient"=>FALSE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayValues"=>$show_values,"DisplayZeroValues"=>FALSE,"DisplayR"=>100,"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE, "OverrideColors"=>$overridePalette);
	
	/* goes through a series of colors and assigns them to the bars, when it ends it starts from the beginning */
	
	for ($i=0,$j=1; $i < count($settings['OverrideColors']); $i++) { 
		
		switch ($j) {
			case 1:
				$settings['OverrideColors'][$i]['R'] = 43;
				$settings['OverrideColors'][$i]['G'] = 98;
				$settings['OverrideColors'][$i]['B'] = 201;
				$j++;
				break;
				
			case 2:
				$settings['OverrideColors'][$i]['R'] = 243;
				$settings['OverrideColors'][$i]['G'] = 86;
				$settings['OverrideColors'][$i]['B'] = 157;
				$j++;
				break;
					
			case 3:
				$settings['OverrideColors'][$i]['R'] = 191;
				$settings['OverrideColors'][$i]['G'] = 191;
				$settings['OverrideColors'][$i]['B'] = 191;
				$j++;
				break;
						
			case 4:
				$settings['OverrideColors'][$i]['R'] = 251;
				$settings['OverrideColors'][$i]['G'] = 183;
				$settings['OverrideColors'][$i]['B'] = 50;
				$j++;
				break;
							
			case 5:
				$settings['OverrideColors'][$i]['R'] = 157;
				$settings['OverrideColors'][$i]['G'] = 117;
				$settings['OverrideColors'][$i]['B'] = 177;
				$j++;
				break;
								
			case 6:
				$settings['OverrideColors'][$i]['R'] = 39;
				$settings['OverrideColors'][$i]['G'] = 172;
				$settings['OverrideColors'][$i]['B'] = 151;
				$j++;
				break;
									
			case 7:
				$settings['OverrideColors'][$i]['R'] = 171;
				$settings['OverrideColors'][$i]['G'] = 42;
				$settings['OverrideColors'][$i]['B'] = 46;
				$j++;
				break;
				
			case 8:
				$settings['OverrideColors'][$i]['R'] = 185;
				$settings['OverrideColors'][$i]['G'] = 218;
				$settings['OverrideColors'][$i]['B'] = 87;
				$j++;
				break;
				
			case 9:
				$settings['OverrideColors'][$i]['R'] = 60;
				$settings['OverrideColors'][$i]['G'] = 182;
				$settings['OverrideColors'][$i]['B'] = 203;
				$j++;
				break;
					
			case 10:
				$settings['OverrideColors'][$i]['R'] = 105;
				$settings['OverrideColors'][$i]['G'] = 65;
				$settings['OverrideColors'][$i]['B'] = 179;
				$j++;
				break;
						
			case 11:
				$settings['OverrideColors'][$i]['R'] = 228;
				$settings['OverrideColors'][$i]['G'] = 35;
				$settings['OverrideColors'][$i]['B'] = 102;
				$j++;
				break;
				
			case 12:
				$settings['OverrideColors'][$i]['R'] = 252;
				$settings['OverrideColors'][$i]['G'] = 130;
				$settings['OverrideColors'][$i]['B'] = 53;
				$j = 1;
				break;
					
								
			default:
					
				break;
		
		}
				
	}
		
	$myPicture->drawBarChart($settings);
	
	// Paint the water mark at the last moment to show it in front
	if (!empty($water_mark)) {
		$size_water_mark = getimagesize($water_mark);
		$water_mark_width = $size_water_mark[0];
		
		$myPicture->drawFromPNG(($width - $water_mark_width - $margin_right),
			$margin_top, $water_mark);
	}
	
	/* Render the picture */
	$myPicture->stroke(); 
}

function pch_threshold_graph ($graph_type, $index, $data, $width, $height, $font,
	$antialiasing, $xaxisname = "", $yaxisname = "", $title = "",
	$show_values = false, $show_legend = false, $font_size) {
	/* CAT:Threshold Chart */
	
	/* Create and populate the pData object */
	$MyData = new pData();  
	$MyData->addPoints($data,"DEFCA");
	$MyData->setAxisName(0,$yaxisname);
	$MyData->setAxisDisplay(0,AXIS_FORMAT_CURRENCY);
	$MyData->addPoints($index,"Labels");
	$MyData->setSerieDescription("Labels",$xaxisname);
	$MyData->setAbscissa("Labels");
	$MyData->setPalette("DEFCA",array("R"=>55,"G"=>91,"B"=>127));
	
	/* Create the pChart object */
	$myPicture = new pImage(700,230,$MyData);
	$myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,array("StartR"=>220,"StartG"=>220,"StartB"=>220,"EndR"=>255,"EndG"=>255,"EndB"=>255,"Alpha"=>100));
	$myPicture->drawRectangle(0,0,699,229,array("R"=>200,"G"=>200,"B"=>200));
	
	/* Write the picture title */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size));
	$myPicture->drawText(60,35,$title,array("FontSize"=>$font_size,"Align"=>TEXT_ALIGN_BOTTOMLEFT));
	
	/* Do some cosmetic and draw the chart */
	$myPicture->setGraphArea(60,40,670,190);
	$myPicture->drawFilledRectangle(60,40,670,190,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
	$myPicture->drawScale(array("GridR"=>180,"GridG"=>180,"GridB"=>180, "Mode" => SCALE_MODE_START0));
	$myPicture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size));
	$settings = array("Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayValues"=>$show_values,"DisplayZeroValues"=>FALSE,"DisplayR"=>100,"DisplayG"=>100,"DisplayB"=>100,"DisplayShadow"=>TRUE,"Surrounding"=>5,"AroundZero"=>FALSE);
	$myPicture->drawSplineChart($settings);
	$myPicture->setShadow(FALSE);
	
	if ($show_legend) {
		/* Write the chart legend */ 
		$myPicture->drawLegend(643,210,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL)); 
	}
	
	/* Render the picture */
	$myPicture->stroke();
}

function pch_bullet_chart($graph_type, $data, $legend,
			$width, $height, $font, $water_mark, $font_size, $legend_position, $colors) {
	
	
	/* Create and populate the pData object */
	$MyData = new pData();
	
	foreach ($data as $key => $dat) {
		$MyData->addPoints($dat, $key);
	}
	$MyData->setPalette(__("Min"),array("R"=>55,"G"=>91,"B"=>127));
	$MyData->setPalette(__("Actual"),array("R"=>70,"G"=>130,"B"=>180));
	$MyData->setPalette(__("Max"),array("R"=>221,"G"=>221,"B"=>221));
	
	$MyData->addPoints($legend,"Labels");
	
	
	$MyData->setAbscissa("Labels");
	$MyData->setSerieDescription("Labels", __("Agents/Modules"));
	
	$height_t = ($height * count($data) ) + 40;
	$height_t = $height;
	$max_chars = graph_get_max_index($legend);
	$width_t = ($width + ( 100 + $max_chars));
	
	/* Create the pChart object */
	$myPicture = new pImage($width_t, $height_t,$MyData);
	
	/* Write the picture title */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size));

	/* Write the chart title */ 
	$myPicture->setFontProperties(array("FontName"=>$font,"FontSize"=>$font_size));
	
	$height_t - 10;
	/* Draw the scale and chart */
	$myPicture->setGraphArea(250,20,($width + 100), $height_t);
	$myPicture->drawScale(array("Pos"=>SCALE_POS_TOPBOTTOM, "Mode"=>SCALE_MODE_ADDALL_START0,
		  "LabelingMethod"=>LABELING_DIFFERENT, "GridR"=>255, "GridG"=>255,
		  "GridB"=>255, "GridAlpha"=>50, "TickR"=>0,"TickG"=>0, "TickB"=>0, 
		  "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, 
		  "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, 
		  "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, 
		  "DrawYLines"=>ALL));
	$myPicture->drawStackedBarChart(array("MODE"=>SCALE_MODE_START0));
	 
	/* Write the chart legend */
	//$myPicture->drawLegend(0,205,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
	
	/* Render the picture */
	$myPicture->stroke(); 
}
?>