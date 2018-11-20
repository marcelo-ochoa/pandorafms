<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage DataBase
 */

include_once($config['homedir'] . "/include/functions_extensions.php");
include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . "/include/functions_users.php");
include_once($config['homedir'] . "/include/functions_ui.php");

function db_select_engine() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			require_once ($config['homedir'] . '/include/db/mysql.php');
			break;
		case "postgresql":
			require_once ($config['homedir'] . '/include/db/postgresql.php');
			break;
		case "oracle":
			require_once ($config['homedir'] . '/include/db/oracle.php');
			break;
	}
}

function db_connect($host = null, $db = null, $user = null, $pass = null, $port = null, $critical = true, $charset = null) {
	global $config;
	static $error = 0;
	
	switch ($config["dbtype"]) {
		case "mysql": 
			$return = mysql_connect_db($host, $db, $user, $pass, $port, $charset);
			break;
		case "postgresql":
			$return = postgresql_connect_db($host, $db, $user, $pass, $port);
			break;
		case "oracle":
			$return = oracle_connect_db($host, $db, $user, $pass, $port);
			break;
		default:
			$return = false;
	}
	
	// Something went wrong
	if ($return === false) {
		if ($critical) {
			$url = explode('/', $_SERVER['REQUEST_URI']);
			$flag_url =0;
			foreach ($url as $key => $value) {
				if (strpos($value, 'index.php') !== false || $flag_url) {
					$flag_url=1;
					unset($url[$key]);
				}
				else if(strpos($value, 'enterprise') !== false || $flag_url){
					$flag_url=1;
					unset($url[$key]);
				}
			}
			$config["homeurl"] = rtrim(join("/", $url),"/");
			$config["homeurl_static"] = $config["homeurl"];
			$ownDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;	
			$config['homedir'] = $ownDir;
			$login_screen = 'error_authconfig';
			require($config['homedir'] . '../general/error_screen.php');
			exit;
		}
		else if ($error == 0) {
			// Display the error once even if multiple connection attempts are made
			$error = 1;
			ui_print_error_message (__("Error connecting to database %s at %s.", $db, $host));
		}
	}
	
	return $return;
}

/**
 * When you delete (with the function "db_process_sql_delete" or other) any row in
 * any table, some times the cache save the data just deleted, because you
 * must use "db_clean_cache".
 */

/**
 *
 * Escape string to set it properly to use in sql queries
 *
 * @param string String to be cleaned.
 *
 * @return string String cleaned.
 */
function db_escape_string_sql($string) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_escape_string_sql($string);
			break;
		case "postgresql":
			return postgresql_escape_string_sql($string);
			break;
		case "oracle":
			return oracle_escape_string_sql($string);
			break;
	}
}

function db_encapsule_fields_with_same_name_to_instructions($field) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_encapsule_fields_with_same_name_to_instructions($field);
			break;
		case "postgresql":
			return postgresql_encapsule_fields_with_same_name_to_instructions($field);
			break;
		case "oracle":
			return oracle_encapsule_fields_with_same_name_to_instructions($field);
			break;
	}
}

// Alias for 'db_encapsule_fields_with_same_name_to_instructions'
function db_escape_key_identifier($field) {
	return db_encapsule_fields_with_same_name_to_instructions($field);
}

/**
 * Adds an audit log entry (new function in 3.0)
 *
 * @param string $accion Action description
 * @param string $descripcion Long action description
 * @param string $id User id, by default is the user that login.
 * @param string $ip The ip to make the action, by default is $_SERVER['REMOTE_ADDR'] or $config["remote_addr"]
 * @param string $info The extended info for enterprise audit, by default is empty string.
 *
 * @return int Return the id of row in tsesion or false in case of fail.
 */
function db_pandora_audit($accion, $descripcion, $user_id = false, $ip = true, $info = '') {
	global $config;
	
	// Ignore $ip and always set the ip address
	if (isset($config["remote_addr"])) {
		$ip = $config["remote_addr"];
	}
	else {
		if ($_SERVER['REMOTE_ADDR']) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ip = __('N/A');
		}
	}
	
	if ($user_id !== false) {
		$id = $user_id;
	}
	else {
		if (isset($config["id_user"])) {
			$id = $config["id_user"];
		}
		else $id = 0;
	}
	
	$accion = io_safe_input($accion);
	$descripcion = io_safe_input($descripcion);
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$values = array('id_usuario' => $id,
				'accion' => $accion,
				'ip_origen' => $ip,
				'descripcion' => $descripcion,
				'fecha' => date('Y-m-d H:i:s'),
				'utimestamp' => time());
			break;
		case "oracle":
			$values = array('id_usuario' => $id,
				'accion' => $accion,
				'ip_origen' => $ip,
				'descripcion' => $descripcion,
				'fecha' => '#to_date(\'' . date('Y-m-d H:i:s') .
					'\',\'YYYY-MM-DD HH24:MI:SS\')',
				'utimestamp' => time());
			break;
	}
	$id_audit = db_process_sql_insert('tsesion', $values);

	$valor = "".$values['fecha']." - ".io_safe_output($id)." - ".io_safe_output($accion)." - ".$ip. " - ".io_safe_output($descripcion)."\n";

	if(empty($config["auditdir"])){
		file_put_contents($config["homedir"]."/audit.log", $valor, FILE_APPEND);
	}else{
		file_put_contents($config["auditdir"]."/audit.log", $valor, FILE_APPEND);
	}

	enterprise_include_once('include/functions_audit.php');
	enterprise_hook('audit_pandora_enterprise', array($id_audit, $info));
	
	return $id_audit;
}



/**
 * Log in a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function db_logon ($id_user, $ip) {
	db_pandora_audit("Logon", "Logged in", $id_user, $ip);
	
	// Update last registry of user to set last logon. How do we audit when the user was created then?
	process_user_contact ($id_user);
}

/**
 * Log out a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function db_logoff ($id_user, $ip) {
	db_pandora_audit("Logoff", "Logged out", $id_user, $ip);
}

$sql_cache = array ('saved' => array());

/**
 * Get the first value of the first row of a table in the database.
 *
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param string Field to filter elements
 * @param string Condition the field must have
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function db_get_value($field, $table, $field_search = 1, $condition = 1, $search_history_db = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
		case "postgresql":
			return postgresql_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
		case "oracle":
			return oracle_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
	}
}

/**
 * Get the first value of the first row of a table in the database from an
 * array with filter conditions.
 *
 * Example:
 * <code>
 * db_get_value_filter ('name', 'talert_templates',
 * array ('value' => 2, 'type' => 'equal'));
 * // Equivalent to:
 * // SELECT name FROM talert_templates WHERE value = 2 AND type = 'equal' LIMIT 1
 *
 * db_get_value_filter ('description', 'talert_templates',
 * array ('name' => 'My alert', 'type' => 'regex'), 'OR');
 * // Equivalent to:
 * // SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' LIMIT 1
 * </code>
 *
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param array Conditions to filter the element. See db_format_array_where_clause_sql()
 * for the format
 * @param string Join operator for the elements in the filter.
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function db_get_value_filter ($field, $table, $filter, $where_join = 'AND', $search_history_db = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);
			break;
		case "postgresql":
			return postgresql_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);
			break;
		case "oracle":
			return oracle_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);
			break;
	}
}

/**
 * Get the first value of the first row of a table result from query.
 *
 * @param string SQL select statement to execute.
 *
 * @return the first value of the first row of a table result from query.
 *
 */
function db_get_value_sql($sql, $dbconnection = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value_sql($sql, $dbconnection);
			break;
		case "postgresql":
			return postgresql_db_get_value_sql($sql, $dbconnection);
			break;
		case "oracle":
			return oracle_db_get_value_sql($sql, $dbconnection);
			break;
	}
}

/**
 * Get the first row of an SQL database query.
 *
 * @param string SQL select statement to execute.
 *
 * @return mixed The first row of the result or false
 */
function db_get_row_sql($sql, $search_history_db = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row_sql($sql, $search_history_db);
			break;
		case "postgresql":
			return postgresql_db_get_row_sql($sql, $search_history_db);
			break;
		case "oracle":
			return oracle_db_get_row_sql($sql, $search_history_db);
			break;
			
	}
}

/**
 * Get the first row of a database query into a table.
 *
 * The SQL statement executed would be something like:
 * "SELECT (*||$fields) FROM $table WHERE $field_search = $condition"
 *
 * @param string Table to get the row
 * @param string Field to filter elements
 * @param string Condition the field must have.
 * @param mixed Fields to select (array or string or false/empty for *)
 *
 * @return mixed The first row of a database query or false.
 */
function db_get_row ($table, $field_search, $condition, $fields = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row($table, $field_search, $condition, $fields);
			break;
		case "postgresql":
			return postgresql_db_get_row($table, $field_search, $condition, $fields);
			break;
		case "oracle":
			return oracle_db_get_row($table, $field_search, $condition, $fields);
			break;
	}
}

/**
 * Get the row of a table in the database using a complex filter.
 *
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 <code>
 Both are similars:
 db_get_row_filter ('table', array ('disabled', 0));
 db_get_row_filter ('table', 'disabled = 0');

 Both are similars:
 db_get_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name, description', 'OR');
 db_get_row_filter ('table', 'disabled = 0 OR history_data = 0', 'name, description');
 db_get_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), array ('name', 'description'), 'OR');
 </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition to join the filters (AND, OR).
 *
 * @return mixed Array of the row or false in case of error.
 */
function db_get_row_filter($table, $filter, $fields = false, $where_join = 'AND', $historydb = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row_filter($table, $filter, $fields, $where_join, $historydb);
			break;
		case "postgresql":
			return postgresql_db_get_row_filter($table, $filter, $fields, $where_join);
			break;
		case "oracle":
			return oracle_db_get_row_filter($table, $filter, $fields, $where_join);
			break;
	}
}

/**
 * Get a single field in the databse from a SQL query.
 *
 * @param string SQL statement to execute
 * @param mixed Field number or row to get, beggining by 0. Default: 0
 *
 * @return mixed The selected field of the first row in a select statement.
 */

function db_get_sql ($sql, $field = 0, $search_history_db = false) {
	$result = db_get_all_rows_sql ($sql, $search_history_db);

	if ($result === false)
		return false;

	$ax = 0;
	foreach ($result[0] as $f) {
		if ($field == $ax)
		return $f;
		$ax++;
	}
}

/**
 * Get all the result rows using an SQL statement.
 *
 * @param string SQL statement to execute.
 * @param bool If want to search in history database also
 * @param bool If want to use cache (true by default)
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function db_get_all_rows_sql($sql, $search_history_db = false, $cache = true, $dbconnection = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);
			break;
		case "oracle":
			return oracle_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);
			break;
	}
}

/**
 * Returns the time the module is in unknown status (by events)
 * @param int  $id_agente_modulo  module to check
 * @param int  $tstart            begin of search
 * @param int  $tend              end of search
 */
function db_get_module_ranges_unknown($id_agente_modulo, $tstart = false, $tend = false, $historydb = false, $fix_to_range = 0) {
	global $config;

	if (!isset($id_agente_modulo)) {
		return false;
	}

	if ((!isset($tstart)) || ($tstart === false)) {
		// Return data from the begining
		$tstart = 0;
	}

	if ((!isset($tend)) || ($tend === false)) {
		// Return data until now
		$tend = time();
	}

	if ($tstart > $tend) {
		return false;
	}

	// Retrieve going unknown events in range
	$query  = "SELECT * FROM tevento WHERE id_agentmodule = " . $id_agente_modulo
			. " AND event_type like 'going_%' "
			. " AND utimestamp >= $tstart AND utimestamp <= $tend "
			. " ORDER BY utimestamp ASC";
	$events = db_get_all_rows_sql($query, $historydb);

	$query  = "SELECT * FROM tevento WHERE id_agentmodule = " . $id_agente_modulo
			. " AND event_type like 'going_%' "
			. " AND utimestamp < $tstart "
			. " ORDER BY utimestamp DESC LIMIT 1;";
	$previous_event = db_get_all_rows_sql($query, $historydb);

	if ($previous_event !== false) {
		$last_status = $previous_event[0]["event_type"] == "going_unknown" ? 1:0;
	}
	else {
		$last_status = 0;
	}

	if ((! is_array($events)) && (! is_array($previous_event))) {
		return false;
	}

	if (! is_array($events)) {
		if ($previous_event[0]["event_type"] == "going_unknown") {
			return array(
				array(
					"time_from" => (($fix_to_range == 1)?$tstart:$previous_event[0]["utimestamp"]),
				)
			);
		}
	}

	$return = array();
	$i=0;
	if(is_array($events)){
		foreach ($events as $event) {
			switch ($event["event_type"]) {
				case "going_up_critical":
				case "going_up_warning":
				case "going_up_normal":
				case "going_down_critical":
				case "going_down_warning":
				case "going_down_normal": {
					if ($last_status == 1) {
						$return[$i]["time_to"] = $event["utimestamp"];
						$i++;
						$last_status = 0;
					}
					break;
				}
				case "going_unknown":{
					if ($last_status == 0){
						$return[$i] = array();
						$return[$i]["time_from"] = $event["utimestamp"];
						$last_status = 1;
					}
					break;
				}
			}
		}
	}
	if(!isset($return[0])){
		return false;
	}

	return $return;
}

/**
 * Uncompresses and returns the data of a given id_agent_module
 *
 * @param int          $id_agente_modulo  id_agente_modulo
 * @param utimestamp   $tstart            Begin of the catch
 * @param utimestamp   $tend              End of the catch
 * @param int          $interval          Size of slice (default-> module_interval)
 *
 * @return   hash with the data uncompressed in blocks of module_interval
 * false in case of empty result
 *
 * Note: All "unknown" data are marked as NULL
 * Warning: Be careful with the amount of data, check your RAM size available
 * We'll return a bidimensional array
 * Structure returned: schema:
 *
 * uncompressed_data =>
 *      pool_id (int)
 *          utimestamp (start of current slice)
 *          data
 *              array
 *                  datos
 *                  utimestamp
 *
 */
function db_uncompress_module_data($id_agente_modulo, $tstart = false, $tend = false, $slice_size = false) {
	global $config;

	if (!isset($id_agente_modulo)) {
		return false;
	}

	if ((!isset($tend)) || ($tend === false)) {
		// Return data until now
		$tend = time();
	}

	if ($tstart > $tend) {
		return false;
	}

	$search_historydb = false;
	$table = "tagente_datos";

	$module = modules_get_agentmodule($id_agente_modulo);
	if ($module === false){
		// module not exists
		return false;
	}
	$module_type = $module['id_tipo_modulo'];
	$module_type_str = modules_get_type_name ($module_type);

	if (strstr ($module_type_str, 'string') !== false) {
		$table = "tagente_datos_string";
	}

	$flag_async = false;
	if(strstr ($module_type_str, 'async_data') !== false) {
		$flag_async = true;
	}
	if(strstr ($module_type_str, 'async_proc') !== false) {
		$flag_async = true;
	}

	$result = modules_get_first_date($id_agente_modulo,$tstart);
	$first_utimestamp = $result["first_utimestamp"];
	$search_historydb = $result["search_historydb"];

	if ($first_utimestamp === false) {
		$first_data["utimestamp"] = $tstart;
		$first_data["datos"]      = false;
	}
	else {
		$query  = "SELECT datos,utimestamp FROM $table ";
		$query .= " WHERE id_agente_modulo=$id_agente_modulo ";
		$query .= " AND utimestamp = " . $first_utimestamp;

		$data = db_get_all_rows_sql($query,$search_historydb);

		if ($data === false) {
			// first utimestamp not found in active database
			// SEARCH HISTORY DB
			$search_historydb = true;
			$data = db_get_all_rows_sql($query,$search_historydb);
		}

		if ($data === false) { // Not init
			$first_data["utimestamp"] = $tstart;
			$first_data["datos"]      = false;
		}
		else {
			$first_data["utimestamp"] = $data[0]["utimestamp"];
			$first_data["datos"]      = $data[0]["datos"];
		}
	}

	$query  = " SELECT utimestamp, datos FROM $table ";
	$query .= " WHERE id_agente_modulo=$id_agente_modulo AND utimestamp >= $tstart AND utimestamp <= $tend";
	$query .= " ORDER BY utimestamp ASC";
	// Retrieve all data from module in given range
	$raw_data = db_get_all_rows_sql($query, $search_historydb);

	$module_interval = modules_get_interval ($id_agente_modulo);

	if (($raw_data === false) && ( $first_utimestamp === false )) {
		// No data
		return false;
	}

	// Retrieve going unknown events in range
	$unknown_events = db_get_module_ranges_unknown(
		$id_agente_modulo, $tstart,
		$tend, $search_historydb, 1
	);

	//if time to is missing in last event force time to outside range time
	if( $unknown_events && !isset($unknown_events[count($unknown_events) -1]['time_to']) ){
		$unknown_events[count($unknown_events) -1]['time_to'] = $tend;
	}

	//if time to is missing in first event force time to outside range time
	if ($first_data["datos"] === false && !$flag_async) {
		$last_inserted_value = false;
	}elseif(($unknown_events && !isset($unknown_events[0]['time_from']) && !$flag_async) ||
			($first_utimestamp < $tstart - (SECONDS_1DAY + 2*$module_interval) && !$flag_async) ){
		$last_inserted_value = $first_data["datos"];
		$unknown_events[0]['time_from'] = $tstart;
	}
	else{
		$last_inserted_value = $first_data["datos"];
	}

	// Retrieve module_interval to build the template
	if($slice_size === false){
		$slice_size = $module_interval;
	}

	$return = array();

	// Point current_timestamp to begin of the set and initialize flags
	$current_timestamp   = $tstart;
	$last_timestamp      = $first_data["utimestamp"];
	$last_value          = $first_data["datos"];

	//reverse array data optimization
	if (is_array($raw_data)) {
		$raw_data = array_reverse($raw_data);
	}

	// Build template
	$pool_id = 0;
	$now = time();

	if($unknown_events){
		$current_unknown  = array_shift($unknown_events);
	}
	else{
		$current_unknown = null;
	}

	if(is_array($raw_data)) {
		$current_raw_data = array_pop($raw_data);
	}
	else{
		$current_raw_data = null;
	}

	while ( $current_timestamp < $tend ) {
		$return[$pool_id]["data"] = array();
		$tmp_data   = array();
		$current_timestamp_end = $current_timestamp + $slice_size;

		if (($current_timestamp > $now)
		|| (($current_timestamp - $last_timestamp) > (SECONDS_1DAY + 2 * $module_interval)) ) {

			$tmp_data["utimestamp"] = $current_timestamp;

			//check not init
			$tmp_data["datos"] = $last_value === false ? false : null;

			//async not unknown
			if($flag_async &&  $tmp_data["datos"] === null){
				$tmp_data["datos"] = $last_inserted_value;
			}

			// debug purpose
			//$tmp_data["obs"] = "unknown extra";
			array_push($return[$pool_id]["data"], $tmp_data);
		}

		//insert raw data
		while ( ($current_raw_data != null) &&
				(   ($current_timestamp_end >  $current_raw_data['utimestamp']) &&
					($current_timestamp     <= $current_raw_data['utimestamp']) ) ) {

			// Add real data detected
			if (count($return[$pool_id]['data']) == 0) {
				//insert first slice data
				$tmp_data["utimestamp"] = $current_timestamp;
				$tmp_data["datos"]  =  $last_inserted_value;
				// debug purpose
				//$tmp_data["obs"] = "virtual data (raw)";
				$tmp_data["type"] = ($current_timestamp == $tstart || ($current_timestamp == $tend)?0:1); // virtual data

				//Add order to avoid usort missorder in same utimestamp data cells
				$tmp_data["order"] = 1;
				array_push($return[$pool_id]["data"], $tmp_data);
			}

			$tmp_data["utimestamp"] = $current_raw_data["utimestamp"];
			$tmp_data["datos"]      = $current_raw_data["datos"];
			$tmp_data["type"] = 0; // real data
			// debug purpose
			//$tmp_data["obs"] = "real data";
			//Add order to avoid usort missorder in same utimestamp data cells
			$tmp_data["order"] = 2;
			array_push($return[$pool_id]["data"], $tmp_data);

			$last_value = $current_raw_data["datos"];
			$last_timestamp = $current_raw_data["utimestamp"];
			if($raw_data){
				$current_raw_data = array_pop($raw_data);
			}
			else{
				$current_raw_data = null;
			}
		}

		//unknown
		$data_slices = $return[$pool_id]["data"];
		if(!$flag_async){
			while ( ($current_unknown != null) &&
					( ( ($current_unknown['time_from'] != null) &&
						($current_timestamp_end >= $current_unknown['time_from']) ) ||
					($current_timestamp_end >= $current_unknown['time_to']) ) ) {

				if( ( $current_timestamp <= $current_unknown['time_from']) &&
					( $current_timestamp_end >= $current_unknown['time_from'] ) ){
					if (count($return[$pool_id]['data']) == 0) {
						//insert first slice data
						$tmp_data["utimestamp"] = $current_timestamp;
						$tmp_data["datos"]  =  $last_inserted_value;
						// debug purpose
						//$tmp_data["obs"] = "virtual data (e)";
						//Add order to avoid usort missorder in same utimestamp data cells
						$tmp_data["order"] = 1;

						array_push($return[$pool_id]["data"], $tmp_data);
					}

					// Add unknown state detected
					$tmp_data["utimestamp"] = $current_unknown["time_from"];
					$tmp_data["datos"]      = null;
					// debug purpose
					//$tmp_data["obs"] = "event data unknown from";
					//Add order to avoid usort missorder in same utimestamp data cells
					$tmp_data["order"] = 2;
					array_push($return[$pool_id]["data"], $tmp_data);
					$current_unknown["time_from"] = null;
				}
				elseif( ($current_timestamp <= $current_unknown['time_to']) &&
					($current_timestamp_end > $current_unknown['time_to'] ) ){

					if (count($return[$pool_id]['data']) == 0) {
						//add first slice data always
						//insert first slice data
						$tmp_data["utimestamp"] = $current_timestamp;
						$tmp_data["datos"]  =  $last_inserted_value;
						// debug purpose
						//$tmp_data["obs"] = "virtual data (event_to)";
						//Add order to avoid usort missorder in same utimestamp data cells
						$tmp_data["order"] = 1;
						array_push($return[$pool_id]["data"], $tmp_data);
					}

					$tmp_data["utimestamp"] = $current_unknown["time_to"];
					//Add order to avoid usort missorder in same utimestamp data cells
					$tmp_data["order"] = 2;
					$i = count($data_slices);
					while ($i >= 0) {
						if($data_slices[$i]['utimestamp'] <= $current_unknown["time_to"]){
							$tmp_data["datos"] = 
								$data_slices[$i]['datos'] == null
								? $last_value
								: $data_slices[$i]['datos'];
							break;
						}
						$i--;
					}

					// debug purpose
					//$tmp_data["obs"] = "event data unknown to";
					array_push($return[$pool_id]["data"], $tmp_data);
					if($unknown_events){
						$current_unknown = array_shift($unknown_events);
					}
					else{
						$current_unknown = null;
					}
				}
				else {
					break;
				}
			}
		}

		$return[$pool_id]["utimestamp"] = $current_timestamp;
		if (count($return[$pool_id]['data']) == 0) {
			//insert first slice data
			$tmp_data["utimestamp"] = $current_timestamp;
			$tmp_data["datos"] =  $last_inserted_value;
			// debug purpose
			//$tmp_data["obs"] = "virtual data (empty)";
			array_push($return[$pool_id]["data"], $tmp_data);
		}

		//sort current slice
		if(count($return[$pool_id]['data']) > 1) {
			usort(
				$return[$pool_id]['data'],
				function ($a, $b) {
					if ($a['utimestamp'] == $b['utimestamp']) return (($a['order'] < $b['order'])?-1:1);
					return ($a['utimestamp'] < $b['utimestamp']) ? -1 : 1;
				}
			);
		}
		//put the last slice data like first element of next slice
		$last_inserted_value = end($return[$pool_id]['data']);
		$last_inserted_value = $last_inserted_value['datos'];

		//increment
		$pool_id++;
		$current_timestamp = $current_timestamp_end;
	}

	//slice to the end.
	if($pool_id == 1){
		$end_array = array();
		$end_array['data'][0]['utimestamp'] = $tend;
		$end_array['data'][0]['datos']      = $last_inserted_value;
		//$end_array['data'][0]['obs']        = 'virtual data END';
		array_push($return, $end_array);
	}

	return $return;
}

/**
 * Get all the rows of a table in the database that matches a filter.
 *
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 * <code>
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled', 0));
 * db_get_all_rows_filter ('table', 'disabled = 0');
 *
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name', 'OR');
 * db_get_all_rows_filter ('table', 'disabled = 0 OR history_data = 0', 'name');
 * </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition of the filter (AND, OR).
 * @param bool $returnSQL Return a string with SQL instead the data, by default false.
 *
 * @return mixed Array of the row or false in case of error.
 */
function db_get_all_rows_filter($table, $filter = array(), $fields = false, $where_join = 'AND', $search_history_db = false, $returnSQL = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
		case "oracle":
			return oracle_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
	}
}

/**
 * Get row by row the DB by SQL query. The first time pass the SQL query and
 * rest of times pass none for iterate in table and extract row by row, and
 * the end return false.
 *
 * @param bool $new Default true, if true start to query.
 * @param resource $result The resource of mysql for access to query.
 * @param string $sql
 * @return mixed The row or false in error.
 */
function db_get_all_row_by_steps_sql($new = true, &$result, $sql = null) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
		case "postgresql":
			return postgresql_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
		case "oracle":
			return oracle_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
	}
}

/**
 * Return the count of rows of query.
 *
 * @param $sql
 * @return integer The count of rows of query.
 */
function db_get_num_rows($sql) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_num_rows($sql);
			break;
		case "postgresql":
			return postgresql_db_get_num_rows($sql);
			break;
		case "oracle":
			return oracle_db_get_num_rows($sql);
			break;
	}
}

/**
 * Error handler function when an SQL error is triggered.
 *
 * @param int Level of the error raised (not used, but required by set_error_handler()).
 * @param string Contains the error message.
 *
 * @return bool True if error level is lower or equal than errno.
 */
function db_sql_error_handler ($errno, $errstr) {
	global $config;
	
	/* If debug is activated, this will also show the backtrace */
	if (ui_debug ($errstr))
		return false;
	
	if (error_reporting () <= $errno)
		return false;
	
	echo "<strong>SQL error</strong>: ".$errstr."<br />\n";
	
	return true;
}

/**
 * Add a database query to the debug trace.
 *
 * This functions does nothing if the config['debug'] flag is not set. If a
 * sentence was repeated, then the 'saved' counter is incremented.
 *
 * @param string SQL sentence.
 * @param mixed Query result. On error, error string should be given.
 * @param int Affected rows after running the query.
 * @param mixed Extra parameter for future values.
 */
function db_add_database_debug_trace ($sql, $result = false, $affected = false, $extra = false) {
	global $config;
	
	if (! isset ($config['debug']))
	return false;
	
	if (! isset ($config['db_debug']))
	$config['db_debug'] = array ();
	
	if (isset ($config['db_debug'][$sql])) {
		$config['db_debug'][$sql]['saved']++;
		return;
	}
	
	$var = array ();
	$var['sql'] = $sql;
	$var['result'] = $result;
	$var['affected'] = $affected;
	$var['saved'] = 0;
	$var['extra'] = $extra;
	
	$config['db_debug'][$sql] = $var;
}

/**
 * Clean the cache for to have errors and ghost rows when you do "select <table>",
 * "delete <table>" and "select <table>".
 *
 * @return None
 */
function db_clean_cache() {
	global $sql_cache;
	
	$sql_cache = array ('saved' => array ());
}

/**
 * Change the sql cache id to another value
 *
 * @return None
 */
function db_change_cache_id($name, $host) {
	global $sql_cache;
	
	// Update the sql cache identification 
	$sql_cache['id'] = $name . "_" . $host;
	if (!isset ($sql_cache['saved'][$sql_cache['id']])){
		$sql_cache['saved'][$sql_cache['id']] = 0;
	}
}

/**
 * Get the total cached queries and the databases checked
 *
 * @return (total_queries, total_dbs)
 */
function db_get_cached_queries() {
	global $sql_cache;
	
	$total_saved = 0;
	$total_dbs = 0;
	foreach ($sql_cache['saved'] as $saver) {
		$total_saved += format_numeric($saver);
		$total_dbs++;
	}
	
	return array ($total_saved, $total_dbs);
}

/**
 * This function comes back with an array in case of SELECT
 * in case of UPDATE, DELETE etc. with affected rows
 * an empty array in case of SELECT without results
 * Queries that return data will be cached so queries don't get repeated
 *
 * @param string SQL statement to execute
 *
 * @param string What type of info to return in case of INSERT/UPDATE.
 *		'affected_rows' will return mysql_affected_rows (default value)
 *		'insert_id' will return the ID of an autoincrement value
 *		'info' will return the full (debug) information of a query
 *
 * @param string $status The status and type of query (support only postgreSQL).
 *
 * @param bool $autocommit (Only oracle) Set autocommit transaction mode true/false 
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
function db_process_sql($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true, &$status = null, $autocommit = true) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return @mysql_db_process_sql($sql, $rettype, $dbconnection, $cache);
			break;
		case "postgresql":
			return @postgresql_db_process_sql($sql, $rettype, $dbconnection, $cache, $status);
			break;
		case "oracle":
			return oracle_db_process_sql($sql, $rettype, $dbconnection, $cache, $status, $autocommit);
			break;
	}
}

/**
 * Get all the rows in a table of the database.
 *
 * @param string Database table name.
 * @param string Field to order by.
 * @param string $order The type of order, by default 'ASC'.
 *
 * @return mixed A matrix with all the values in the table
 */
function db_get_all_rows_in_table ($table, $order_field = "", $order = 'ASC') {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_in_table($table, $order_field, $order);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_in_table($table, $order_field, $order);
			break;
		case "oracle":
			return oracle_db_get_all_rows_in_table($table, $order_field, $order);
			break;
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 * @param string Condition the field must have to be selected.
 * @param string Field to order by.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field or false
 */
function db_get_all_rows_field_filter($table, $field, $condition, $order_field = "") {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
		case "oracle":
			return oracle_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function db_get_all_fields_in_table($table, $field = '', $condition = '', $order_field = '') {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
		case "postgresql":
			return postgresql_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
		case "oracle":
			return oracle_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
	}
}

/**
 * Formats an array of values into a SQL string.
 *
 * This function is useful to generate an UPDATE SQL sentence from a list of
 * values. Example code:
 *
 * <code>
 * $values = array ();
 * $values['name'] = "Name";
 * $values['description'] = "Long description";
 * $sql = 'UPDATE table SET '.db_format_array_to_update_sql ($values).' WHERE id=1';
 * echo $sql;
 * </code>
 * Will return:
 * <code>
 * UPDATE table SET `name` = "Name", `description` = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function db_format_array_to_update_sql($values) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_format_array_to_update_sql($values);
			break;
		case "postgresql":
			return postgresql_db_format_array_to_update_sql($values);
			break;
		case "oracle":
			return oracle_db_format_array_to_update_sql($values);
			break;
	}
}

/**
 * Formats an array of values into a SQL where clause string.
 *
 * This function is useful to generate a WHERE clause for a SQL sentence from
 * a list of values. Example code:
 <code>
 $values = array ();
 $values['name'] = "Name";
 $values['description'] = "Long description";
 $values['limit'] = $config['block_size']; // Assume it's 20
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 echo $sql;
 </code>
 * Will return:
 * <code>
 * SELECT * FROM table WHERE `name` = "Name" AND `description` = "Long description" LIMIT 20
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 * There are special parameters such as 'limit' and 'offset' that will be used
 * as ORDER, LIMIT and OFFSET clauses respectively. Since LIMIT and OFFSET are
 * numerics, ORDER can receive a field name or a SQL function and a the ASC or
 * DESC clause. Examples:
 <code>
 $values = array ();
 $values['value'] = 10;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // SELECT * FROM table WHERE VALUE = 10

 $values = array ();
 $values['value'] = 10;
 $values['order'] = 'name DESC';
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // SELECT * FROM table WHERE VALUE = 10 ORDER BY name DESC

 </code>
 * @param string Join operator. AND by default.
 * @param string A prefix to be added to the string. It's useful when limit and
 * offset could be given to avoid this cases:
 <code>
 $values = array ();
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // Wrong SQL: SELECT * FROM table WHERE LIMIT 10 OFFSET 20

 $values = array ();
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values, 'AND', 'WHERE');
 // Good SQL: SELECT * FROM table LIMIT 10 OFFSET 20

 $values = array ();
 $values['value'] = 5;
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values, 'AND', 'WHERE');
 // Good SQL: SELECT * FROM table WHERE value = 5 LIMIT 10 OFFSET 20
 </code>
 *
 * @return string Values joined into an SQL string that can fits into the WHERE
 * clause of an SQL sentence.

 // IMPORTANT!!! OFFSET parameter is not allowed for Oracle because Oracle needs to recode the complete query. 
 // use oracle_format_query() function instead.
 */
function db_format_array_where_clause_sql ($values, $join = 'AND', $prefix = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
		case "postgresql":
			return postgresql_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
		case "oracle":
			return oracle_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
	}
}

/**
 * Delete query without commit transaction
 *
 * @param string Table name
 * @param string Field of the filter condition
 * @param string Value of the filter
 * @param bool The value will be appended without quotes
 *
 * @result Rows deleted or false if something goes wrong
 */
function db_process_delete_temp ($table, $row, $value, $custom_value = false) {
	global $error; //Globalize the errors variable
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$result = db_process_sql_delete ($table, $row.' = '.$value);
			break;
		case "oracle":
			if ($custom_value || is_int ($value) || is_bool ($value) ||
					is_float ($value) || is_double ($value)) {
				$result = oracle_db_process_sql_delete_temp ($table, $row . ' = ' . $value);
			}
			else {
				$result = oracle_db_process_sql_delete_temp ($table, $row . " = '" . $value . "'");
			}
			break;
	}
	
	if ($result === false) {
		$error = true;
	}
}

/**
 * Inserts strings into database
 *
 * The number of values should be the same or a positive integer multiple as the number of rows
 * If you have an associate array (eg. array ("row1" => "value1")) you can use this function with ($table, array_keys ($array), $array) in it's options
 * All arrays and values should have been cleaned before passing. It's not neccessary to add quotes.
 *
 * @param string Table to insert into
 * @param mixed A single value or array of values to insert (can be a multiple amount of rows)
 * @param bool Whether to do autocommit or not (only Oracle)
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_insert($table, $values, $autocommit = true) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_insert($table, $values);
			break;
		case "postgresql":
			return postgresql_db_process_sql_insert($table, $values);
			break;
		case "oracle":
			return oracle_db_process_sql_insert($table, $values, $autocommit);
			break;
	}
}

/**
 * Updates a database record.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id));
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name));
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name), 'OR');
 * db_process_sql_update ('table', array ('field' => 2), 'id in (1, 2, 3) OR id > 10');
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 * @param bool Transaction automatically commited or not 
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_update($table, $values, $where = false, $where_join = 'AND', $autocommit = true) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_update($table, $values, $where, $where_join);
			break;
		case "postgresql":
			return postgresql_db_process_sql_update($table, $values, $where, $where_join);
			break;
		case "oracle":
			return oracle_db_process_sql_update($table, $values, $where, $where_join, $autocommit);
			break;
	}
}

/**
 * Delete database records.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
 * db_process_sql_delete ('table', array ('id' => 1));
 * // DELETE FROM table WHERE id = 1
 * db_process_sql_delete ('table', array ('id' => 1, 'name' => 'example'));
 * // DELETE FROM table WHERE id = 1 AND name = 'example'
 * db_process_sql_delete ('table', array ('id' => 1, 'name' => 'example'), 'OR');
 * // DELETE FROM table WHERE id = 1 OR name = 'example'
 * db_process_sql_delete ('table', 'id in (1, 2, 3) OR id > 10');
 * // DELETE FROM table WHERE id in (1, 2, 3) OR id > 10
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_delete($table, $where, $where_join = 'AND') {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_delete($table, $where, $where_join);
			break;
		case "postgresql":
			return postgresql_db_process_sql_delete($table, $where, $where_join);
			break;
		case "oracle":
			return oracle_db_process_sql_delete($table, $where, $where_join);
			break;
	}
}

/**
 * Starts a database transaction.
 */
function db_process_sql_begin() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_begin();
			break;
		case "postgresql":
			return postgresql_db_process_sql_begin();
			break;
		case "oracle":
			return oracle_db_process_sql_begin();
			break;
	}
}

/**
 * Commits a database transaction.
 */
function db_process_sql_commit() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_commit();
			break;
		case "postgresql":
			return postgresql_db_process_sql_commit();
			break;
		case "oracle":
			return oracle_db_process_sql_commit();
			break;
	}
}

/**
 * Rollbacks a database transaction.
 */
function db_process_sql_rollback() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_rollback();
			break;
		case "postgresql":
			return postgresql_db_process_sql_rollback();
			break;
		case "oracle":
			return oracle_db_process_sql_rollback();
			break;
	}
}

/**
 * Prints a database debug table with all the queries done in the page loading.
 *
 * This functions does nothing if the config['debug'] flag is not set.
 */
function db_print_database_debug () {
	global $config;
	
	if (! isset ($config['debug']))
		return '';
	
	echo '<div class="database_debug_title">'.__('Database debug').'</div>';
	
	$table->id = 'database_debug';
	$table->cellpadding = '0';
	$table->width = '95%';
	$table->align = array ();
	$table->align[1] = 'left';
	$table->size = array ();
	$table->size[0] = '40px';
	$table->size[2] = '30%';
	$table->size[3] = '40px';
	$table->size[4] = '40px';
	$table->size[5] = '40px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = '#';
	$table->head[1] = __('SQL sentence');
	$table->head[2] = __('Result');
	$table->head[3] = __('Rows');
	$table->head[4] = __('Saved');
	$table->head[5] = __('Time (ms)');
	
	if (! isset ($config['db_debug']))
		$config['db_debug'] = array ();
	$i = 1;
	foreach ($config['db_debug'] as $debug) {
		$data = array ();
		
		$data[0] = $i++;
		$data[1] = $debug['sql'];
		$data[2] = (empty ($debug['result']) ? __('OK') : $debug['result']);
		$data[3] = $debug['affected'];
		$data[4] = $debug['saved'];
		$data[5] = (isset ($debug['extra']['time']) ? format_numeric ($debug['extra']['time'] * 1000, 0) : '');
		
		array_push ($table->data, $data);
		
		if (($i % 100) == 0) {
			html_print_table ($table);
			$table->data = array ();
		}
	}
	
	html_print_table ($table);
}

/**
 * Get last error.
 * 
 * @return string Return the string error.
 */
function db_get_last_error() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_last_error();
			break;
		case "postgresql":
			return postgresql_db_get_last_error();
			break;
		case "oracle":
			return oracle_db_get_last_error();
			break;
	}
}

/**
 * Get the type of field.
 * 
 * @param string $table The table to examine the type of field.
 * @param integer $field The field order in table.
 * 
 * @return mixed Return the type name or False in error case.
 */
function db_get_type_field_table($table, $field) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_type_field_table($table, $field);
			break;
		case "postgresql":
			return postgresql_db_get_type_field_table($table, $field);
			break;
		case "oracle":
			return oracle_db_get_type_field_table($table, $field);
			break;
	}
}

/**
 * Get the columns of a table.
 * 
 * @param string $table table to retrieve columns.
 * 
 * @return array with column names.
 */
function db_get_fields($table) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_get_fields($table);
			break;
		case "postgresql":
			//return postgresql_get_fields($table);
			break;
		case "oracle":
			//return oracle_get_fields($table);
			break;
	}
}

/**
 * @param int Unix timestamp with the date.
 * 
 * @return bool Returns true if the history db has data after the date provided or false otherwise.
 */
function db_search_in_history_db ($utimestamp) {
	global $config;

	$search_in_history_db = false;
	if ($config['history_db_enabled'] == 1) {
		$history_db_start_period = $config['history_db_days'] * SECONDS_1DAY;

		// If the date is newer than the newest history db data
		if (time() - $history_db_start_period >= $utimestamp)
			$search_in_history_db = true;
	}

	return $search_in_history_db;
}

/**
 * Process a file with an oracle schema sentences.
 * Based on the function which installs the pandoradb.sql schema.
 * 
 * @param string $path File path.
 * @param bool $handle_error Whether to handle the oci_execute errors or throw an exception.
 * 
 * @return bool Return the final status of the operation.
 */
function db_process_file ($path, $handle_error = true) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_file($path, $handle_error);
			break;
		case "postgresql":
			// Not supported
			//return postgresql_db_process_file($path, $handle_error);
			break;
		case "oracle":
			return oracle_db_process_file($path, $handle_error);
			break;
	}
}

/**
 * Search for minor release files.
 * 
 * @return bool Return if minor release is available or not
 */
function db_check_minor_relase_available () {
	global $config;

	if (!$config['enable_update_manager']) return false;
	
	$dir = $config["homedir"]."/extras/mr";
	
	$have_minor_release = false;
	
	if (file_exists($dir) && is_dir($dir)) {
		if (is_readable($dir)) {
			$files = scandir($dir); // Get all the files from the directory ordered by asc

			if ($files !== false) {
				$pattern = "/^\d+\.sql$/";
				$sqlfiles = preg_grep($pattern, $files); // Get the name of the correct files
				$files = null;
				$pattern = "/\.sql$/";
				$replacement = "";
				$sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles); // Get the number of the file
				
				if ($sqlfiles_num) {
					foreach ($sqlfiles_num as $sqlfile_num) {
						if ($config["MR"] < $sqlfile_num) {
							$have_minor_release = true;
						}
					}
				}
			}
		}
	}
	
	if ($have_minor_release) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Search for minor release files.
 * 
 * @return bool Return if minor release is available or not
 */
function db_check_minor_relase_available_to_um ($package, $ent, $offline) {
	global $config;
	
	if (!$config['enable_update_manager']) return false;

	if (!$ent) {
		$dir = $config['attachment_store'] . "/downloads/pandora_console/extras/mr";
	}
	else {
		if ($offline) {
			$dir = $package . "/extras/mr";
		}
		else {
			$dir = sys_get_temp_dir() . "/pandora_oum/" . $package . "/extras/mr";
		}
	}
	
	$have_minor_release = false;
	if (file_exists($dir) && is_dir($dir)) {
		if (is_readable($dir)) {
			$files = scandir($dir); // Get all the files from the directory ordered by asc

			if ($files !== false) {
				$pattern = "/^\d+\.sql$/";
				$sqlfiles = preg_grep($pattern, $files); // Get the name of the correct files
				$files = null;
				$pattern = "/\.sql$/";
				$replacement = "";
				$sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles); // Get the number of the file
				
				$exists = false;
				foreach ($sqlfiles_num as $num) {
					$file_dest = $config["homedir"] . "/extras/mr/updated/$num.sql";
					if (file_exists($file_dest)) {
						$exists = true;
						update_config_token("MR", $num);
						if (file_exists($config["homedir"] . "/extras/mr/$num.sql")) {
							unlink($config["homedir"] . "/extras/mr/$num.sql");
						}
					}
				}

				if ($sqlfiles_num && !$exists) {
					$have_minor_release = true;
				}
			}
		}
	}
	
	if ($have_minor_release) {
		return true;
	}
	else {
		return false;
	}
}

?>
