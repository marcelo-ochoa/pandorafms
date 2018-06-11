<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once($config['homedir'] . "/include/graphs/fgraph.php");
include_once($config['homedir'] . "/include/functions_reporting.php");
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_users.php");

function get_graph_statistics ($chart_array) {
	global $config;

	/// IMPORTANT!
	///
	/// The calculus for AVG, MIN and MAX values are in this function
	/// because it must be done based on graph array data not using reporting
	/// function to get coherent data between stats and graph visualization

	$stats = array ();

	$count = 0;

	$size = sizeof($chart_array);

	//Initialize stats array
	$stats = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);

	foreach ($chart_array as $item) {

		//Sum all values later divide by the number of elements
		$stats['avg'] = $stats['avg'] + $item;

		//Get minimum
		if ($stats['min'] == null) {
			$stats['min'] = $item;
		}
		else if ($item < $stats['min']) {
			$stats['min'] = $item;
		}

		//Get maximum
		if ($stats['max'] == null) {
			$stats['max'] = $item;
		}
		else if ($item > $stats['max']) {
			$stats['max'] = $item;
		}

		$count++;

		//Get last data
		if ($count == $size) {
			$stats['last'] = $item;
		}
	}

	//End the calculus for average
	if ($count > 0) {

		$stats['avg'] = $stats['avg'] / $count;
	}

	//Format stat data to display properly
	$stats['last'] = remove_right_zeros(number_format($stats['last'], $config['graph_precision']));
	$stats['avg'] = remove_right_zeros(number_format($stats['avg'], $config['graph_precision']));
	$stats['min'] = remove_right_zeros(number_format($stats['min'], $config['graph_precision']));
	$stats['max'] = remove_right_zeros(number_format($stats['max'], $config['graph_precision']));

	return $stats;
}

function get_statwin_graph_statistics ($chart_array, $series_suffix = '') {

	/// IMPORTANT!
	///
	/// The calculus for AVG, MIN and MAX values are in this function
	/// because it must be done based on graph array data not using reporting
	/// function to get coherent data between stats and graph visualization

	$stats = array ();

	$count = 0;

	$size = sizeof($chart_array);

	//Initialize stats array
	$stats['sum'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	$stats['min'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	$stats['max'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);

	foreach ($chart_array as $item) {
		if ($series_suffix != '') {
			if (isset($item['sum' . $series_suffix]))
				$item['sum'] = $item['sum' . $series_suffix];
			if (isset($item['min' . $series_suffix]))
				$item['min'] = $item['min' . $series_suffix];
			if (isset($item['max' . $series_suffix]))
				$item['max'] = $item['max' . $series_suffix];
		}

		//Get stats for normal graph
		if (isset($item['sum']) && $item['sum']) {

			//Sum all values later divide by the number of elements
			$stats['sum']['avg'] = $stats['sum']['avg'] + $item['sum'];

			//Get minimum
			if ($stats['sum']['min'] == null) {
				$stats['sum']['min'] = $item['sum'];
			}
			else if ($item['sum'] < $stats['sum']['min']) {
				$stats['sum']['min'] = $item['sum'];
			}

			//Get maximum
			if ($stats['sum']['max'] == null) {
				$stats['sum']['max'] = $item['sum'];
			}
			else if ($item['sum'] > $stats['sum']['max']) {
				$stats['sum']['max'] = $item['sum'];
			}
		}

		//Get stats for min graph
		if (isset($item['min']) && $item['min']) {
			//Sum all values later divide by the number of elements
			$stats['min']['avg'] = $stats['min']['avg'] + $item['min'];

			//Get minimum
			if ($stats['min']['min'] == null) {
				$stats['min']['min'] = $item['min'];
			}
			else if ($item['min'] < $stats['min']['min']) {
				$stats['min']['min'] = $item['min'];
			}

			//Get maximum
			if ($stats['min']['max'] == null) {
				$stats['min']['max'] = $item['min'];
			}
			else if ($item['min'] > $stats['min']['max']) {
				$stats['min']['max'] = $item['min'];
			}
		}

		//Get stats for max graph
		if (isset($item['max']) && $item['max']) {
			//Sum all values later divide by the number of elements
			$stats['max']['avg'] = $stats['max']['avg'] + $item['max'];

			//Get minimum
			if ($stats['max']['min'] == null) {
				$stats['max']['min'] = $item['max'];
			}
			else if ($item['max'] < $stats['max']['min']) {
				$stats['max']['min'] = $item['max'];
			}

			//Get maximum
			if ($stats['max']['max'] == null) {
				$stats['max']['max'] = $item['max'];
			}
			else if ($item['max'] > $stats['max']['max']) {
				$stats['max']['max'] = $item['max'];
			}
		}

		//Count elements
		$count++;

		//Get last data
		if ($count == $size) {
			if (isset($item['sum']) && $item['sum']) {
				$stats['sum']['last'] = $item['sum'];
			}

			if (isset($item['min']) && $item['min']) {
				$stats['min']['last'] = $item['min'];
			}

			if (isset($item['max']) && $item['max']) {
				$stats['max']['last'] = $item['max'];
			}
		}
	}

	//End the calculus for average
	if ($count > 0) {

		$stats['sum']['avg'] = $stats['sum']['avg'] / $count;
		$stats['min']['avg'] = $stats['min']['avg'] / $count;
		$stats['max']['avg'] = $stats['max']['avg'] / $count;
	}

	//Format stat data to display properly
	$stats['sum']['last'] = round($stats['sum']['last'], 2);
	$stats['sum']['avg'] = round($stats['sum']['avg'], 2);
	$stats['sum']['min'] = round($stats['sum']['min'], 2);
	$stats['sum']['max'] = round($stats['sum']['max'], 2);

	$stats['min']['last'] = round($stats['min']['last'], 2);
	$stats['min']['avg'] = round($stats['min']['avg'], 2);
	$stats['min']['min'] = round($stats['min']['min'], 2);
	$stats['min']['max'] = round($stats['min']['max'], 2);

	$stats['max']['last'] = round($stats['max']['last'], 2);
	$stats['max']['avg'] = round($stats['max']['avg'], 2);
	$stats['max']['min'] = round($stats['max']['min'], 2);
	$stats['max']['max'] = round($stats['max']['max'], 2);

	return $stats;
}

function grafico_modulo_sparse_data_chart (
		$agent_module_id,
		$date_array,
		$data_module_graph,
		$params,
		$series_suffix
	) {

	global $config;

	if( $data_module_graph['id_module_type'] == 23 ||
		$data_module_graph['id_module_type'] == 3 ||
		$data_module_graph['id_module_type'] == 17 ||
		$data_module_graph['id_module_type'] == 10 ||
		$data_module_graph['id_module_type'] == 33 ){

//XXXXXXXXXXX SLICES
/*
"SELECT count(*) as data, min(utimestamp) as utimestamp
					FROM tagente_datos_string
					WHERE id_agente_modulo = 227
					AND utimestamp > 1527584831
					AND utimestamp < 1527671231
					GROUP by ROUND(utimestamp / 300);"
*/
		$data = db_get_all_rows_filter (
			'tagente_datos_string',
			array ('id_agente_modulo' => (int)$agent_module_id,
					"utimestamp > '". $date_array['start_date']. "'",
					"utimestamp < '". $date_array['final_date'] . "'",
					'group' => "ROUND(utimestamp / 300)",
					'order' => 'utimestamp ASC'),
			array ('count(*) as datos', 'min(utimestamp) as utimestamp'),
			'AND',
			$data_module_graph['history_db']
		);
	}
	else{
		/*
		if(true){
			$data = db_get_all_rows_filter (
				'tagente_datos',
				array ('id_agente_modulo' => (int)$agent_module_id,
						"utimestamp > '". $date_array['start_date']. "'",
						"utimestamp < '". $date_array['final_date'] . "'",
						'group' => "ROUND(utimestamp / 86400)",
						'order' => 'utimestamp ASC'),
				array ('max(datos) as datos', 'min(utimestamp) as utimestamp'),
				'AND',
				$data_module_graph['history_db']
			);
		}
		else{
			*/
			$data = db_get_all_rows_filter (
				'tagente_datos',
				array ('id_agente_modulo' => (int)$agent_module_id,
						"utimestamp > '". $date_array['start_date']. "'",
						"utimestamp < '". $date_array['final_date'] . "'",
						'order' => 'utimestamp ASC'),
				array ('datos', 'utimestamp'),
				'AND',
				$data_module_graph['history_db']
			);
		//}
	}

	if($data === false){
		$data = array();
	}

	// Get previous data
	$previous_data = modules_get_previous_data (
		$agent_module_id,
		$date_array['start_date']
	);

	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $date_array['start_date'];
		unset($previous_data['id_agente_modulo']);
		array_unshift ($data, $previous_data);
	}

	// Get next data
	$nextData = modules_get_next_data (
		$agent_module_id,
		$date_array['final_date']
	);

	if ($nextData !== false) {
		unset($nextData['id_agente_modulo']);
		array_push ($data, $nextData);
	}
	else if (count ($data) > 0) {
		// Propagate the last known data to the end of the interval
		$nextData = array(
			'datos'      => $data[count($data)-1]['datos'],
			'utimestamp' => $date_array['final_date'],
		);
		array_push ($data, $nextData);
	}

	// Check available data
	if (count ($data) < 1) {
		//return fs_error_image ();
		return false;
	}

	$array_data = array();
	$min_value = PHP_INT_MAX-1;
	$max_value = PHP_INT_MIN+1;
	$array_percentil = array();

	foreach ($data as $k => $v) {
		//convert array
		if($params['flag_overlapped']){
			$array_data["sum" . $series_suffix]['data'][$k] = array(
				($v['utimestamp'] + $date_array['period']  )* 1000,
				$v['datos']
			);
		}
		else{
			$array_data["sum" . $series_suffix]['data'][$k] = array(
				$v['utimestamp'] * 1000,
				$v['datos']
			);
		}

		//min
		if($min_value > $v['datos']){
			$min_value = $v['datos'];
		}

		//max
		if($max_value < $v['datos']){
			$max_value = $v['datos'];
		}

		//avg
		$sum_data += $v['datos'];
		$count_data++;

		//percentil
		if (!is_null($params['percentil']) && $params['percentil']) {
			$array_percentil[] = $v['datos'];
		}
	}

	$array_data["sum" . $series_suffix]['min']            = $min_value;
	$array_data["sum" . $series_suffix]['max']            = $max_value;
	$array_data["sum" . $series_suffix]['avg']            = $sum_data/$count_data;
	$array_data["sum" . $series_suffix]['agent_module_id']= $agent_module_id;
	$array_data["sum" . $series_suffix]['id_module_type'] = $data_module_graph['id_module_type'];
	$array_data["sum" . $series_suffix]['agent_name']     = $data_module_graph['agent_name'];
	$array_data["sum" . $series_suffix]['module_name']    = $data_module_graph['module_name'];
	$array_data["sum" . $series_suffix]['agent_alias']    = $data_module_graph['agent_alias'];

	if (!is_null($params['percentil']) &&
		$params['percentil'] &&
		!$params['flag_overlapped']) {
		$percentil_result = get_percentile($params['percentil'], $array_percentil);
		$array_data["percentil" . $series_suffix]['data'][0] = array(
			$date_array['start_date'] * 1000,
			$percentil_result
		);
		$array_data["percentil" . $series_suffix]['data'][1] = array(
			$date_array['final_date'] * 1000,
			$percentil_result
		);
		$array_data["percentil" . $series_suffix]['agent_module_id'] = $agent_module_id;
	}
	return $array_data;
}

function grafico_modulo_sparse_data(
	$agent_module_id, $date_array,
	$data_module_graph, $params,
	$series_suffix) {

	global $config;
	global $array_events_alerts;

	if($params['fullscale']){
		$array_data = fullscale_data(
			$agent_module_id,
			$date_array,
			$params['show_unknown'],
			$params['percentil'],
			$series_suffix,
			$params['flag_overlapped']
		);

		$array_data["sum" . $series_suffix]['agent_module_id']= $agent_module_id;
		$array_data["sum" . $series_suffix]['id_module_type'] = $data_module_graph['id_module_type'];
		$array_data["sum" . $series_suffix]['agent_name']     = $data_module_graph['agent_name'];
		$array_data["sum" . $series_suffix]['module_name']    = $data_module_graph['module_name'];
		$array_data["sum" . $series_suffix]['agent_alias']    = $data_module_graph['agent_alias'];
	}
	else{
		$array_data = grafico_modulo_sparse_data_chart (
			$agent_module_id,
			$date_array,
			$data_module_graph,
			$params,
			$series_suffix
		);
	}

	if($array_data === false){
		return false;
	}

	//XXX Esto es para un tipo especifico de report que consiste en pasarle un intervalo y hacer suma media y avg.
	if($params['force_interval'] != ''){
		$period_time_interval = $date_array['period'] * 1000;
		$start_period = $date_array['start_date'] * 1000;
		$i = 0;

		$sum_data = 0;
		$count_data = 0;
		$data_last_acum = $array_data['sum1']['data'][0][1];

		while($period_time_interval > 0) {
			foreach ($array_data['sum1']['data'] as $key => $value) {
				if($value[0] >= $start_period && $value[0] < $start_period + $params['time_interval'] * 1000){
					$sum_data = $value[1];
					$array_data_only[] = $value[1];
					$count_data++;
					unset($array_data['sum1']['data'][$key]);
				}
				else{
					if($params['force_interval'] == 'max_only'){
						$acum_array_data[$i][0] = $start_period;
						if(is_array($array_data_only) && count($array_data_only) > 0){
							$acum_array_data[$i][1] = max($array_data_only);
							$data_last_acum = $array_data_only[count($array_data_only) - 1];
						}
						else{
							$acum_array_data[$i][1] = $data_last_acum;
						}
					}

					if($params['force_interval'] == 'min_only'){
						$acum_array_data[$i][0] = $start_period;
						if(is_array($array_data_only) && count($array_data_only) > 0){
							$acum_array_data[$i][1] = min($array_data_only);
							$data_last_acum = $array_data_only[count($array_data_only) - 1];
						}
						else{
							$acum_array_data[$i][1] = $data_last_acum;
						}
					}

					if($params['force_interval'] == 'avg_only'){
						$acum_array_data[$i][0] = $start_period;
						if(is_array($array_data_only) && count($array_data_only) > 0){
							$acum_array_data[$i][1] = $sum_data / $count_data;
						}
						else{
							$acum_array_data[$i][1] = $data_last_acum;
						}
					}

					$start_period = $start_period + $params['time_interval'] * 1000;
					$array_data_only = array();
					$sum_data = 0;
					$count_data = 0;
					$i++;
					break;
				}
			}
			$period_time_interval = $period_time_interval - $params['time_interval'];
		}
		$array_data['sum1']['data'] = $acum_array_data;
	}

	if($params['percentil']){
		$percentil_value = $array_data['percentil' . $series_suffix]['data'][0][1];
	}
	else{
		$percentil_value = 0;
	}

	$events = array();
	if(isset($array_data['sum' . $series_suffix]['max'])){
		$max = $array_data['sum'. $series_suffix]['max'];
		$min = $array_data['sum'. $series_suffix]['min'];
		$avg = $array_data['sum'. $series_suffix]['avg'];
	}

	if(!$params['flag_overlapped']){
		if($params['fullscale']){
			if(	$params['show_unknown'] &&
				isset($array_data['unknown' . $series_suffix]) &&
				is_array($array_data['unknown' . $series_suffix]['data']) ){
				foreach ($array_data['unknown' . $series_suffix]['data'] as $key => $s_date) {
					if ($s_date[1] == 1) {
						$array_data['unknown' . $series_suffix]['data'][$key] = array($s_date[0], $max * 1.05);
					}
				}
			}
		}
		else{
			if(	$params['show_unknown'] ) {
				$unknown_events = db_get_module_ranges_unknown(
					$agent_module_id,
					$date_array['start_date'],
					$date_array['final_date'],
					$data_module_graph['history_db'],
					1 // fix the time ranges to start_date - final_date
				);

				if($unknown_events !== false){
					foreach ($unknown_events as $key => $s_date) {
						if( isset($s_date['time_from'])) {
							$array_data['unknown' . $series_suffix]['data'][] = array(
								($s_date['time_from'] - 1) * 1000,
								0
							);

							$array_data['unknown' . $series_suffix]['data'][] = array(
								$s_date['time_from'] * 1000,
								$max * 1.05
							);
						}
						else{
							$array_data['unknown' . $series_suffix]['data'][] = array(
								$date_array['start_date'] * 1000,
								$max * 1.05
							);
						}

						if( isset($s_date['time_to']) ){
							$array_data['unknown' . $series_suffix]['data'][] = array(
								$s_date['time_to'] * 1000,
								$max * 1.05
							);

							$array_data['unknown' . $series_suffix]['data'][] = array(
								($s_date['time_to'] + 1) * 1000,
								0
							);
						}
						else{
							$array_data['unknown' . $series_suffix]['data'][] = array(
								$date_array['final_date'] * 1000,
								$max * 1.05
							);
						}
					}
				}
			}
		}

		if ($params['show_events']  ||
			$params['show_alerts'] ) {

			$events = db_get_all_rows_filter (
				'tevento',
				array ('id_agentmodule' => $agent_module_id,
						"utimestamp > " . $date_array['start_date'],
						"utimestamp < " . $date_array['final_date'],
						'order' => 'utimestamp ASC'
					),
				false,
				'AND',
				$data_module_graph['history_db']
			);

			$alerts_array = array();
			$events_array = array();

			if($events && is_array($events)){
				$count_events=0;
				$count_alerts=0;
				foreach ($events as $k => $v) {
					if (strpos($v["event_type"], "alert") !== false){
						if($params['flag_overlapped']){
							$alerts_array['data'][$count_alerts] = array(
								($v['utimestamp'] + $date_array['period'] *1000),
								$max * 1.10
							);
						}
						else{
							$alerts_array['data'][$count_alerts] = array(
								($v['utimestamp']*1000),
								$max * 1.10
							);
						}
						$count_alerts++;
					}
					else{
						if($params['flag_overlapped']){
							if( ( strstr($v['event_type'], 'going_up') ) ||
									( strstr($v['event_type'], 'going_down') ) ){
								$events_array['data'][$count_events] = array(
									(($v['utimestamp'] + 1 + $date_array['period']) * 1000),
									$max * 1.15
								);
							}
							else{
								$events_array['data'][$count_events] = array(
									($v['utimestamp'] + $date_array['period'] *1000),
									$max * 1.15
								);
							}
						}
						else{
							if( ( strstr($v['event_type'], 'going_up') ) ||
									( strstr($v['event_type'], 'going_down') ) ){
								$events_array['data'][$count_events] = array(
									(($v['utimestamp'] + 1) *1000),
									$max * 1.15
								);
							}
							else{
								$events_array['data'][$count_events] = array(
									($v['utimestamp']*1000),
									$max * 1.15
								);
							}
						}
						$count_events++;
					}
				}
			}
		}

		if($params['show_events']){
			$array_data['event' . $series_suffix] = $events_array;
		}

		if($params['show_alerts']){
			$array_data['alert' . $series_suffix] = $alerts_array;
		}
	}

	if ($params['return_data'] == 1) {
		return $array_data;
	}

	$array_events_alerts[$series_suffix] = $events;

	return $array_data;
}

/*
	$params =array(
		'agent_module_id'     => $agent_module_id,
		'period'              => $period,
		'show_events'         => false,
		'width'               => $width,
		'height'              => $height,
		'title'               => '',
		'unit_name'           => null,
		'show_alerts'         => false,
		'date'                => 0,
		'unit'                => '',
		'baseline'            => 0,
		'return_data'         => 0,
		'show_title'          => true,
		'only_image'          => false,
		'homeurl'             => $config['homeurl'],
		'ttl'                 => 1,
		'adapt_key'           => '',
		'compare'             => false,
		'show_unknown'        => false,
		'menu'                => true,
		'backgroundColor'     => 'white',
		'percentil'           => null,
		'dashboard'           => false,
		'vconsole'            => false,
		'type_graph'          => 'area',
		'fullscale'           => false,
		'id_widget_dashboard' => false,
		'force_interval'      => '',
		'time_interval'       => 300,
		'array_data_create'   => 0,
		'show_legend'         => true,
		'show_overview'       => true,
		'return_img_base_64'  => false,
		'image_treshold'      => false,
		'graph_combined'      => false
	);
 */
function grafico_modulo_sparse ($params) {
	global $config;

	/*XXXXXXXXXXXX Documnetar
	*Set all variable
	*/

	if(!isset($params) || !is_array($params)){
		return false;
	}

	if(!isset($params['agent_module_id'])){
		return false;
	}
	else{
		$agent_module_id = $params['agent_module_id'];
	}

	if(!isset($params['period'])){
		return false;
	}

	if(!isset($params['show_events'])){
		$params['show_events'] = false;
	}

	if(!isset($params['width'])){
		$params['width'] = '90%';
	}

	if(!isset($params['height'])){
		$params['height'] = 450;
	}

	if(!isset($params['title'])){
		$params['title'] = '';
	}

	if(!isset($params['unit_name'])){
		$params['unit_name'] = null;
	}

	if(!isset($params['show_alerts'])){
		$params['show_alerts'] = false;
	}

	if(!isset($params['date']) || !$params['date']){
		$params['date'] = get_system_time();
	}

	if(!isset($params['unit'])){
		$params['unit'] = '';
	}

	if(!isset($params['baseline'])){
		$params['baseline'] = 0;
	}

	if(!isset($params['return_data'])){
		$params['return_data'] = 0;
	}

	if(!isset($params['show_title'])){
		$show_title = true;
	}

	if(!isset($params['only_image'])){
		$params['only_image'] = false;
	}

	if(!isset($params['homeurl'])){
		$params['homeurl'] = $config['homeurl'];
	}

	if(!isset($params['ttl'])){
		$params['ttl'] = 1;
	}

	if(!isset($params['adapt_key'])){
		$params['adapt_key'] = '';
	}

	if(!isset($params['compare'])){
		$params['compare'] = false;
	}

	if(!isset($params['show_unknown'])){
		$params['show_unknown'] = false;
	}

	if(!isset($params['menu'])){
		$params['menu'] = true;
	}

	if(!isset($params['show_legend'])){
		$params['show_legend'] = true;
	}

	if(!isset($params['show_overview'])){
		$params['show_overview'] = true;
	}

	if(!isset($params['show_export_csv'])){
		$params['show_export_csv'] = true;
	}

	if(!isset($params['backgroundColor'])){
		$params['backgroundColor'] = 'white';
	}

	if(!isset($params['percentil'])){
		$params['percentil'] = null;
	}

	if(!isset($params['dashboard'])){
		$params['dashboard'] = false;
	}

	if(!isset($params['vconsole']) || $params['vconsole'] == false){
		$params['vconsole'] = false;
	}
	else{
		$params['menu'] = false;
	}

	if(!isset($params['type_graph'])){
		$params['type_graph'] = $config['type_module_charts'];
	}

	if(!isset($params['fullscale'])){
		$params['fullscale'] = false;
	}

	if(!isset($params['id_widget_dashboard'])){
		$params['id_widget_dashboard'] = false;
	}

	if(!isset($params['force_interval'])){
		$params['force_interval'] = '';
	}

	if(!isset($params['time_interval'])){
		$params['time_interval'] = 300;
	}

	if(!isset($params['array_data_create'])){
		$params['array_data_create'] = 0;
	}

	if(!isset($params['return_img_base_64'])){
		$params['return_img_base_64'] = false;
	}

	if(!isset($params['image_treshold'])){
		$params['image_treshold'] = false;
	}

	if(!isset($params['graph_combined'])){
		$params['graph_combined'] = false;
	}

	$params['font']       = $config['fontpath'];
	$params['font-size']  = $config['font_size'];

	if($params['only_image']){
		return generator_chart_to_pdf('sparse', $params);
	}

	global $graphic_type;
	global $array_events_alerts;

	$array_data   = array();
	$legend       = array();
	$array_events_alerts = array();


	$date_array = array();
	$date_array["period"]     = $params['period'];
	$date_array["final_date"] = $params['date'];
	$date_array["start_date"] = $params['date'] - $params['period'];

	if($agent_module_id){
		$module_data = db_get_row_sql (
			'SELECT * FROM tagente_modulo
			WHERE id_agente_modulo = ' .
			$agent_module_id
		);

		$data_module_graph = array();
		$data_module_graph['history_db']     = db_search_in_history_db($date_array["start_date"]);
		$data_module_graph['agent_name']     = modules_get_agentmodule_agent_name($agent_module_id);
		$data_module_graph['agent_alias']    = modules_get_agentmodule_agent_alias($agent_module_id);
		$data_module_graph['agent_id']       = $module_data['id_agente'];
		$data_module_graph['module_name']    = $module_data['nombre'];
		$data_module_graph['id_module_type'] = $module_data['id_tipo_modulo'];
		$data_module_graph['module_type']    = modules_get_moduletype_name($data_module_graph['id_module_type']);
		$data_module_graph['uncompressed']   = is_module_uncompressed($data_module_graph['module_type']);
		$data_module_graph['w_min']    		 = $module_data['min_warning'];
		$data_module_graph['w_max']   		 = $module_data['max_warning'];
		$data_module_graph['w_inv']    		 = $module_data['warning_inverse'];
		$data_module_graph['c_min']    		 = $module_data['min_critical'];
		$data_module_graph['c_max']    		 = $module_data['max_critical'];
		$data_module_graph['c_inv']    		 = $module_data['critical_inverse'];
	}
	else{
		$data_module_graph = false;
	}

	//format of the graph
	if (empty($params['unit'])) {
		$params['unit'] = $module_data['unit'];
		if(modules_is_unit_macro($params['unit'])){
			$params['unit'] = "";
		}
	}

	if(!$params['array_data_create']){
		if ($params['compare'] !== false) {
			$series_suffix = 2;

			$date_array_prev['final_date'] = $date_array['start_date'];
			$date_array_prev['start_date'] = $date_array['start_date'] - $date_array['period'];
			$date_array_prev['period']     = $date_array['period'];

			if ($params['compare'] === 'overlapped') {
				$params['flag_overlapped'] = 1;
			}
			else{
				$params['flag_overlapped'] = 0;
			}

			$array_data = grafico_modulo_sparse_data(
				$agent_module_id,
				$date_array_prev,
				$data_module_graph,
				$params,
				$series_suffix
			);

			switch ($params['compare']) {
				case 'separated':
				case 'overlapped':
					// Store the chart calculated
					$array_data_prev = $array_data;
					$legend_prev     = $legend;
					break;
			}
		}

		$series_suffix = 1;
		$params['flag_overlapped'] = 0;

		$array_data = grafico_modulo_sparse_data(
			$agent_module_id,
			$date_array,
			$data_module_graph,
			$params,
			$series_suffix
		);

		if($params['compare']){
			if ($params['compare'] === 'overlapped') {
				$array_data = array_merge($array_data, $array_data_prev);
				$legend     = array_merge($legend, $legend_prev);
			}
		}
	}
	else{
		$array_data = $params['array_data_create'];
	}

	if($params['return_data']){
		return $array_data;
	}

	$series_type_array = series_type_graph_array(
		$array_data,
		$params
	);

	$series_type = $series_type_array['series_type'];
	$legend      = $series_type_array['legend'];
	$color       = $series_type_array['color'];

	if($config["fixed_graph"] == false){
		$water_mark = array(
			'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
			'url' => ui_get_full_url(
				"/images/logo_vertical_water.png",
				false,
				false,
				false
			)
		);
	}

	$data_module_graph['series_suffix'] = $series_suffix;

	// Check available data
	if ($params['compare'] === 'separated') {
		if (!empty($array_data)) {
			$return = area_graph(
				$agent_module_id,
				$array_data,
				$legend,
				$series_type,
				$color,
				$date_array,
				$data_module_graph,
				$params,
				$water_mark,
				$array_events_alerts
			);
		}
		else{
			$return = graph_nodata_image($params['width'], $params['height']);
		}
		$return .= '<br>';
		if (!empty($array_data_prev)) {

			$series_type_array = series_type_graph_array(
				$array_data_prev,
				$params
			);

			$series_type = $series_type_array['series_type'];
			$legend      = $series_type_array['legend'];
			$color       = $series_type_array['color'];

			$return .= area_graph(
				$agent_module_id,
				$array_data_prev,
				$legend,
				$series_type,
				$color,
				$date_array_prev,
				$data_module_graph,
				$params,
				$water_mark,
				$array_events_alerts
			);
		}
		else{
			$return .= graph_nodata_image($params['width'], $params['height']);
		}
	}
	else{
		if (!empty($array_data)) {
			$return = area_graph(
				$agent_module_id,
				$array_data,
				$legend,
				$series_type,
				$color,
				$date_array,
				$data_module_graph,
				$params,
				$water_mark,
				$array_events_alerts
			);
		}
		else{
			$return = graph_nodata_image(
				$params['width'],
				$params['height']
			);
		}
	}

return $return;
}

function graph_get_formatted_date($timestamp, $format1, $format2) {
	global $config;

	if ($config['flash_charts']) {
		$date = date("$format1 $format2", $timestamp);
	}
	else {
		$date = date($format1, $timestamp);
		if ($format2 != '') {
			$date .= "\n".date($format2, $timestamp);
		}
	}

	return $date;
}

/**
 * Produces a combined/user defined graph
 *
 * @param array List of source modules
 * @param array List of weighs for each module
 * @param int Period (in seconds)
 * @param int Width, in pixels
 * @param int Height, in pixels
 * @param string Title for graph
 * @param string Unit name, for render in legend
 * @param int Show events in graph (set to 1)
 * @param int Show alerts in graph (set to 1)
 * @param int Pure mode (without titles) (set to 1)
 * @param int Date to start of getting info.
 * @param mixed If is a projection graph this parameter will be module data with prediction data (the projection)
 * or false in other case.
 * @param array List of names for the items. Should have the same size as the module list.
 * @param array List of units for the items. Should have the same size as the module list.
 * @param bool Show the last value of the item on the list.
 * @param bool Show the max value of the item on the list.
 * @param bool Show the min value of the item on the list.
 * @param bool Show the average value of the item on the list.
 *
 * @return Mixed
 */


/*
	$params =array(
		'period'              => $period,
		'show_events'         => false,
		'width'               => $width,
		'height'              => $height,
		'title'               => '',
		'unit_name'           => null,
		'show_alerts'         => false,
		'date'                => 0,
		'unit'                => '',
		'only_image'          => false,
		'homeurl'             => '',
		'ttl'                 => 1,
		'percentil'           => null,
		'dashboard'           => false,
		'vconsole'            => false,
		'fullscale'           => false,
		'id_widget_dashboard' => false,
	);

	$params_combined = array(
		'weight_list'    => array(),
		'stacked'        => 0,
		'projection'     => false,
		'labels'         => array(),
		'from_interface' => false,
		'summatory'      => 0,
		'average'        => 0,
		'modules_series' => 0,
		'id_graph'       => 0,
		'return'         => 1
	);
 */

function graphic_combined_module (
	$module_list,
	$params,
	$params_combined
) {

	if(!isset($params_combined['from_interface'])){
		$params_combined['from_interface'] = false;
	}

	if(!isset($params_combined['stacked'])){
		if ($params_combined['from_interface']) {
			if ($config["type_interface_charts"] == 'line') {
				$params_combined['stacked'] = CUSTOM_GRAPH_LINE;
			}
			else {
				$params_combined['stacked'] = CUSTOM_GRAPH_AREA;
			}
		}
		else {
			if ($id_graph == 0) {
				$params_combined['stacked'] = CUSTOM_GRAPH_AREA;
			}
			else {
				$params_combined['stacked'] = db_get_row('tgraph', 'id_graph', $id_graph);
			}
		}
	}

	$params['stacked'] = $params_combined['stacked'];

	if(!isset($params_combined['projection'])){
		$params_combined['projection'] = false;
	}
	else{
		$params['stacked'] = 'area';
	}

	if(!isset($params_combined['labels'])){
		$params_combined['labels'] = array();
	}

	if(!isset($params_combined['summatory'])){
		$params_combined['summatory'] = 0;
	}

	if(!isset($params_combined['average'])){
		$params_combined['average'] = 0;
	}

	if(!isset($params_combined['modules_series'])){
		$params_combined['modules_series'] = 0;
	}

	if(!isset($params_combined['return'])){
		$params_combined['return'] = 1;
	}

	if(!isset($params_combined['id_graph'])){
		$params_combined['id_graph'] = 0;
	}

	if(!isset($params['percentil'])){
		$params_combined['percentil'] = null;
	}
	else{
		$params_combined['percentil'] = $params['percentil'];
	}


	if(!isset($params['period'])){
		return false;
	}

	if(!isset($params['width'])){
		$params['width'] = '90%';
	}

	if(!isset($params['height'])){
		$params['height'] = 450;
	}

	if(!isset($params['title'])){
		$params['title'] = '';
	}

	if(!isset($params['unit_name'])){
		$params['unit_name'] = null;
	}

	if(!isset($params['show_alerts'])){
		$params['show_alerts'] = false;
	}

	if(!isset($params['date']) || !$params['date']){
		$params['date'] = get_system_time();
	}

	if(!isset($params['only_image'])){
		$params['only_image'] = false;
	}

	if(!isset($params['ttl'])){
		$params['ttl'] = 1;
	}

	if(!isset($params['backgroundColor'])){
		$params['backgroundColor'] = 'white';
	}

	if(!isset($params['dashboard'])){
		$params['dashboard'] = false;
	}

	if(!isset($params['menu']) || $params['only_image']){
		$params['menu'] = true;
	}
	else{
		$params['menu'] = false;
	}

	if(!isset($params['vconsole']) || $params['vconsole'] == false){
		$params['vconsole'] = false;
	}
	else{
		$params['menu'] = false;
	}

	if(!isset($params['type_graph'])){
		$params['type_graph'] = $config['type_module_charts'];
	}

	if(!isset($params['percentil'])){
		$params['percentil'] = null;
	}

	if(!isset($params['fullscale'])){
		$params['fullscale'] = false;
	}

	if(!isset($params['id_widget_dashboard'])){
		$params['id_widget_dashboard'] = false;
	}

	if(!isset($params['homeurl'])){
		$params['homeurl'] = ui_get_full_url(false, false, false, false);
	}

	if(!isset($params['show_legend'])){
		$params['show_legend'] = true;
	}

	if(!isset($params['show_overview'])){
		$params['show_overview'] = true;
	}

	if(!isset($params['show_export_csv'])){
		$params['show_export_csv'] = true;
	}

	if(!isset($params['return_img_base_64'])){
		$params['return_img_base_64'] = false;
	}

	if(!isset($params['image_treshold'])){
		$params['image_treshold'] = false;
	}

	if(!isset($params['show_unknown'])){
		$params['show_unknown'] = false;
	}

	$params['graph_combined'] = true;

	if($params['only_image']){
		return generator_chart_to_pdf('combined', $params, $params_combined, $module_list);
	}

	global $config;
	global $graphic_type;

	$sources = false;
	if ($params_combined['id_graph'] == 0) {
		$count_modules = count($module_list);

		if(!$params_combined['weight_list']){
			$weights = array_fill(0, $count_modules, 1);
		}

		if ($count_modules > 0){
			$sources = true;
		}
	}
	else {
		$sources = db_get_all_rows_field_filter(
			'tgraph_source',
			'id_graph',
			$params_combined['id_graph']
		);

		$series = db_get_all_rows_sql(
			'SELECT summatory_series,average_series,modules_series 
			FROM tgraph 
			WHERE id_graph = '.
			$params_combined['id_graph']
		);

		$summatory      = $series[0]['summatory_series'];
		$average        = $series[0]['average_series'];
		$modules_series = $series[0]['modules_series'];

		$weights = array ();
		$labels  = array ();
		$modules = array ();

		if(isset($sources) && is_array($sources)){
			foreach ($sources as $source) {
				array_push ($modules, $source['id_agent_module']);
				array_push ($weights, $source['weight']);
				if ($source['label'] != ''){
					$item['type']            = 'custom_graph';
					$item['id_agent']        = agents_get_module_id($source['id_agent_module']);
					$item['id_agent_module'] = $source['id_agent_module'];
					$labels[$source['id_agent_module']] = reporting_label_macro($item, $source['label']);
				}
			}
		}
	}

	if(isset($summatory)){
		$params_combined['summatory'] = $summatory;
	}

	if(isset($average)){
		$params_combined['average'] = $average;
	}

	if(isset($modules_series)){
		$params_combined['modules_series'] = $modules_series;
	}

	if(isset($labels)){
		$params_combined['labels'] = $labels;
	}

	if(isset($weights)){
		$params_combined['weight_list'] = $weights;
	}

	if(!$module_list){
		$module_list = $modules;
	}

	if ($sources === false) {
		if ($params_combined['return']){
			return false;
		}
		else{
			ui_print_info_message (
				array (
					'no_close' => true,
					'message' =>  __('No items.')
				)
			);
			return;
		}
	}

	$width            = $params['width'];
	$height           = $params['height'];
	$homeurl          = $params['homeurl'];
	$ttl              = $params['ttl'];
	$background_color = $params['backgroundColor'];
	$datelimit        = $date_array["start_date"];
	$fixed_font_size  = $config['font_size'];
	$flash_charts     = false;

	if($config["fixed_graph"] == false){
		$water_mark = array(
			'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
			'url' => ui_get_full_url(
				"/images/logo_vertical_water.png",
				false,
				false,
				false
			)
		);
	}

	//XXX arreglar estas
	$long_index      = '';

	switch ($params_combined['stacked']) {
		default:
		case CUSTOM_GRAPH_STACKED_LINE:
		case CUSTOM_GRAPH_STACKED_AREA:
		case CUSTOM_GRAPH_AREA:
		case CUSTOM_GRAPH_LINE:
			$date_array = array();
			$date_array["period"]     = $params['period'];
			$date_array["final_date"] = $params['date'];
			$date_array["start_date"] = $params['date'] - $params['period'];

			$i=0;
			$array_data = array();
			foreach ($module_list as $key => $agent_module_id) {
				$module_data = db_get_row_sql (
					'SELECT * FROM tagente_modulo
					WHERE id_agente_modulo = ' .
					$agent_module_id
				);

				$data_module_graph = array();
				$data_module_graph['history_db']     = db_search_in_history_db($date_array["start_date"]);
				$data_module_graph['agent_name']     = modules_get_agentmodule_agent_name($agent_module_id);
				$data_module_graph['agent_alias']    = modules_get_agentmodule_agent_alias($agent_module_id);
				$data_module_graph['agent_id']       = $module_data['id_agente'];
				$data_module_graph['module_name']    = $module_data['nombre'];
				$data_module_graph['id_module_type'] = $module_data['id_tipo_modulo'];
				$data_module_graph['module_type']    = modules_get_moduletype_name($data_module_graph['id_module_type']);
				$data_module_graph['uncompressed']   = is_module_uncompressed($data_module_graph['module_type']);
				$data_module_graph['w_min']    		 = $module_data['min_warning'];
				$data_module_graph['w_max']   		 = $module_data['max_warning'];
				$data_module_graph['w_inv']    		 = $module_data['warning_inverse'];
				$data_module_graph['c_min']    		 = $module_data['min_critical'];
				$data_module_graph['c_max']    		 = $module_data['max_critical'];
				$data_module_graph['c_inv']    		 = $module_data['critical_inverse'];
				$data_module_graph['module_id']      = $agent_module_id;

				//stract data
				$array_data_module = grafico_modulo_sparse_data(
					$agent_module_id,
					$date_array,
					$data_module_graph,
					$params,
					$i
				);

				$series_suffix     = $i;

				//convert to array graph and weight
				foreach ($array_data_module as $key => $value) {
					$array_data[$key] = $value;
					if($params_combined['weight_list'][$i] > 1){
						foreach ($value['data'] as $k => $v) {
							$array_data[$key]['data'][$k][1] = $v[1] * $params_combined['weight_list'][$i];
						}
					}
				}

				$max = $array_data['sum' . $i]['max'];
				$min = $array_data['sum' . $i]['min'];
				$avg = $array_data['sum' . $i]['avg'];

				$percentil_value = $array_data['percentil' . $i]['data'][0][1];

				if($config["fixed_graph"] == false){
					$water_mark = array(
						'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
						'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
				}

				//Work around for fixed the agents name with huge size chars.
				$fixed_font_size = $config['font_size'];

				//$array_events_alerts[$series_suffix] = $events;
				$i++;
			}

			if($params_combined['projection'] && is_array($params_combined['projection'])){
				$array_data['projection']['data']= $params_combined['projection'];
			}

			//summatory and average series
			if($params_combined['stacked'] == CUSTOM_GRAPH_AREA  || $params_combined['stacked'] == CUSTOM_GRAPH_LINE) {
				if($params_combined['summatory'] || $params_combined['average']) {
					$array_data = combined_graph_summatory_average (
						$array_data,
						$params_combined['average'],
						$params_combined['summatory'],
						$params_combined['modules_series']
					);
				}
			}

			$series_type_array = series_type_graph_array(
				$array_data,
				$params_combined
			);

			$series_type = $series_type_array['series_type'];
			$legend      = $series_type_array['legend'];
			$color       = $series_type_array['color'];

			$threshold_data = array();
			if ($params_combined['from_interface']) {
				$yellow_threshold = 0;
				$red_threshold = 0;

				$yellow_up = 0;
				$red_up = 0;

				$yellow_inverse = 0;
				$red_inverse = 0;

				$compare_warning = false;
				$compare_critical = false;

				$do_it_warning_min = true;
				$do_it_critical_min = true;

				$do_it_warning_max = true;
				$do_it_critical_max = true;

				$do_it_warning_inverse = true;
				$do_it_critical_inverse = true;

				foreach ($module_list as $index => $id_module) {
					// Get module warning_min and critical_min
					$warning_min  = db_get_value('min_warning','tagente_modulo','id_agente_modulo',$id_module);
					$critical_min = db_get_value('min_critical','tagente_modulo','id_agente_modulo',$id_module);

					if ($index == 0) {
						$compare_warning = $warning_min;
					}
					else {
						if ($compare_warning != $warning_min) {
							$do_it_warning_min = false;
						}
					}

					if ($index == 0) {
						$compare_critical = $critical_min;
					}
					else {
						if ($compare_critical != $critical_min) {
							$do_it_critical_min = false;
						}
					}
				}

				if ($do_it_warning_min || $do_it_critical_min) {
					foreach ($module_list as $index => $id_module) {
						$warning_max  = db_get_value('max_warning','tagente_modulo','id_agente_modulo',$id_module);
						$critical_max = db_get_value('max_critical','tagente_modulo','id_agente_modulo',$id_module);

						if ($index == 0) {
							$yellow_up = $warning_max;
						}
						else {
							if ($yellow_up != $warning_max) {
								$do_it_warning_max = false;
							}
						}

						if ($index == 0) {
							$red_up = $critical_max;
						}
						else {
							if ($red_up != $critical_max) {
								$do_it_critical_max = false;
							}
						}
					}
				}

				if ($do_it_warning_min || $do_it_critical_min) {
					foreach ($module_list as $index => $id_module) {
						$warning_inverse  = db_get_value('warning_inverse','tagente_modulo','id_agente_modulo',$id_module);
						$critical_inverse = db_get_value('critical_inverse','tagente_modulo','id_agente_modulo',$id_module);

						if ($index == 0) {
							$yellow_inverse = $warning_inverse;
						}
						else {
							if ($yellow_inverse != $warning_inverse) {
								$do_it_warning_inverse = false;
							}
						}

						if ($index == 0) {
							$red_inverse = $critical_inverse;
						}
						else {
							if ($red_inverse != $critical_inverse) {
								$do_it_critical_inverse = false;
							}
						}
					}
				}

				if ($do_it_warning_min && $do_it_warning_max && $do_it_warning_inverse) {
					$yellow_threshold = $compare_warning;
					$threshold_data['yellow_up']      = $yellow_up;
					$threshold_data['yellow_inverse'] = (bool)$yellow_inverse;
				}

				if ($do_it_critical_min && $do_it_critical_max && $do_it_critical_inverse) {
					$red_threshold = $compare_critical;
					$threshold_data['red_up']      = $red_up;
					$threshold_data['red_inverse'] = (bool)$red_inverse;
				}

				$params['threshold_data'] = $threshold_data;
			}

			$output = area_graph(
				$agent_module_id,
				$array_data,
				$legend,
				$series_type,
				$color,
				$date_array,
				$data_module_graph,
				$params,
				$water_mark,
				$array_events_alerts
			);

			break;
		case CUSTOM_GRAPH_BULLET_CHART_THRESHOLD:
		case CUSTOM_GRAPH_BULLET_CHART:
			if($params_combined['stacked'] == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD){
				$acumulador = 0;
				foreach ($module_list as $module_item) {
					$module = $module_item;
					$query_last_value = sprintf('
						SELECT datos
						FROM tagente_datos
						WHERE id_agente_modulo = %d
							AND utimestamp < %d
							ORDER BY utimestamp DESC',
						$module, $params['date']);
					$temp_data = db_get_value_sql($query_last_value);
					if ($acumulador < $temp_data){
						$acumulador = $temp_data;
					}
				}
			}

			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}

				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;

				$search_in_history_db = db_search_in_history_db($datelimit);

				$temp[$module] = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $params['date']);
				$temp_data = db_get_value_sql($query_last_value);

				if ($temp_data) {
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}

				if ( !empty($params_combined['labels']) && isset($params_combined['labels'][$module]) ){
					$label = io_safe_input($params_combined['labels'][$module]);
				}else{
					$alias = db_get_value ("alias","tagente","id_agente",$temp[$module]['id_agente']);
					$label = $alias . ': ' . $temp[$module]['nombre'];
				}

				$temp[$module]['label'] = $label;
				$temp[$module]['value'] = $value;
				$temp_max = reporting_get_agentmodule_data_max($module, $params['period'], $params['date']);
				if ($temp_max < 0)
					$temp_max = 0;
				if (isset($acumulador)){
					$temp[$module]['max'] = $acumulador;
				}else{
					$temp[$module]['max'] = ($temp_max === false) ? 0 : $temp_max;
				}

				$temp_min = reporting_get_agentmodule_data_min($module, $params['period'], $params['date']);
				if ($temp_min < 0)
					$temp_min = 0;
				$temp[$module]['min'] = ($temp_min === false) ? 0 : $temp_min;

				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[0])) {
						metaconsole_restore_db();
					}
				}
			}

			$graph_values = $temp;

			$width = 1024;
			$height = 50;

			$color = color_graph_array();

			$output = stacked_bullet_chart(
				$graph_values,
				$width,
				$height,
				$color,
				$module_name_list,
				$long_index,
				ui_get_full_url("images/image_problem_area_small.png", false, false, false),
				"",
				"",
				$water_mark,
				$config['fontpath'],
				($config['font_size']+1),
				"",
				$ttl,
				$homeurl,
				$background_color
			);

			break;

		case CUSTOM_GRAPH_GAUGE:
			$i = 0;
			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}

				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;

				$temp[$module] = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $params['date']);
				$temp_data = db_get_value_sql($query_last_value);
				if ( $temp_data ) {
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}
				$temp[$module]['label'] = ($params_combined['labels'][$module] != '') ? $params_combined['labels'][$module] : $temp[$module]['nombre'];

				$temp[$module]['value'] = $value;
				$temp[$module]['label'] = ui_print_truncate_text($temp[$module]['label'],"module_small",false,true,false,"..");

				if ($temp[$module]['unit'] == '%') {
					$temp[$module]['min'] =	0;
					$temp[$module]['max'] = 100;
				}
				else {
					$min = $temp[$module]['min'];
					if ($temp[$module]['max'] == 0)
						$max = reporting_get_agentmodule_data_max($module, $params['period'], $params['date']);
					else
						$max = $temp[$module]['max'];
					$temp[$module]['min'] = ($min == 0 ) ? 0 : $min;
					$temp[$module]['max'] = ($max == 0 ) ? 100 : $max;
				}
				$temp[$module]['gauge'] = uniqid('gauge_');

				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[0])) {
						metaconsole_restore_db();
					}
				}
				$i++;
			}

			$graph_values = $temp;

			$color = color_graph_array();

			$width = 200;
			$height = 200;

			$output = stacked_gauge(
				$graph_values,
				$width,
				$height,
				$color,
				$module_name_list,
				ui_get_full_url("images/image_problem_area_small.png", false, false, false),
				$config['fontpath'],
				$fixed_font_size,
				"",
				$homeurl
			);

			break;
		case CUSTOM_GRAPH_HBARS:
		case CUSTOM_GRAPH_VBARS:
			$label = '';
			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}

				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;

				$module_data = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $params['date']);
				$temp_data = db_get_value_sql($query_last_value);

				$agent_name = io_safe_output(
					modules_get_agentmodule_agent_name ($module));

				if (!empty($params_combined['labels']) && isset($params_combined['labels'][$module]) ){
					$label = $params_combined['labels'][$module];
				}else {
					$alias = db_get_value ("alias","tagente","id_agente",$module_data['id_agente']);
					$label = $alias . " - " .$module_data['nombre'];
				}

				$temp[$label]['g'] = round($temp_data,4);

				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[0])) {
						metaconsole_restore_db();
					}
				}
			}

			$color = color_graph_array();

			$graph_values = $temp;

			$width = 1024;
			$height = 500;
			$flash_charts = true;

			if($params_combined['stacked'] == CUSTOM_GRAPH_HBARS){
				$output = hbar_graph(
					true,
					$graph_values,
					$width,
					$height,
					$color,
					$module_name_list,
					$long_index,
					ui_get_full_url("images/image_problem_area_small.png", false, false, false),
					"",
					"",
					$water_mark,
					$config['fontpath'],
					$fixed_font_size,
					"",
					$ttl,
					$homeurl,
					$background_color,
					'black'
				);
			}

			if($params_combined['stacked'] == CUSTOM_GRAPH_VBARS){
				$output = vbar_graph(
					true,
					$graph_values,
					$width,
					$height,
					$color,
					$module_name_list,
					$long_index,
					ui_get_full_url("images/image_problem_area_small.png", false, false, false),
					"",
					"",
					$water_mark,
					$config['fontpath'],
					$fixed_font_size,
					"",
					$ttl,
					$homeurl,
					$background_color,
					true,
					false,
					"black"
				);
			}

			break;
		case CUSTOM_GRAPH_THERMOMETER:
			$datelimit = $params['date'] - $params['period'];
			$i = 0;
			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}

				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;

				$temp[$module] = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $params['date']);
				$temp_data = db_get_value_sql($query_last_value);
				if ( $temp_data ) {
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}
				$temp[$module]['label'] = ($labels[$module] != '') ? $labels[$module] : $temp[$module]['nombre'];
				$temp[$module]['value'] = $value;
				$temp[$module]['label'] = ui_print_truncate_text($temp[$module]['label'],"module_small",false,true,false,"..");

				if ($temp[$module]['unit'] == '%') {
					$temp[$module]['min'] =	0;
					$temp[$module]['max'] = 100;
				}
				else {
					$min = $temp[$module]['min'];
					if ($temp[$module]['max'] == 0)
						$max = reporting_get_agentmodule_data_max($module,$params['period'],$params['date']);
					else
						$max = $temp[$module]['max'];
					$temp[$module]['min'] = ($min == 0 ) ? 0 : $min;
					$temp[$module]['max'] = ($max == 0 ) ? 100 : $max;
				}
				$temp[$module]['gauge'] = uniqid('gauge_');

				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[0])) {
						metaconsole_restore_db();
					}
				}
				$i++;

				$color = color_graph_array();

				$graph_values = $temp;

				return stacked_thermometers(
					$flash_charts,
					$graph_values,
					$width,
					$height,
					$color,
					$module_name_list,
					$long_index,
					ui_get_full_url("images/image_problem_area_small.png", false, false, false),
					"",
					"",
					$water_mark,
					$config['fontpath'],
					$fixed_font_size,
					"",
					$ttl,
					$homeurl,
					$background_color
				);
			}
			break;
		case CUSTOM_GRAPH_PIE:
			$total_modules = 0;
			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}

				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;

				$data_module = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp > %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $datelimit, $params['date']);
				$temp_data = db_get_value_sql($query_last_value);

				if ( $temp_data ){
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}
				$total_modules += $value;

				if ( !empty($params_combined['labels']) && isset($params_combined['labels'][$module]) ){
					$label = io_safe_output($params_combined['labels'][$module]);
				}else {
					$alias = db_get_value ("alias","tagente","id_agente",$data_module['id_agente']);
					$label = io_safe_output($alias . ": " . $data_module['nombre']);
				}

				$temp[$label] = array('value'=>$value,
										'unit'=>$data_module['unit']);
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[0])) {
						metaconsole_restore_db();
					}
				}
			}

			$temp['total_modules'] = $total_modules;

			$graph_values = $temp;

			$width  = 1024;
			$height = 500;

			$color  = color_graph_array();

			$output = ring_graph(
				true,
				$graph_values,
				$width,
				$height,
				$others_str,
				$homeurl,
				$water_mark,
				$config['fontpath'],
				($config['font_size']+1),
				$ttl,
				false,
				$color,
				false,
				$background_color
			);

			break;
	}

	if ($params_combined['return']){
		return $output;
	}

	echo $output;
}

function combined_graph_summatory_average ($array_data, $average = false, $summatory = false, $modules_series = false, $baseline = false){
	if(isset($array_data) && is_array($array_data)){
		foreach ($array_data as $key => $value) {
			if(strpos($key, 'sum') !== false){
				$data_array_reverse[$key] = array_reverse($value['data']);
				if(!$modules_series) {
					unset($array_data[$key]);
				}
			}
		}

		if(isset($data_array_reverse) && is_array($data_array_reverse)){
			$array_sum_reverse = array();
			$array_avg_reverse = array();
			$data_array_prev = false;
			$data_array_pop = array();
			$count = 0;

			while(count($data_array_reverse['sum0']) > 0){
				foreach ($data_array_reverse as $key_reverse => $value_reverse) {
					if(is_array($value_reverse) && count($value_reverse) > 0){
						$data_array_pop[$key_reverse] = array_pop($data_array_reverse[$key_reverse]);
					}
				}

				if(isset($data_array_pop) && is_array($data_array_pop)){
					$acum_data  = 0;
					$acum_array = array();
					$sum_data   = 0;
					$count_pop  = 0;
					foreach ($data_array_pop as $key_pop => $value_pop) {
						if( $value_pop[0] > $acum_data ){
							if($acum_data != 0){
								$sum_data = $sum_data + $data_array_prev[$key_pop][1];
								$data_array_reverse[$key_pop][] = $value_pop;
								$data_array_prev[$acum_key] = $acum_array;
							}
							else{
								if($data_array_prev[$key_pop] == false){
									$data_array_prev[$key_pop] = $value_pop;
								}
								$acum_key   = $key_pop;
								$acum_data  = $value_pop[0];
								$acum_array = $value_pop;
								$sum_data   = $value_pop[1];
							}
						}
						elseif($value_pop[0] < $acum_data){
							$sum_data = $sum_data + $data_array_prev[$key_pop][1];
							$data_array_reverse[$acum_key][] = $acum_array;
							$data_array_prev[$key_pop] = $value_pop;
							$acum_key   = $key_pop;
							$acum_data  = $value_pop[0];
							$acum_array = $value_pop;
						}
						elseif($value_pop[0] == $acum_data){
							$data_array_prev[$key_pop] = $value_pop;
							$sum_data += $value_pop[1];
						}
						$count_pop++;
					}
					if($summatory){
						$array_sum_reverse[$count][0] = $acum_data;
						$array_sum_reverse[$count][1] = $sum_data;
					}
					if($average){
						$array_avg_reverse[$count][0] = $acum_data;
						$array_avg_reverse[$count][1] = $sum_data / $count_pop;
					}
				}
				$count++;
			}

			if($summatory && isset($array_sum_reverse) && is_array($array_sum_reverse) && count($array_sum_reverse) > 0){
				$array_data['summatory']['data']  = $array_sum_reverse;
				$array_data['summatory']['color'] = 'purple';
			}

			if($average && isset($array_avg_reverse) && is_array($array_avg_reverse) && count($array_avg_reverse) > 0){
				if($baseline){
					$array_data['baseline']['data']  = $array_avg_reverse;
					$array_data['baseline']['color'] = 'green';
				}
				else{
					$array_data['average']['data']  = $array_avg_reverse;
					$array_data['average']['color'] = 'orange';
				}
			}

		}
		return $array_data;
	}
	else{
		return false;
	}
}



















/**
 * Print a graph with access data of agents
 *
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param bool return or echo the result flag
 */
function graphic_agentaccess ($id_agent, $width, $height, $period = 0, $return = false, $tree = false) {
	global $config;
	global $graphic_type;

	$date       = get_system_time();
	$datelimit  = $date - $period;
	$data_array = array ();

	$data = db_get_all_rows_sql(
		"SELECT count(*) as data, min(utimestamp) as utimestamp
		FROM tagent_access
		WHERE id_agent = $id_agent
		AND utimestamp > $datelimit
		AND utimestamp < $date
		GROUP by ROUND(utimestamp / 300)"
	);

	if(isset($data) && is_array($data)){
		foreach ($data as $key => $value) {
			$data_array['Agent access']['data'][$key][0] = $value['utimestamp'] * 1000;
			$data_array['Agent access']['data'][$key][1] = $value['data'];
		}
		$data_array['Agent access']['color'] = 'green';
	}
	else{
		if ($return) {
			return graph_nodata_image($width, $height);
		}
		else{
			echo graph_nodata_image($width, $height);
		}
	}

	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}

	$params =array(
		'agent_module_id'     => false,
		'period'              => $period,
		'width'               => $width,
		'height'              => $height,
		'unit'                => $unit,
		'only_image'          => $only_image,
		'homeurl'             => $homeurl,
		'menu'                => true,
		'backgroundColor'     => 'white',
		'type_graph'          => 'area',
		'font'                => $config['fontpath'],
		'font-size'           => $config['font_size'],
		'array_data_create'   => $data_array,
		'show_overview'       => false,
		'show_export_csv'     => false,
		'vconsole'            => $tree
	);

	if ($return) {
		return grafico_modulo_sparse($params);
	}
	else {
		echo grafico_modulo_sparse($params);
	}
}

/**
 * Print a pie graph with alerts defined/fired data
 * 
 * @param integer Number of defined alerts
 * @param integer Number of fired alerts
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param bool return or echo flag
 */
function graph_alert_status ($defined_alerts, $fired_alerts, $width = 300, $height = 200, $return = false) {
	global $config;
	
	$data = array(__('Not fired alerts') => $defined_alerts - $fired_alerts, __('Fired alerts') => $fired_alerts);
	$colors = array(COL_NORMAL, COL_ALERTFIRED);
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	$out = pie2d_graph($config['flash_charts'], $data, $width, $height, __("other"),
		'', '', $config['fontpath'], $config['font_size'], 1, "hidden", $colors);
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

// If any value is negative, truncate it to 0
function truncate_negatives(&$element) {
	if ($element < 0) {
		$element = 0;
	}
}

/**
 * Print a pie graph with events data of agent or all agents (if id_agent = false)
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param bool return or echo flag
 * @param bool show_not_init flag
 */
function graph_agent_status ($id_agent = false, $width = 300, $height = 200, $return = false, $show_not_init = false, $data_agents=false) {
	global $config;

	if ($data_agents == false) {
		$groups = implode(',', array_keys(users_get_groups(false, 'AR', false)));
		$data = db_get_row_sql(sprintf('SELECT
				SUM(critical_count) AS Critical,
				SUM(warning_count) AS Warning,
				SUM(normal_count) AS Normal,
				SUM(unknown_count) AS Unknown
				%s
			FROM tagente ta LEFT JOIN tagent_secondary_group tasg
				ON ta.id_agente = tasg.id_agent
			WHERE
				ta.disabled = 0 AND
				%s
				(ta.id_grupo IN (%s) OR tasg.id_group IN (%s))',
			$show_not_init ? ', SUM(notinit_count) "Not init"' : '',
			empty($id_agent) ? '' : "ta.id_agente = $id_agent AND",
			$groups,
			$groups
		));
	} else {
		$data = $data_agents;
	}

	if (empty($data)) {
		$data = array();
	}
	
	array_walk($data, 'truncate_negatives');
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	//$colors = array(COL_CRITICAL, COL_WARNING, COL_NORMAL, COL_UNKNOWN);
	$colors[__('Critical')] = COL_CRITICAL;
	$colors[__('Warning')] = COL_WARNING;
	$colors[__('Normal')] = COL_NORMAL;
	$colors[__('Unknown')] = COL_UNKNOWN;
	
	if ($show_not_init) {
		$colors[__('Not init')] = COL_NOTINIT;
	}
	
	if (array_sum($data) == 0) {
		$data = array();
	}
	
	$out = pie2d_graph($config['flash_charts'], $data, $width, $height,
		__("other"), ui_get_full_url(false, false, false, false), '',
		$config['fontpath'], $config['font_size'], 1, "hidden", $colors);
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}


/**
 * Print a pie graph with events data of agent
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer id_agent Agent ID
 */
function graph_event_module ($width = 300, $height = 200, $id_agent) {
	global $config;
	global $graphic_type;

	// Fix: tag filters implemented! for tag functionality groups have to be all user_groups (propagate ACL funct!)
	$groups = users_get_groups($config["id_user"]);
	$tags_condition = tags_get_acl_tags($config['id_user'], array_keys($groups), 'ER', 'event_condition', 'AND');
	
	$data = array ();
	$max_items = 6;
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_evento) AS count_number,
					id_agentmodule
				FROM tevento
				WHERE tevento.id_agente = %d %s
				GROUP BY id_agentmodule ORDER BY count_number DESC LIMIT %d', $id_agent, $tags_condition, $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_evento) AS count_number,
					id_agentmodule
				FROM tevento
				WHERE tevento.id_agente = %d AND rownum <= %d
				GROUP BY id_agentmodule ORDER BY count_number DESC', $id_agent, $max_items);
			break;
	}
	
	$events = db_get_all_rows_sql ($sql);
	if ($events === false) {
		if (! $graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
		return;
	}
	
	foreach ($events as $event) {
		if ($event['id_agentmodule'] == 0) {
			$key = __('System') . ' ('.$event['count_number'].')';
		}
		else {
			$key = modules_get_agentmodule_name ($event['id_agentmodule']) .
				' ('.$event['count_number'].')';
		}
		
		$data[$key] = $event["count_number"];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	return pie3d_graph($config['flash_charts'], $data, $width, $height, __("other"),
		'', $water_mark, $config['fontpath'], $config['font_size'], 1, "bottom");
}

function progress_bar($progress, $width, $height, $title = '', $mode = 1, $value_text = false, $color = false, $options = false) {
	global $config;
	
	$out_of_lim_str = io_safe_output(__("Out of limits"));
	
	$title = "";
	
	if ($value_text === false) {
		$value_text = $progress . "%";
	}
	
	$colorRGB = '';
	if ($color !== false) {
		$colorRGB = html_html2rgb($color);
		$colorRGB = implode('|', $colorRGB);
	}
	
	$class_tag = '';
	$id_tag = '';
	if ($options !== false) {
		foreach ($options as $option_type => $option_value) {
			if ($option_type == 'class')
				$class_tag = ' class="' . $option_value . '" ';
			else if ($option_type == 'id')
				$id_tag = ' id="' . $option_value . '" ';
		}
	}
	
	require_once("include_graph_dependencies.php");
	include_graphs_dependencies($config['homedir'].'/');
	$src = ui_get_full_url(
		"/include/graphs/fgraph.php?graph_type=progressbar" .
		"&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str .
		"&title=".$title."&value_text=". $value_text . 
		"&colorRGB=". $colorRGB, false, false, false
		);
	
	return "<img title='" . $title . "' alt='" . $title . "'" . $class_tag . $id_tag . 
		" src='" . $src . "' />";
}

function progress_bubble($progress, $width, $height, $title = '', $mode = 1, $value_text = false, $color = false) {
	global $config;
	
	$hack_metaconsole = '';
	if (defined('METACONSOLE'))
		$hack_metaconsole = '../../';
	
	$out_of_lim_str = io_safe_output(__("Out of limits"));
	$title = "";
	
	if ($value_text === false) {
		$value_text = $progress . "%";
	}
	
	$colorRGB = '';
	if ($color !== false) {
		$colorRGB = html_html2rgb($color);
		$colorRGB = implode('|', $colorRGB);
	}
	
	require_once("include_graph_dependencies.php");
	include_graphs_dependencies($config['homedir'].'/');
	
	return "<img title='" . $title . "' alt='" . $title . "'" .
		" src='" . $config['homeurl'] . $hack_metaconsole . "/include/graphs/fgraph.php?graph_type=progressbubble" .
		"&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str .
		"&title=".$title."&value_text=". $value_text . 
		"&colorRGB=". $colorRGB . "' />";
}

function graph_sla_slicebar ($id, $period, $sla_min, $sla_max, $date, $daysWeek = null, $time_from = null, $time_to = null, $width, $height, $home_url, $ttl = 1, $data = false, $round_corner = null) {
	global $config;
	
	if ($round_corner === null) {
		$round_corner = $config['round_corner'];
	}
	
	// If the data is not provided, we got it
	if ($data === false) {
		$data = reporting_get_agentmodule_sla_array ($id, $period,
			$sla_min, $sla_max, $date, $daysWeek, null, null);
	}
	
	$col_planned_downtime = '#20973F';
	
	$colors = array(1 => COL_NORMAL,
		2 => COL_WARNING,
		3 => COL_CRITICAL,
		4 => COL_UNKNOWN,
		5 => COL_DOWNTIME,
		6 => COL_NOTINIT,
		7 => COL_IGNORED);
	
	return slicesbar_graph($data, $period, $width, $height, $colors,
		$config['fontpath'], $round_corner, $home_url, $ttl);
}

/**
 * Print a pie graph with purge data of agent
 * 
 * @param integer id_agent ID of agent to show
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_purge ($id_agent, $width = 380, $height = 300) {
	global $config;
	global $graphic_type;
	
	$filter = array();
	
	if ($id_agent < 1) {
		$query = "";
	}
	else {
		$modules = agents_get_modules($id_agent);
		$module_ids = array_keys($modules);
		
		if (!empty($module_ids))
			$filter['id_agente_modulo'] = $module_ids;
	}
	
	// All data (now)
	$time_now = time();
	
	// 1 day ago
	$time_1day = $time_now - SECONDS_1DAY;
	
	// 1 week ago
	$time_1week = $time_now - SECONDS_1WEEK;
	
	// 1 month ago
	$time_1month = $time_now - SECONDS_1MONTH;
	
	// Three months ago
	$time_3months = $time_now - SECONDS_3MONTHS;
	
	$query_error = false;
	
	// Data from 1 day ago
	$num_1day = 0;
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos
										WHERE utimestamp > ' . $time_1day);
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos_string
										WHERE utimestamp > ' . $time_1day);
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos_log4x
										WHERE utimestamp > ' . $time_1day);
	if ($num_1day >= 0) {
		// Data from 1 week ago
		$num_1week = 0;
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos_string
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos_log4x
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		if ($num_1week >= 0) {
			if ($num_1week > 0) {
				$num_1week = 0;
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_1week);
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos_string
													WHERE utimestamp > ' . $time_1week);
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos_log4x
													WHERE utimestamp > ' . $time_1week);
			}
			// Data from 1 month ago
			$num_1month = 0;
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos_string
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos_log4x
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			if ($num_1month >= 0) {
				if ($num_1month > 0) {
					$num_1month = 0;
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp > ' . $time_1month);
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos_string
														WHERE utimestamp > ' . $time_1month);
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos_log4x
														WHERE utimestamp > ' . $time_1month);
				}
				// Data from 3 months ago
				$num_3months = 0;
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				if ($num_3months >= 0) {
					if ($num_3months > 0) {
						$num_3months = 0;
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
					}
					// All data
					$num_all = 0;
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					if ($num_all >= 0) {
						$num_older = $num_all - $num_3months;
						if ($config['history_db_enabled'] == 1) {
							// All data in common and history database
							$num_all_w_history = 0;
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							if ($num_all_w_history >= 0) {
								$num_history = $num_all_w_history - $num_all;
							}
						}
					}
				}
			}
		}
	}
	else if (($num_1day == 0) && ($num_1week == 0) && ($num_1month == 0) && ($num_3months == 0) && ($num_all == 0)) {
		//If no data, returns empty
		$query_error = true;
	}
	
	// Error
	if ($query_error || $num_older < 0 || ($config['history_db_enabled'] == 1 && $num_history < 0)
			|| (empty($num_1day) && empty($num_1week) && empty($num_1month)
				&& empty($num_3months) && empty($num_all) 
				&& ($config['history_db_enabled'] == 1 && empty($num_all_w_history)))) {
		return html_print_image('images/image_problem_area_small.png', true);
	}

	// Data indexes
	$str_1day = __("Today");
	$str_1week = "1 ".__("Week");
	$str_1month = "1 ".__("Month");
	$str_3months = "3 ".__("Months");
	$str_older = "> 3 ".__("Months");
	
	// Filling the data array
	$data = array();
	if (!empty($num_1day))
		$data[$str_1day] = $num_1day;
	if (!empty($num_1week))
		$data[$str_1week] = $num_1week;
	if (!empty($num_1month))
		$data[$str_1month] = $num_1month;
	if (!empty($num_3months))
		$data[$str_3months] = $num_3months;
	if (!empty($num_older))
		$data[$str_older] = $num_older;
	if ($config['history_db_enabled'] == 1 && !empty($num_history)) {
		// In this pie chart only 5 elements are shown, so we need to remove
		// an element. With a history db enabled the >3 months element are dispensable
		if (count($data) >= 5 && isset($data[$str_3months]))
			unset($data[$str_3months]);

		$time_historic_db = time() - ((int)$config['history_db_days'] * SECONDS_1DAY);
		$date_human = human_time_comparation($time_historic_db);
		$str_history = "> $date_human (".__("History db").")";
		$data[$str_history] = $num_history;
	}

	$water_mark = array(
			'file' => $config['homedir'] . "/images/logo_vertical_water.png", 
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false)
		);
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark, $config['fontpath'], $config['font_size']);
}

/**
 * Print a horizontal bar graph with packets data of agents
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_paquetes($width = 380, $height = 300) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	$legend = array ();
	
	$agents = agents_get_group_agents (array_keys (users_get_groups (false, 'RR')), false, "none");
	$count = agents_get_modules_data_count (array_keys ($agents));
	unset ($count["total"]);
	arsort ($count, SORT_NUMERIC);
	$count = array_slice ($count, 0, 8, true);
	
	foreach ($count as $agent_id => $value) {
		$data[$agents[$agent_id]]['g'] = $value;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return hbar_graph($config['flash_charts'], $data, $width, $height, array(),
		$legend, "", "", true, "", $water_mark,
		$config['fontpath'], $config['font_size'], false, 1, $config['homeurl'],
					'white',
					'black');
}

/**
 * Print a horizontal bar graph with modules data of agents
 * 
 * @param integer height graph height
 * @param integer width graph width
 */
function graph_db_agentes_modulos($width, $height) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$modules = db_get_all_rows_sql ('
				SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE delete_pending = 0
				GROUP BY id_agente
				ORDER BY 1 DESC LIMIT 10');
			break;
		case "oracle":
			$modules = db_get_all_rows_sql ('
				SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE rownum <= 10
				AND delete_pending = 0
				GROUP BY id_agente
				ORDER BY 1 DESC');
			break;
	}
	if ($modules === false)
		$modules = array ();
	
	$data = array();
	foreach ($modules as $module) {
		$agent_name = agents_get_name ($module['id_agente'], "none");
		
		if (empty($agent_name)) {
			continue;
		}
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$data[$agent_name]['g'] = $module['COUNT(id_agente_modulo)'];
				break;
			case "oracle":
				$data[$agent_name]['g'] = $module['count(id_agente_modulo)'];
				break;
		}
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return hbar_graph($config['flash_charts'],
		$data, $width, $height, array(),
		array(), "", "", true, "",
		$water_mark,
		$config['fontpath'], $config['font_size'], false, 1, $config['homeurl'],
					'white',
					'black');
}

/**
 * Print a pie graph with users activity in a period of time
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_user_activity ($width = 350, $height = 230) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_usuario) n_incidents, id_usuario
				FROM tsesion
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_usuario) n_incidents, id_usuario
				FROM tsesion 
				WHERE rownum <= %d
				GROUP BY id_usuario
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$logins = db_get_all_rows_sql ($sql);
	
	if ($logins == false) {
		$logins = array();
	}
	foreach ($logins as $login) {
		$data[$login['id_usuario']] = $login['n_incidents'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with priodity incident
 */
function grafico_incidente_prioridad () {
	global $config;
	global $graphic_type;
	
	$data_tmp = array (0, 0, 0, 0, 0, 0);
	$sql = 'SELECT COUNT(id_incidencia) n_incidents, prioridad
		FROM tincidencia
		GROUP BY prioridad
		ORDER BY 2 DESC';
	$incidents = db_get_all_rows_sql ($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		if ($incident['prioridad'] < 5)
			$data_tmp[$incident['prioridad']] = $incident['n_incidents'];
		else
			$data_tmp[5] += $incident['n_incidents'];
	}
	$data = array (__('Informative') => $data_tmp[0],
		__('Low') => $data_tmp[1],
		__('Medium') => $data_tmp[2],
		__('Serious') => $data_tmp[3],
		__('Very serious') => $data_tmp[4],
		__('Maintenance') => $data_tmp[5]);
	
		if($config["fixed_graph"] == false){
			$water_mark = array('file' =>
				$config['homedir'] . "/images/logo_vertical_water.png",
				'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
		}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with incidents data
 */
function graph_incidents_status () {
	global $config;
	global $graphic_type;
	$data = array (0, 0, 0, 0);
	
	$data = array ();
	$data[__('Open incident')] = 0;
	$data[__('Closed incident')] = 0;
	$data[__('Outdated')] = 0;
	$data[__('Invalid')] = 0;
	
	$incidents = db_get_all_rows_filter ('tincidencia',
		array ('estado' => array (0, 2, 3, 13)),
		array ('estado'));
	if ($incidents === false)
		$incidents = array ();
	foreach ($incidents as $incident) {
		if ($incident["estado"] == 0)
			$data[__("Open incident")]++;
		if ($incident["estado"] == 2)
			$data[__("Closed incident")]++;
		if ($incident["estado"] == 3)
			$data[__("Outdated")]++;
		if ($incident["estado"] == 13)
			$data[__("Invalid")]++;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with incident data by group
 */
function graphic_incident_group () {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config["dbtype"]) {
		case 'mysql':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
				FROM tincidencia,tgrupo
				WHERE tgrupo.id_grupo = tincidencia.id_grupo
				GROUP BY tgrupo.id_grupo, nombre ORDER BY 1 DESC LIMIT %d',
				$max_items);
			break;
		case 'oracle':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
				FROM tincidencia,tgrupo
				WHERE tgrupo.id_grupo = tincidencia.id_grupo
				AND rownum <= %d
				GROUP BY tgrupo.id_grupo, nombre ORDER BY 1 DESC',
				$max_items);
			break;
	}
	$incidents = db_get_all_rows_sql ($sql);
	
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents
		FROM tincidencia
		WHERE tincidencia.id_grupo = 0');
	
	$incidents_all = db_get_value_sql($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		$data[$incident['nombre']] = $incident['n_incidents'];
	}
	
	if ($incidents_all > 0) {
		$data[__('All')] = $incidents_all;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a graph with access data of agents
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_incident_user () {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config["dbtype"]) {
		case 'mysql':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
				FROM tincidencia
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case 'oracle':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
				FROM tincidencia
				WHERE rownum <= %d
				GROUP BY id_usuario
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$incidents = db_get_all_rows_sql ($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		if ($incident['id_usuario'] == false) {
			$name = __('System');
		}
		else {
			$name = $incident['id_usuario'];
		}
		
		$data[$name] = $incident['n_incidents'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with access data of incidents source
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function graphic_incident_source($width = 320, $height = 200) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				GROUP BY `origen`
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				GROUP BY "origen"
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				WHERE rownum <= %d
				GROUP BY origen
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$origins = db_get_all_rows_sql ($sql);
	
	if ($origins == false) {
		$origins = array();
	}
	foreach ($origins as $origin) {
		$data[$origin['origen']] = $origin['n_incident'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

function graph_events_validated($width = 300, $height = 200, $extra_filters = array(), $meta = false, $history = false) {
	global $config;
	global $graphic_type;
	
	$event_type = false;
	if (array_key_exists('event_type', $extra_filters))
		$event_type = $extra_filters['event_type'];
	
	$event_severity = false;
	if (array_key_exists('event_severity', $extra_filters))
		$event_severity = $extra_filters['event_severity'];
	
	$event_status = false;
	if (array_key_exists('event_status', $extra_filters))
		$event_status = $extra_filters['event_status'];
	
	$event_filter_search = false;
	if (array_key_exists('event_filter_search', $extra_filters))
		$event_filter_search = $extra_filters['event_filter_search'];
	
	$data_graph = events_get_count_events_validated(
		array('id_group' => array_keys(users_get_groups())), null, null, 
		$event_severity, $event_type, $event_status, $event_filter_search);
	
	$colors = array();
	foreach ($data_graph as $k => $v) {
		if ($k == __('Validated')) {
			$colors[$k] = COL_NORMAL;
		}
		else {
			$colors[$k] = COL_CRITICAL;
		}
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	echo pie3d_graph(
		true, $data_graph, $width, $height, __("other"), "",
		$water_mark,
		$config['fontpath'], $config['font_size'], 1, false, $colors);
}

/**
 * Print a pie graph with events data of group
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param string url
 * @param bool if the graph required is or not for metaconsole
 * @param bool if the graph required is or not for history table
 */
function grafico_eventos_grupo ($width = 300, $height = 200, $url = "", $meta = false, $history = false, $noWaterMark = true) {
	global $config;
	global $graphic_type;
	
	//It was urlencoded, so we urldecode it
	$url = html_entity_decode (rawurldecode ($url), ENT_QUOTES);
	$data = array ();
	$loop = 0;
	define ('NUM_PIECES_PIE', 6);
	
	
	//Hotfix for the id_agente_modulo
	$url = str_replace(
		'SELECT id_agente_modulo', 'SELECT_id_agente_modulo', $url);
	
	
	$badstrings = array (";",
		"SELECT ",
		"DELETE ",
		"UPDATE ",
		"INSERT ",
		"EXEC");
	//remove bad strings from the query so queries like ; DELETE FROM  don't pass
	$url = str_ireplace ($badstrings, "", $url);
	
	
	//Hotfix for the id_agente_modulo
	$url = str_replace(
		'SELECT_id_agente_modulo', 'SELECT id_agente_modulo', $url);
	
	
	// Choose the table where search if metaconsole or not
	if ($meta) {
		if ($history) {
			$event_table = 'tmetaconsole_event_history';
		}
		else {
			$event_table = 'tmetaconsole_event';
		}
		$field_extra = ', agent_name';
		$groupby_extra = ', server_id';
	}
	else {
		$event_table = 'tevento';
		$field_extra = '';
		$groupby_extra = '';
	}
	
	// Add tags condition to filter
	$tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');
	
	//This will give the distinct id_agente, give the id_grupo that goes
	//with it and then the number of times it occured. GROUP BY statement
	//is required if both DISTINCT() and COUNT() are in the statement 
	$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente,
					COUNT(id_agente) AS count'.$field_extra.'
				FROM '.$event_table.' te LEFT JOIN tagent_secondary_group tasg
					ON te.id_grupo = tasg.id_group
				WHERE 1=1 %s %s
				GROUP BY id_agente'.$groupby_extra.'
				ORDER BY count DESC LIMIT 8', $url, $tags_condition);
	
	$result = db_get_all_rows_sql ($sql, false, false);
	if ($result === false) {
		$result = array();
	}
	
	$system_events = 0;
	$other_events = 0;
	
	foreach ($result as $row) {
		$row["id_grupo"] = agents_get_agent_group ($row["id_agente"]);
		if (!check_acl ($config["id_user"], $row["id_grupo"], "ER") == 1)
			continue;
		
		if ($loop >= NUM_PIECES_PIE) {
			$other_events += $row["count"];
		}
		else {
			if ($row["id_agente"] == 0) {
				$system_events += $row["count"];
			}
			else {
				if ($meta) {
					$name = mb_substr (io_safe_output($row['agent_name']), 0, 25)." (".$row["count"].")";
				}
				else {
					$alias = agents_get_alias($row["id_agente"]);
					$name = mb_substr($alias, 0, 25)." #".$row["id_agente"]." (".$row["count"].")";
				}
				$data[$name] = $row["count"];
			}
		}
		$loop++;
	}
	
	if ($system_events > 0) {
		$name = __('SYSTEM')." (".$system_events.")";
		$data[$name] = $system_events;
	}
	
	/*
	if ($other_events > 0) {
		$name = __('Other')." (".$other_events.")";
		$data[$name] = $other_events;
	}
	*/
	
	// Sort the data
	arsort($data);
	if ($noWaterMark) {
		$water_mark = array('file' => $config['homedir'] .  "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	else
	{
		$water_mark = array();
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom');
}

function grafico_eventos_agente ($width = 300, $height = 200, $result = false, $meta = false, $history = false) {
	global $config;
	global $graphic_type;
	
	//It was urlencoded, so we urldecode it
	//$url = html_entity_decode (rawurldecode ($url), ENT_QUOTES);
	$data = array ();
	$loop = 0;
	
	if ($result === false) {
		$result = array();
	}
	
	$system_events = 0;
	$other_events = 0;
	$total = array();
	$i = 0;
	
	foreach ($result as $row) {
		if ($meta) {
			$count[] = $row["agent_name"];
		}
		else {
			if ($row["id_agente"] == 0) {
				$count[] = __('SYSTEM');
			}
			else
				$count[] = agents_get_alias($row["id_agente"]) ;
		}
		
	}
	
	$total = array_count_values($count);
	
	foreach ($total as $key => $total) {
		if ($meta) {
			$name = $key." (".$total.")";
		}
		else {
			$name = $key." (".$total.")";
		}
		$data[$name] = $total;
	}
	
	/*
	if ($other_events > 0) {
		$name = __('Other')." (".$other_events.")";
		$data[$name] = $other_events;
	}
	*/
	
	// Sort the data
	arsort($data);
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Others'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom');
}

/**
 * Print a pie graph with events data in 320x200 size
 * 
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total($filter = "", $width = 320, $height = 200, $noWaterMark = true) {
	global $config;
	global $graphic_type;
	
	$filter = str_replace  ( "\\" , "", $filter);
	
	// Add tags condition to filter
	$tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');
	$filter .= $tags_condition;
	
	$data = array ();
	$legend = array ();
	$total = 0;
	
	$where = '';
	if (!users_is_admin()) {
		$where = 'WHERE event_type NOT IN (\'recon_host_detected\', \'system\',\'error\', \'new_agent\', \'configuration_change\')';
	}
	
	$sql = sprintf("SELECT criticity, COUNT(id_evento) events
		FROM tevento %s 
		GROUP BY criticity ORDER BY events DESC", $where);
	
	$criticities = db_get_all_rows_sql ($sql, false, false);
	
	if (empty($criticities)) {
		$criticities = array();
		$colors = array();
	}
	
	foreach ($criticities as $cr) {
		switch ($cr['criticity']) {
			case EVENT_CRIT_MAINTENANCE:
				$data[__('Maintenance')] = $cr['events'];
				$colors[__('Maintenance')] = COL_MAINTENANCE;
				break;
			case EVENT_CRIT_INFORMATIONAL:
				$data[__('Informational')] = $cr['events'];
				$colors[__('Informational')] = COL_INFORMATIONAL;
				break;
			case EVENT_CRIT_NORMAL:
				$data[__('Normal')] = $cr['events'];
				$colors[__('Normal')] = COL_NORMAL;
				break;
			case EVENT_CRIT_MINOR:
				$data[__('Minor')] = $cr['events'];
				$colors[__('Minor')] = COL_MINOR;
				break;
			case EVENT_CRIT_WARNING:
				$data[__('Warning')] = $cr['events'];
				$colors[__('Warning')] = COL_WARNING;
				break;
			case EVENT_CRIT_MAJOR:
				$data[__('Major')] = $cr['events'];
				$colors[__('Major')] = COL_MAJOR;
				break;
			case EVENT_CRIT_CRITICAL:
				$data[__('Critical')] = $cr['events'];
				$colors[__('Critical')] = COL_CRITICAL;
				break;
		}
	}
	if ($noWaterMark) {
		$water_mark = array(
			'file' => $config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("/images/logo_vertical_water.png", false, false, false));
	}
	else {
		$water_mark = array();
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom', $colors);
}

/**
 * Print a pie graph with events data of users
 * 
 * @param integer height pie graph height
 * @param integer period time period
 */
function grafico_eventos_usuario ($width, $height) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	
	$where = '';
	if (!users_is_admin()) {
		$where = 'WHERE event_type NOT IN (\'recon_host_detected\', \'system\',\'error\', \'new_agent\', \'configuration_change\')';
	}
	
	$sql = sprintf ('SELECT COUNT(id_evento) events, id_usuario
				FROM tevento %s
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $where, $max_items);
	
	$events = db_get_all_rows_sql ($sql);
	
	if ($events === false) {
		$events = array();
	}
	
	foreach($events as $event) {
		if ($event['id_usuario'] == '0') {
			$data[__('System')] = $event['events'];
		}
		elseif ($event['id_usuario'] == '') {
			$data[__('System')] = $event['events'];
		}
		else {
			$data[$event['id_usuario']] = $event['events'];
		}
	}
	
	$water_mark = array(
		'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
		'url' => ui_get_full_url("/images/logo_vertical_water.png", false, false, false));
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a custom SQL-defined graph 
 * 
 * @param integer ID of report content, used to get SQL code to get information for graph
 * @param integer height graph height
 * @param integer width graph width
 * @param integer Graph type 1 vbar, 2 hbar, 3 pie
 */
function graph_custom_sql_graph ($id, $width, $height,
	$type = 'sql_graph_vbar', $only_image = false, $homeurl = '',
	$ttl = 1, $max_num_elements = 8) {
	
	global $config;
	$SQL_GRAPH_MAX_LABEL_SIZE = 20;
	
	$report_content = db_get_row ('treport_content', 'id_rc', $id);
	if($id != null){
		$historical_db = db_get_value_sql("SELECT historical_db from treport_content where id_rc =".$id);
	}
	else{
		$historical_db = $content['historical_db'];
	}
	if ($report_content["external_source"] != "") {
		$sql = io_safe_output ($report_content["external_source"]);
	}
	else {
		$sql = db_get_row('treport_custom_sql', 'id', $report_content["treport_custom_sql_id"]);
		$sql = io_safe_output($sql['sql']);
	}
	
	if (($config['metaconsole'] == 1) && defined('METACONSOLE')) {
		$metaconsole_connection = enterprise_hook('metaconsole_get_connection', array($report_content['server_name']));
		
		if ($metaconsole_connection === false) {
			return false;
		}
		
		if (enterprise_hook('metaconsole_load_external_db', array($metaconsole_connection)) != NOERR) {
			//ui_print_error_message ("Error connecting to ".$server_name);
			return false;
		}
	}
	
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			break;
		case "oracle":
			$sql = str_replace(";", "", $sql);
			break;
	}
	
	$data_result = db_get_all_rows_sql ($sql,$historical_db);
	
	
	
	if (($config['metaconsole'] == 1) && defined('METACONSOLE'))
		enterprise_hook('metaconsole_restore_db');
	
	if ($data_result === false)
		$data_result = array ();
	
	$data = array ();
	
	$count = 0;
	foreach ($data_result as $data_item) {
		$count++;
		$value = 0;
		if (!empty($data_item["value"])) {
			$value = $data_item["value"];
		}
		if ($count <= $max_num_elements) {
			$label = __('Data');
			if (!empty($data_item["label"])) {
				$label = io_safe_output($data_item["label"]);
				if (strlen($label) > $SQL_GRAPH_MAX_LABEL_SIZE) {
					$first_label = $label;
					$label = substr($first_label, 0, floor($SQL_GRAPH_MAX_LABEL_SIZE/2));
					$label .= '...';
					$label .= substr($first_label, floor(-$SQL_GRAPH_MAX_LABEL_SIZE/2));
				}
			}
			switch ($type) {
				case 'sql_graph_vbar': // vertical bar
				case 'sql_graph_hbar': // horizontal bar
					$data[$label."_".$count]['g'] = $value;
					break;
				case 'sql_graph_pie': // Pie
					$data[$label."_".$count] = $value;
					break;
			}
		} else {
			switch ($type) {
				case 'sql_graph_vbar': // vertical bar
				case 'sql_graph_hbar': // horizontal bar
					if (!isset($data[__('Other')]['g'])) $data[__('Other')]['g'] = 0;
					$data[__('Other')]['g'] += $value;
					break;
				case 'sql_graph_pie': // Pie
					if (!isset($data[__('Other')])) $data[__('Other')] = 0;
					$data[__('Other')] += $value;
					break;
			}
		}
	}
	
	$flash_charts = $config['flash_charts'];
		
	if ($only_image) {
		$flash_charts = false;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	switch ($type) {
		case 'sql_graph_vbar': // vertical bar
			return vbar_graph(
				$flash_charts,
				$data,
				$width,
				$height,
				array(),
				array(),
				"",
				"",
				"",
				"",
				$water_mark,
				$config['fontpath'],
				$config['font_size'],
				"",
				$ttl,
				$homeurl,
				"white",
				false,
				false,
				"black"
			);
			break;
		case 'sql_graph_hbar': // horizontal bar
			return hbar_graph(
				$flash_charts,
				$data,
				$width,
				$height,
				array(),
				array(),
				"",
				"",
				"",
				"",
				$water_mark,
				$config['fontpath'],
				$config['font_size'],
				false,
				$ttl,
				$homeurl,
				'white',
				'black'
			);
			break;
		case 'sql_graph_pie': // Pie
			return pie3d_graph($flash_charts, $data, $width, $height, __("other"), $homeurl,
				$water_mark, $config['fontpath'], '', $ttl);
			break;
	}
}

/**
 * Print a static graph with event data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param string homeurl
 * @param bool return or echo the result
 */
function graph_graphic_agentevents ($id_agent, $width, $height, $period = 0, $homeurl, $return = false, $from_agent_view = false) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	
	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
	
	$interval = (int) ($period / $resolution);
	$date = get_system_time ();
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	$legend = array();
	$full_legend = array();
	$full_legend_date = array();
	
	$cont = 0;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			if ($config['flash_charts']) {
				$name = date('H:i', $bottom);
			}
			else {
				$name = date('H\h', $bottom);
			}
		}
		else {
			$name = $bottom;
		}
		
		// Show less values in legend
		if ($cont == 0 or $cont % 2)
			$legend[$cont] = $name;
		
		if ($from_agent_view) {
			$full_date = date('Y/m/d', $bottom);
			$full_legend_date[$cont] = $full_date;
		}

		$full_legend[$cont] = $name;
		
		$top = $datelimit + ($periodtime * ($i + 1));
		$event = db_get_row_filter ('tevento',
			array ('id_agente' => $id_agent,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top), 'criticity, utimestamp');
		
		if (!empty($event['utimestamp'])) {
			$data[$cont]['utimestamp'] = $periodtime;
			switch ($event['criticity']) {
				case EVENT_CRIT_WARNING:
					$data[$cont]['data'] = 2;
					break;
				case EVENT_CRIT_CRITICAL:
					$data[$cont]['data'] = 3;
					break;
				default:
					$data[$cont]['data'] = 1;
					break;
			}
		}
		else {
			$data[$cont]['utimestamp'] = $periodtime;
			$data[$cont]['data'] = 1;
		}
		$cont++;
	}

	$colors = array(1 => COL_NORMAL, 2 => COL_WARNING, 3 => COL_CRITICAL, 4 => COL_UNKNOWN);
	
	// Draw slicebar graph
	if ($config['flash_charts']) {
		$out = flot_slicesbar_graph($data, $period, $width, $height, $full_legend, $colors, $config['fontpath'], $config['round_corner'], $homeurl, '', '', false, $id_agent, $full_legend_date);
	}
	else {
		$out = slicesbar_graph($data, $period, $width, $height, $colors, $config['fontpath'], $config['round_corner'], $homeurl);
		
		// Draw legend
		$out .=  "<br>";
		$out .=  "&nbsp;";
		foreach ($legend as $hour) {
			$out .=  "<span style='font-size: 6pt'>" . $hour . "</span>";
			$out .=  "&nbsp;";
		}
	}
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

/**
 * Print a static graph with event data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param string homeurl
 * @param bool return or echo the result
 */
function graph_graphic_moduleevents ($id_agent, $id_module, $width, $height, $period = 0, $homeurl, $return = false) {
	global $config;
	global $graphic_type;

	$data = array ();

	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
	$interval = (int) ($period / $resolution);
	$date = get_system_time ();
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	$legend = array();
	$full_legend = array();

	$cont = 0;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			if ($config['flash_charts']) {
				$name = date('H:i:s', $bottom);
			}
			else {
				$name = date('H\h', $bottom);
			}
		}
		else {
			$name = $bottom;
		}

		// Show less values in legend
		if ($cont == 0 or $cont % 2)
			$legend[$cont] = $name;

		$full_legend[$cont] = $name;

		$top = $datelimit + ($periodtime * ($i + 1));

		$event_filter = array ('id_agente' => $id_agent,
			'utimestamp > '.$bottom,
			'utimestamp < '.$top);
		if ((int)$id_module !== 0) {
			$event_filter['id_agentmodule'] = $id_module;
		}
		$event = db_get_row_filter ('tevento', $event_filter, 'criticity, utimestamp');

		if (!empty($event['utimestamp'])) {
			$data[$cont]['utimestamp'] = $periodtime;
			switch ($event['criticity']) {
				case EVENT_CRIT_WARNING:
					$data[$cont]['data'] = 2;
					break;
				case EVENT_CRIT_CRITICAL:
					$data[$cont]['data'] = 3;
					break;
				default:
					$data[$cont]['data'] = 1;
					break;
			}
		}
		else {
			$data[$cont]['utimestamp'] = $periodtime;
			$data[$cont]['data'] = 1;
		}
		$cont++;
	}

	$colors = array(1 => COL_NORMAL, 2 => COL_WARNING, 3 => COL_CRITICAL, 4 => COL_UNKNOWN);

	// Draw slicebar graph
	if ($config['flash_charts']) {
		$out = flot_slicesbar_graph($data, $period, $width, $height, $full_legend, $colors, $config['fontpath'], $config['round_corner'], $homeurl, '', '', false, $id_agent);
	}
	else {
		$out = slicesbar_graph($data, $period, $width, $height, $colors, $config['fontpath'], $config['round_corner'], $homeurl);

		// Draw legend
		$out .=  "<br>";
		$out .=  "&nbsp;";
		foreach ($legend as $hour) {
			$out .=  "<span style='font-size: 6pt'>" . $hour . "</span>";
			$out .=  "&nbsp;";
		}
	}

	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

// Prints an error image
function fs_error_image ($width = 300, $height = 110) {
	global $config;
	return graph_nodata_image($width, $height, 'area');
}

function fullscale_data (
	$agent_module_id, $date_array,
	$show_unknown = 0, $show_percentil = 0,
	$series_suffix,
	$compare = false){

	global $config;
	$data_uncompress =
		db_uncompress_module_data(
			$agent_module_id,
			$date_array['start_date'],
			$date_array['final_date']
		);

	$data = array();
	$previous_data = 0;
	$min_value = PHP_INT_MAX-1;
	$max_value = PHP_INT_MIN+1;
	$flag_unknown  = 0;
	$array_percentil = array();
	foreach ($data_uncompress as $k) {
		foreach ($k["data"] as $v) {
			if (isset($v["type"]) && $v["type"] == 1) { # skip unnecesary virtual data
				continue;
			}
			if($compare){ // * 1000 need js utimestam mlsecond
				$real_date = ($v['utimestamp'] + $date_array['period']) * 1000;
			}
			else{
				$real_date = $v['utimestamp'] * 1000;
			}

			if ($v["datos"] === NULL) {
				// Unknown
				if($show_unknown){
					if(!$compare){
						if($flag_unknown){
							$data["unknown" . $series_suffix]['data'][] = array($real_date , 1);
						}
						else{
							$data["unknown" . $series_suffix]['data'][] = array( ($real_date - 1) , 0);
							$data["unknown" . $series_suffix]['data'][] = array($real_date , 1);
							$flag_unknown = 1;
						}
					}
				}

				$data["sum" . $series_suffix]['data'][] = array($real_date , $previous_data);
			}
			else {
				//normal
				$previous_data = $v["datos"];
				$data["sum" . $series_suffix]['data'][] = array($real_date , $v["datos"]);
				if($show_unknown){
					if(!$compare){
						if($flag_unknown){
							$data["unknown" . $series_suffix]['data'][] = array($real_date , 0);
							$flag_unknown = 0;
						}
					}
				}
			}

			if(isset($v["datos"]) && $v["datos"]){
				//max
				if($v['datos'] >= $max_value){
					$max_value = $v['datos'];
				}
				//min
				if($v['datos'] <= $min_value){
					$min_value = $v['datos'];
				}
				//avg sum
				$sum_data += $v["datos"];
			}
			//avg count
			$count_data++;

			if($show_percentil && !$compare){
				$array_percentil[] = $v["datos"];
			}

			$last_data = $v["datos"];
		}
	}

	if($show_percentil && !$compare){
		$percentil_result = get_percentile($show_percentil, $array_percentil);
		if($compare){
			$data["percentil" . $series_suffix]['data'][] = array(
				($date_array['start_date'] + $date_array['period']) * 1000,
				$percentil_result
			);
			$data["percentil" . $series_suffix]['data'][] = array(
				($date_array['final_date'] + $date_array['period']) * 1000,
				$percentil_result
			);
		}
		else{
			$data["percentil" . $series_suffix]['data'][] = array(
				$date_array['start_date'] * 1000,
				$percentil_result
			);
			$data["percentil" . $series_suffix]['data'][] = array(
				$date_array['final_date'] * 1000,
				$percentil_result
			);
		}
	}
	// Add missed last data
	if($compare){
		$data["sum" . $series_suffix]['data'][] = array(
			($date_array['final_date'] + $date_array['period']) * 1000,
			$last_data
		);
	}
	else{
		$data["sum" . $series_suffix]['data'][] = array(
			$date_array['final_date'] * 1000,
			$last_data
		);
	}

	$data["sum" . $series_suffix]['min'] = $min_value;
	$data["sum" . $series_suffix]['max'] = $max_value;
	$data["sum" . $series_suffix]['avg'] = $sum_data/$count_data;

	return $data;
}

/**
 * Print an area graph with netflow aggregated
 */
function graph_netflow_aggregate_area ($data, $period, $width, $height, $unit = '', $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;

	if (empty ($data)) {
		echo fs_error_image ();
		return;
	}

	// Calculate source indexes
	foreach ($data['sources'] as $key => $value) {
		$i = 0;
		foreach($data['data'] as $k => $v){
			$chart['netflow_' . $key]['data'][$i][0] = $k * 1000;
			$chart['netflow_' . $key]['data'][$i][1] = $v[$key];
			$i++;
		}
	}

	if ($config['homeurl'] != '') {
		$homeurl = $config['homeurl'];
	}
	else {
		$homeurl = '';
	}

	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));

			$water_mark = 	$config['homedir'] .  "/images/logo_vertical_water.png";
	}

	if($ttl >= 2){
		$only_image = true;
	}
	else{
		$only_image = false;
	}

	$params =array(
		'agent_module_id'     => false,
		'period'              => $period,
		'width'               =>'90%',
		'height'              => 450,
		'unit'                => $unit,
		'only_image'          => $only_image,
		'homeurl'             => $homeurl,
		'menu'                => true,
		'backgroundColor'     => 'white',
		'type_graph'          => 'area',
		'font'                => $config['fontpath'],
		'font-size'           => $config['font_size'],
		'array_data_create'   => $chart
	);

	return grafico_modulo_sparse($params);
}

/**
 * Print an area graph with netflow total
 */
function graph_netflow_total_area ($data, $period, $width, $height, $unit = '', $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;

	if (empty ($data)) {
		echo fs_error_image ();
		return;
	}

	// Calculate source indexes
	$i=0;
	foreach ($data as $key => $value) {
		$chart['netflow']['data'][$i][0] = $key * 1000;
		$chart['netflow']['data'][$i][1] = $value['data'];
		$i++;
	}

	if ($config['homeurl'] != '') {
		$homeurl = $config['homeurl'];
	}
	else {
		$homeurl = '';
	}

	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));

			$water_mark = 	$config['homedir'] .  "/images/logo_vertical_water.png";
	}

	if($ttl >= 2){
		$only_image = true;
	}
	else{
		$only_image = false;
	}

	$params =array(
		'agent_module_id'     => false,
		'period'              => $period,
		'width'               =>'90%',
		'height'              => 450,
		'unit'                => $unit,
		'only_image'          => $only_image,
		'homeurl'             => $homeurl,
		'menu'                => true,
		'backgroundColor'     => 'white',
		'type_graph'          => 'area',
		'font'                => $config['fontpath'],
		'font-size'           => $config['font_size'],
		'array_data_create'   => $chart
	);

	return grafico_modulo_sparse($params);
}

/**
 * Print a pie graph with netflow aggregated
 */
function graph_netflow_aggregate_pie ($data, $aggregate, $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;

	if (empty ($data)) {
		return fs_error_image ();
	}

	$date_array = array();
	$date_array["period"]     = 300;
	$date_array["final_date"] = time();
	$date_array["start_date"] = time() - 300;

	$i = 0;
	$values = array();
	$agg = '';
	while (isset ($data[$i])) {
		$agg = $data[$i]['agg'];
		if (!isset($values[$agg])) {
			$values[$agg] = $data[$i]['data'];
		}
		else {
			$values[$agg] += $data[$i]['data'];
		}
		$i++;
	}

	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}

	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}

	return pie3d_graph($flash_chart, $values, 370, 200,
		__('Other'), $config['homeurl'], $water_mark,
		$config['fontpath'], $config['font_size'], $ttl);
}

/**
 * Print a circular graph with the data transmitted between IPs
 */
function graph_netflow_circular_mesh ($data, $unit, $radius = 700) {
	global $config;

	if (empty($data) || empty($data['elements']) || empty($data['matrix'])) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_relationship_graph ($data['elements'], $data['matrix'], $unit, $radius, true);
}

/**
 * Print a rectangular graph with the traffic of the ports for each IP
 */
function graph_netflow_host_traffic ($data, $unit, $width = 700, $height = 700) {
	global $config;

	if (empty ($data)) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_tree_map_graph ($data, $width, $height, true);
}

/**
 * Print a graph with event data of module
 * 
 * @param integer id_module Module ID
 * @param integer width graph width
 * @param integer height graph height
 * @param integer period time period
 * @param string homeurl Home url if the complete path is needed
 * @param int Zoom factor over the graph
 * @param string adaptation width and margin left key (could be adapter_[something] or adapted_[something])
 * @param int date limit of the period
 */
function graphic_module_events ($id_module, $width, $height, $period = 0, $homeurl = '', $zoom = 0, $adapt_key = '', $date = false, $stat_win = false) {
	global $config;
	global $graphic_type;

	$data = array ();
$width = 90;
$height = 100;
	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph

	$interval = (int) ($period / $resolution);
	if ($date === false) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();

	// Set the title and time format
	if ($period <= SECONDS_6HOURS) {
		$time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$time_format = 'M d H\h';
	}
	elseif ($period < SECONDS_6MONTHS) {
		$time_format = "M d H\h";
	}
	else {
		$time_format = "Y M d H\h";
	}

	$legend = array();
	$cont = 0;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			$name = date($time_format, $bottom);
			//$name = date('H\h', $bottom);
		}
		else {
			$name = $bottom;
		}

		$top = $datelimit + ($periodtime * ($i + 1));

		$events = db_get_all_rows_filter ('tevento',
			array ('id_agentmodule' => $id_module,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top),
			'event_type, utimestamp');

		if (!empty($events)) {
			$status = 'normal';
			foreach($events as $event) {
				if (empty($event['utimestamp'])) {
					continue;
				}
				switch($event['event_type']) {
					case 'going_down_normal':
					case 'going_up_normal':
						// The default status is normal. Do nothing
						break;
					case 'going_unknown':
						if ($status == 'normal') {
							$status = 'unknown';
						}
						break;
					case 'going_up_warning':
					case 'going_down_warning':
						if ($status == 'normal' || $status == 'unknown') {
							$status = 'warning';
						}
						break;
					case 'going_up_critical':
					case 'going_down_critical':
						$status = 'critical';
						break;
				}
			}
		}

		$data[$cont]['utimestamp'] = $periodtime;

		if (!empty($events)) {
			switch ($status) {
				case 'warning':
					$data[$cont]['data'] = 2;
					break;
				case 'critical':
					$data[$cont]['data'] = 3;
					break;
				case 'unknown':
					$data[$cont]['data'] = 4;
					break;
				default:
					$data[$cont]['data'] = 1;
					break;
			}
		}
		else {
			$data[$cont]['data'] = 1;
		}
		$current_timestamp = $bottom;

		$legend[] = date($time_format, $current_timestamp);
		$cont++;
	}

	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($data);
	if ($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}

	$step = round($ndata/$xdata_display);

	$colors = array(1 => '#38B800', 2 => '#FFFF00', 3 => '#FF0000', 4 => '#C3C3C3');

	// Draw slicebar graph
	if ($config['flash_charts']) {
		echo flot_slicesbar_graph($data, $period, $width, 50, $legend, $colors, $config['fontpath'], $config['round_corner'], $homeurl, '', $adapt_key, $stat_win);
	}
	else {
		echo slicesbar_graph($data, $period, $width, 15, $colors, $config['fontpath'], $config['round_corner'], $homeurl);
	}
}

function graph_nodata_image($width = 300, $height = 110, $type = 'area', $text = '') {
	$image = ui_get_full_url('images/image_problem_area_small.png',
		false, false, false); 
	
	// if ($text == '') {
	// 	$text = __('No data to show');
	// }
	
	$text_div = '<div class="nodata_text">' . $text . '</div>';
	
	$image_div = '<div class="nodata_container" style="width:80%;height:80%;background-size: 80% 80%;background-image: url(\'' . $image . '\');">' .
		$text_div . '</div>';
	
	$div = '<div style="width:' . $width . 'px; height:' . $height . 'px; border: 1px dotted #ddd; background-color: white; margin: 0 auto;">' .
		$image_div . '</div>';
	
	return $div;
}

function get_criticity_pie_colors ($data_graph) {
	$colors = array();
	foreach (array_keys($data_graph) as $crit) {
		switch ($crit) {
			case __('Maintenance'): 
				$colors[$crit] = COL_MAINTENANCE;
				break;
			case __('Informational'): 
				$colors[$crit] = COL_INFORMATIONAL;
				break;
			case __('Normal'): 
				$colors[$crit] = COL_NORMAL;
				break;
			case __('Warning'): 
				$colors[$crit] = COL_WARNING;
				break;
			case __('Critical'): 
				$colors[$crit] = COL_CRITICAL;
				break;
			case __('Minor'): 
				$colors[$crit] = COL_MINOR;
				break;
			case __('Major'): 
				$colors[$crit] = COL_MAJOR;
				break;
		}
	}
	
	return $colors;
}


/**
 * Print a rectangular graph with the snmptraps received
 */
function graph_snmp_traps_treemap ($data, $width = 700, $height = 700) {
	global $config;

	if (empty ($data)) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_tree_map_graph ($data, $width, $height, true);
}

/**
 * Print a solarburst graph with a representation of all the groups, agents, module groups and modules grouped
 */
function graph_monitor_wheel ($width = 550, $height = 600, $filter = false) {
	global $config;

	include_once ($config['homedir'] . "/include/functions_users.php");
	include_once ($config['homedir'] . "/include/functions_groups.php");
	include_once ($config['homedir'] . "/include/functions_agents.php");
	include_once ($config['homedir'] . "/include/functions_modules.php");

	$graph_data = array();

	$filter_module_group = (!empty($filter) && !empty($filter['module_group'])) ? $filter['module_group'] : false;

	if ($filter['group'] != 0) {
		$filter_subgroups = "";
		if (!$filter['dont_show_subgroups']) {
			$filter_subgroups = " || parent = " . $filter['group'];
		}

		$groups = db_get_all_rows_sql ("SELECT * FROM tgrupo where id_grupo = " . $filter['group'] . $filter_subgroups);

		$groups_ax = array();
		foreach ($groups as $g) {
			$groups_ax[$g['id_grupo']] = $g;
		}

		$groups = $groups_ax;
	}
	else {
		$groups = users_get_groups(false, "AR", false, true, (!empty($filter) && isset($filter['group']) ? $filter['group'] : null));
	}

	$data_groups = array();
	if (!empty($groups)) {
		$groups_aux = $groups;
		
		$childrens = array();
		$data_groups = groups_get_tree_good($groups, false, $childrens);

		// When i want only one group
		if (count($data_groups) > 1) {
			foreach ($childrens as $id_c) {
				unset($data_groups[$id_c]);
			}
		}
		$data_groups_keys = array();
		groups_get_tree_keys($data_groups, $data_groups_keys);

		$groups_aux = null;
	}

	if (!empty($data_groups)) {
		$filter = array('id_grupo' => array_keys($data_groups_keys));

		$fields = array('id_agente', 'id_parent', 'id_grupo', 'alias');
		$agents = agents_get_agents($filter, $fields);

		if (!empty($agents)) {
			$agents_id = array();
			$agents_aux = array();
			foreach ($agents as $key => $agent) {
				$agents_aux[$agent['id_agente']] = $agent;
			}
			$agents = $agents_aux;
			$agents_aux = null;
			$fields = array('id_agente_modulo', 'id_agente', 'id_module_group', 'nombre');

			$module_groups = modules_get_modulegroups();
			$module_groups[0] = __('Not assigned');
			$modules = agents_get_modules(array_keys($agents), '*');

			$data_agents = array();
			if (!empty($modules)) {
				foreach ($modules as $key => $module) {
					$module_id = (int) $module['id_agente_modulo'];
					$agent_id = (int) $module['id_agente'];
					$module_group_id = (int) $module['id_module_group'];
					$module_name = io_safe_output($module['nombre']);
					$module_status = modules_get_agentmodule_status($module_id);
					$module_value = modules_get_last_value($module_id);
					
					if ($filter_module_group && $filter_module_group != $module_group_id)
						continue;

					if (!isset($data_agents[$agent_id])) {
						$data_agents[$agent_id] = array();
						$data_agents[$agent_id]['id'] = $agent_id;
						$data_agents[$agent_id]['name'] = io_safe_output($agents[$agent_id]['alias']);
						$data_agents[$agent_id]['group'] = (int) $agents[$agent_id]['id_grupo'];
						$data_agents[$agent_id]['type'] = 'agent';
						$data_agents[$agent_id]['size'] = 30;
						$data_agents[$agent_id]['show_name'] = true;
						$data_agents[$agent_id]['children'] = array();

						$tooltip_content = __('Agent') . ": <b>" . $data_agents[$agent_id]['name'] . "</b>";
						$data_agents[$agent_id]['tooltip_content'] = io_safe_output($tooltip_content);

						$data_agents[$agent_id]['modules_critical'] = 0;
						$data_agents[$agent_id]['modules_warning'] = 0;
						$data_agents[$agent_id]['modules_normal'] = 0;
						$data_agents[$agent_id]['modules_not_init'] = 0;
						$data_agents[$agent_id]['modules_not_normal'] = 0;
						$data_agents[$agent_id]['modules_unknown'] = 0;

						$data_agents[$agent_id]['color'] = COL_UNKNOWN;

						unset($agents[$agent_id]);
					}
					if (!isset($data_agents[$agent_id]['children'][$module_group_id])) {
						$data_agents[$agent_id]['children'][$module_group_id] = array();
						$data_agents[$agent_id]['children'][$module_group_id]['id'] = $module_group_id;
						$data_agents[$agent_id]['children'][$module_group_id]['name'] = io_safe_output($module_groups[$module_group_id]);
						$data_agents[$agent_id]['children'][$module_group_id]['type'] = 'module_group';
						$data_agents[$agent_id]['children'][$module_group_id]['size'] = 10;
						$data_agents[$agent_id]['children'][$module_group_id]['children'] = array();

						$tooltip_content = __('Module group') . ": <b>" . $data_agents[$agent_id]['children'][$module_group_id]['name'] . "</b>";
						$data_agents[$agent_id]['children'][$module_group_id]['tooltip_content'] = $tooltip_content;

						$data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_not_init'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] = 0;

						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
					}
					
					switch ($module_status) {
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
						case AGENT_MODULE_STATUS_CRITICAL_ALERT:
							$data_agents[$agent_id]['modules_critical']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_critical']++;
							break;
						
						case AGENT_MODULE_STATUS_WARNING:
						case AGENT_MODULE_STATUS_WARNING_ALERT:
							$data_agents[$agent_id]['modules_warning']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_warning']++;
							break;

						case AGENT_MODULE_STATUS_NORMAL:
						case AGENT_MODULE_STATUS_NORMAL_ALERT:
							$data_agents[$agent_id]['modules_normal']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_normal']++;
							break;

						case AGENT_MODULE_STATUS_NOT_INIT:
							$data_agents[$agent_id]['modules_not_init']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_not_init']++;
							break;

						case AGENT_MODULE_STATUS_NOT_NORMAL:
							$data_agents[$agent_id]['modules_not_normal']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal']++;
							break;

						case AGENT_MODULE_STATUS_NO_DATA:
						case AGENT_MODULE_STATUS_UNKNOWN:
							$data_agents[$agent_id]['modules_unknown']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_unknown']++;
							break;
					}

					if ($data_agents[$agent_id]['modules_critical'] > 0) {
						$data_agents[$agent_id]['color'] = COL_CRITICAL;
					}
					else if ($data_agents[$agent_id]['modules_warning'] > 0) {
						$data_agents[$agent_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['modules_not_normal'] > 0) {
						$data_agents[$agent_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['modules_unknown'] > 0) {
						$data_agents[$agent_id]['color'] = COL_UNKNOWN;
					}
					else if ($data_agents[$agent_id]['modules_normal'] > 0) {
						$data_agents[$agent_id]['color'] = COL_NORMAL;
					}
					else {
						$data_agents[$agent_id]['color'] = COL_NOTINIT;
					}

					if ($data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_CRITICAL;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NORMAL;
					}
					else {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NOTINIT;
					}
					
					$data_module = array();
					$data_module['id'] = $module_id;
					$data_module['name'] = $module_name;
					$data_module['type'] = 'module';
					$data_module['size'] = 10;
					$data_module['link'] = ui_get_full_url("index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$agent_id");

					$tooltip_content = __('Module') . ": <b>" . $module_name . "</b>";
					if (isset($module_value) && $module_value !== false) {
						$tooltip_content .= "<br>";
						$tooltip_content .= __('Value') . ": <b>" . io_safe_output($module_value) . "</b>";
					}
					$data_module['tooltip_content'] = $tooltip_content;

					switch ($module_status) {
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
						case AGENT_MODULE_STATUS_CRITICAL_ALERT:
							$data_module['color'] = COL_CRITICAL;
							break;
						
						case AGENT_MODULE_STATUS_WARNING:
						case AGENT_MODULE_STATUS_WARNING_ALERT:
							$data_module['color'] = COL_WARNING;
							break;

						case AGENT_MODULE_STATUS_NORMAL:
						case AGENT_MODULE_STATUS_NORMAL_ALERT:
							$data_module['color'] = COL_NORMAL;
							break;

						case AGENT_MODULE_STATUS_NOT_INIT:
							$data_module['color'] = COL_NOTINIT;
							break;

						case AGENT_MODULE_STATUS_NOT_NORMAL:
							$data_module['color'] = COL_WARNING;
							break;

						case AGENT_MODULE_STATUS_NO_DATA:
						case AGENT_MODULE_STATUS_UNKNOWN:
						default:
							$data_module['color'] = COL_UNKNOWN;
							break;
					}

					$data_agents[$agent_id]['children'][$module_group_id]['children'][] = $data_module;
					unset($modules[$module_id]);
				}
				function order_module_group_keys ($value, $key) {
					$value['children'] = array_merge($value['children']);
					return $value;
				}
				$data_agents = array_map('order_module_group_keys', $data_agents);
			}
			foreach ($agents as $id => $agent) {
				if (!isset($data_agents[$id])) {
					$data_agents[$id] = array();
					$data_agents[$id]['id'] = (int) $id;
					$data_agents[$id]['name'] = io_safe_output($agent['alias']);
					$data_agents[$id]['type'] = 'agent';
					$data_agents[$id]['color'] = COL_NOTINIT;
					$data_agents[$id]['show_name'] = true;
				}
			}
			$agents = null;
		}
	}

	function iterate_group_array ($groups, &$data_agents) {
		$data = array();

		foreach ($groups as $id => $group) {

			$group_aux = array();
			$group_aux['id'] = (int) $id;
			$group_aux['name'] = io_safe_output($group['nombre']);
			$group_aux['show_name'] = true;
			$group_aux['parent'] = (int) $group['parent'];
			$group_aux['type'] = 'group';
			$group_aux['size'] = 100;
			$group_aux['status'] = groups_get_status($id);

			switch ($group_aux['status']) {
				case AGENT_STATUS_CRITICAL:
					$group_aux['color'] = COL_CRITICAL;
					break;
				
				case AGENT_STATUS_WARNING:
				case AGENT_STATUS_ALERT_FIRED:
					$group_aux['color'] = COL_WARNING;
					break;

				case AGENT_STATUS_NORMAL:
					$group_aux['color'] = COL_NORMAL;
					break;

				case AGENT_STATUS_UNKNOWN:
				default:
					$group_aux['color'] = COL_UNKNOWN;
					break;
			}

			$tooltip_content = html_print_image("images/groups_small/" . $group['icon'] . ".png", true) . "&nbsp;" . __('Group') . ": <b>" . $group_aux['name'] . "</b>";
			$group_aux['tooltip_content'] = $tooltip_content;

			$group_aux['children'] = array();
			
			if (!empty($group['children']))
				$group_aux['children'] = iterate_group_array($group['children'], $data_agents);

			$agents = extract_agents_with_group_id($data_agents, (int) $id);

			if (!empty($agents))
				$group_aux['children'] = array_merge($group_aux['children'], $agents);

			$data[] = $group_aux;
		}

		return $data;
	}

	function extract_agents_with_group_id (&$agents, $group_id) {
		$valid_agents = array();
		foreach ($agents as $id => $agent) {
			if (isset($agent['group']) && $agent['group'] == $group_id) {
				$valid_agents[$id] = $agent;
				unset($agents[$id]);
			}
		}
		
		if (!empty($valid_agents))
			return $valid_agents;
		else
			return false;
	}

	$graph_data = array('name' => __('Main node'), 'type' => 'center_node', 'children' => iterate_group_array($data_groups, $data_agents), 'color' => '#3F3F3F');

	if (empty($graph_data['children']))
		return fs_error_image();

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_sunburst_graph ($graph_data, $width, $height, true);
}

?>
