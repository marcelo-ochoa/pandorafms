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
global $statusProcessInDB;

check_login ();

require_once ($config['homedir'] . '/include/functions_visual_map.php');
require_once($config['homedir'] . "/include/functions_agents.php");
enterprise_include_once('include/functions_visual_map.php');

// Retrieve the visual console id
set_unless_defined ($idVisualConsole, 0); // Set default
$idVisualConsole = get_parameter('id_visual_console', $idVisualConsole);

if (!defined('METACONSOLE')) {
	$action_name_parameter = 'action';
}
else {
	$action_name_parameter = 'action2';
}

$action = get_parameterBetweenListValues($action_name_parameter,
	array('new', 'save', 'edit', 'update', 'delete', 'multiple_delete'),
	'new');

$activeTab = get_parameterBetweenListValues('tab',
	array('data', 'list_elements', 'wizard', 'wizard_services', 'editor'),
	'data');

// Visual console creation tab and actions
if (empty($idVisualConsole)) {
	$visualConsole = null;
	
	// General ACL
	//$vconsole_read = check_acl ($config['id_user'], 0, "VR");
	$vconsole_write = check_acl ($config['id_user'], 0, "VW");
	$vconsole_manage = check_acl ($config['id_user'], 0, "VM");
}
// The visual console exists
else if ($activeTab != 'data' || ($activeTab == 'data' && $action != 'new')) {
	
	// Load the visual console data
	$visualConsole = db_get_row_filter('tlayout', array('id' => $idVisualConsole));
	
	// The visual console should exist.
	if (empty($visualConsole)) {
		db_pandora_audit("ACL Violation",
			"Trying to access report builder");
		require ("general/noaccess.php");
		return;
	}
	
	// The default group id is 0
	set_unless_defined ($visualConsole['id_group'], 0);
	
	// ACL for the existing visual console
	//$vconsole_read = check_acl ($config['id_user'], $visualConsole['id_group'], "VR");
	$vconsole_write = check_acl ($config['id_user'], $visualConsole['id_group'], "VW");
	$vconsole_manage = check_acl ($config['id_user'], $visualConsole['id_group'], "VM");
}
else {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	return;
}

// This section is only to manage the visual console
if (!$vconsole_write && !$vconsole_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$pure = (int) get_parameter ('pure', 0);
$refr = (int) get_parameter ('refr', $config['vc_refr']);

$id_layout = 0;


//Save/Update data in DB
global $statusProcessInDB;
if (empty($statusProcessInDB))
	$statusProcessInDB = null;
switch ($activeTab) {
	case 'data':
		switch ($action) {
			case 'new':
				$idGroup = '';
				$background = '';
				$background_color = '';
				$width = '';
				$height = '';
				$visualConsoleName = '';
				$is_favourite = 0;
				break;
			
			case 'update':
			case 'save':
				$idGroup = (int) get_parameter('id_group');
				$background = (string) get_parameter('background');
				$background_color = (string) get_parameter('background_color');
				$width = (int) get_parameter('width');
				$height = (int) get_parameter('height');
				$visualConsoleName = (string) get_parameter('name');
				$is_favourite  = (int) get_parameter('is_favourite_sent');

				// ACL for the new visual console
				//$vconsole_read_new = check_acl ($config['id_user'], $idGroup, "VR");
				$vconsole_write_new = check_acl ($config['id_user'], $idGroup, "VW");
				$vconsole_manage_new = check_acl ($config['id_user'], $idGroup, "VM");
				
				// The user should have permissions on the new group
				if (!$vconsole_write_new && !$vconsole_manage_new) {
					db_pandora_audit("ACL Violation",
						"Trying to access report builder");
					require ("general/noaccess.php");
					exit;
				}
				
				$values = array(
						'name' => $visualConsoleName,
						'id_group' => $idGroup, 
						'background' => $background,
						'background_color' => $background_color,
						'width' => $width,
						'height' => $height,
						'is_favourite' => $is_favourite
				);
				
				$error = $_FILES['background_image']['error'];
				$upload_file = true;
				$uploadOK = true;
				switch ($error) {
					case UPLOAD_ERR_OK:
						$tmpName = $_FILES['background_image']['tmp_name'];
						$pathname = $config['homedir'] . '/images/console/background/';
						$nameImage = str_replace(' ','_',$_FILES["background_image"]["name"]);
						$target_file = $pathname . basename($nameImage);
						$imageFileType = strtolower( pathinfo($target_file,PATHINFO_EXTENSION));
						
						$check = getimagesize($_FILES["background_image"]["tmp_name"]);
						if($check !== false) {
							$uploadOK = 1;
						} else {
							$uploadOK = false;
							$error_message = __("This file isn't image");
							$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__("This file isn't image."), '', true));
						}
						if (file_exists($target_file)) {
							$uploadOK = false;
							$error_message = __("File already are exists.");
							$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('File already are exists.'), '', true));
						}
						
						if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
						&& $imageFileType != "gif" ) {
							$uploadOK = false;
							$error_message = __("The file have not image extension.");
							$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('The file have not image extension.'), '', true));
						}
						
						if ($uploadOK == 1) {
							if (move_uploaded_file($_FILES["background_image"]["tmp_name"], $target_file)) {
								$background = $nameImage;
								$values['background'] = $background;
								$error2 = chmod($target_file, 0644);
								$uploadOK = $error2;
							} else {
								$uploadOK = false;
								$error_message = __("Problems with move file to target.");
								$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('Problems with move file to target.'), '', true));
								
							}
						}
						break;
					case UPLOAD_ERR_INI_SIZE:
						$uploadOK = false;
						$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('Problems with move file to target.'), '', true));
					case UPLOAD_ERR_PARTIAL:
						$uploadOK = false;
						$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('Problems with move file to target.'), '', true));
						break;
					case UPLOAD_ERR_NO_FILE:
						$upload_file = false;
						break;
				}
				
				if ( $upload_file && !$uploadOK){
					db_pandora_audit( "Visual console builder", $error_message);
					break;
				}
					
				// If the background is changed the size is reseted
				$background_now = $visualConsole['background'];
			
				$values['width'] = $width;
				$values['height'] = $height;
				switch ($action) {
					case 'update':
						$result = false;
						if ($values['name'] != "" && $values['background'])
							$result = db_process_sql_update('tlayout', $values, array('id' => $idVisualConsole));
						if ($result !== false) {
							db_pandora_audit( "Visual console builder", "Update visual console #$idVisualConsole");
							$action = 'edit';
							$statusProcessInDB = array('flag' => true, 'message' => ui_print_success_message(__('Successfully update.'), '', true));
							
							// Return the updated visual console
							$visualConsole = db_get_row_filter('tlayout',
								array('id' => $idVisualConsole));
							// Update the ACL
							//$vconsole_read = $vconsole_read_new;
							$vconsole_write = $vconsole_write_new;
							$vconsole_manage = $vconsole_manage_new;
						}
						else {
							db_pandora_audit( "Visual console builder", "Fail update visual console #$idVisualConsole");
							$statusProcessInDB = array('flag' => false, 'message' => ui_print_error_message(__('Could not be update.'), '', true));
						}
						break;
					
					case 'save':
						if ($values['name'] != "" && $values['background'])
							$idVisualConsole = db_process_sql_insert('tlayout', $values);
						else
							$idVisualConsole = false;
						
						if ($idVisualConsole !== false) {
							db_pandora_audit( "Visual console builder", "Create visual console #$idVisualConsole");
							$action = 'edit';
							$statusProcessInDB = array('flag' => true,
								'message' => ui_print_success_message(__('Successfully created.'), '', true));
							
							// Return the updated visual console
							$visualConsole = db_get_row_filter('tlayout',
								array('id' => $idVisualConsole));
							// Update the ACL
							//$vconsole_read = $vconsole_read_new;
							$vconsole_write = $vconsole_write_new;
							$vconsole_manage = $vconsole_manage_new;
						}
						else {
							db_pandora_audit( "Visual console builder", "Fail try to create visual console");
							$statusProcessInDB = array('flag' => false,
								'message' => ui_print_error_message(__('Could not be created.'), '', true));
						}
					break;
				}
				break;
			
			case 'edit':
				$visualConsoleName = $visualConsole['name'];
				$idGroup = $visualConsole['id_group'];
				$background = $visualConsole['background'];
				$background_color = $visualConsole['background_color'];
				$width = $visualConsole['width'];
				$height = $visualConsole['height'];
				$is_favourite = $visualConsole['is_favourite'];
				break;
		}
		break;
	
	case 'list_elements':
		switch ($action) {
			case 'multiple_delete':
				$delete_items_json = io_safe_output(
					get_parameter("id_item_json",
						json_encode(array())));
				
				$delete_items = json_decode($delete_items_json, true);
				
				if (!empty($delete_items)) {
					$result = (bool)db_process_sql_delete(
						'tlayout_data',
						array('id_layout' => $idVisualConsole,
							'id' => $delete_items));
					
				}
				else {
					$result = false;
				}
				
				$statusProcessInDB = array(
					'flag' => true,
					'message' => ui_print_result_message($result,
						__('Successfully multiple delete.'),
						__('Unsuccessful multiple delete.'), '', true));
				break;
			case 'update':
				//Update background
				
				$background = get_parameter('background');
				$background_color = get_parameter('background_color');
				$width = get_parameter('width');
				$height = get_parameter('height');
				
				if ($width == 0 && $height == 0) {
					$sizeBackground = getimagesize(
						$config['homedir'] . '/images/console/background/' . $background);
					$width = $sizeBackground[0];
					$height = $sizeBackground[1];
				}
				
				db_process_sql_update('tlayout',
					array('background' => $background,
					'background_color' => $background_color,
						'width' => $width,
						'height' => $height),
					array('id' => $idVisualConsole));
				
				// Return the updated visual console
				$visualConsole = db_get_row_filter('tlayout',
					array('id' => $idVisualConsole));
				
				//Update elements in visual map
				$idsElements = db_get_all_rows_filter('tlayout_data',
					array('id_layout' => $idVisualConsole), array('id'));
				
				if ($idsElements === false) {
					$idsElements = array();
				}
				
				foreach ($idsElements as $idElement) {
					$id = $idElement['id'];
					$values = array();
					$values['label'] = get_parameter('label_' . $id, '');
					$values['image'] = get_parameter('image_' . $id, '');
					$values['width'] = get_parameter('width_' . $id, 0);
					$values['height'] = get_parameter('height_' . $id, 0);
					$values['pos_x'] = get_parameter('left_' . $id, 0);
					$values['pos_y'] = get_parameter('top_' . $id, 0);
					$type = db_get_value('type', 'tlayout_data', 'id', $id);
					switch ($type) {
						case MODULE_GRAPH:
						case SIMPLE_VALUE_MAX:
						case SIMPLE_VALUE_MIN:
						case SIMPLE_VALUE_AVG:
							$values['period'] = get_parameter('period_' . $id, 0);
							break;
						case GROUP_ITEM:
							$values['id_group'] = get_parameter('group_' . $id, 0);
							$values['show_statistics'] = get_parameter('show_statistics', 0);
							break;
					}
					$agentName = get_parameter('agent_' .  $id, '');
					if (defined('METACONSOLE')) {
						$values['id_metaconsole'] = (int) get_parameter('id_server_id_' . $id, '');
						$values['id_agent'] = (int) get_parameter('id_agent_' . $id, 0);
					}
					else {
						$agent_id = (int) get_parameter('id_agent_' . $id, 0);
						$values['id_agent'] = $agent_id;
					}
					$values['id_agente_modulo'] = get_parameter('module_' . $id, 0);
					$values['id_custom_graph'] = get_parameter('custom_graph_' . $id, 0);
					$values['parent_item'] = get_parameter('parent_' . $id, 0);
					$values['id_layout_linked'] = get_parameter('map_linked_' . $id, 0);
					
					if (enterprise_installed()) {
						enterprise_visual_map_update_action_from_list_elements($type, $values, $id);
					}
					
					db_process_sql_update('tlayout_data', $values, array('id' => $id));
				}
				break;
			case 'delete':
				$id_element = get_parameter('id_element');
				$result = db_process_sql_delete('tlayout_data', array('id' => $id_element));
				if ($result !== false) {
					$statusProcessInDB = array('flag' => true, 'message' => ui_print_success_message(__('Successfully delete.'), '', true));
				}
				break;
		}
		$visualConsoleName = $visualConsole['name'];
		$action = 'edit';
		break;
	case 'wizard':
		$visualConsoleName = $visualConsole['name'];
		$background = $visualConsole['background'];
		
		$fonts = get_parameter ('fonts');
		$fontf = get_parameter ('fontf');
		
		
		switch ($action) {
			case 'update':
				$id_agents = get_parameter ('id_agents', array ());
				$name_modules = get_parameter ('module', array ());
							
				$type = (int)get_parameter('type', STATIC_GRAPH);
				$image = get_parameter ('image');
				$range = (int) get_parameter ("range", 50);
				$width = (int) get_parameter ("width", 0);
				$height = (int) get_parameter ("height", 0);
				$period = (int) get_parameter ("period", 0);
				$show_statistics = get_parameter ("show_statistics", 0);
				$process_value = (int) get_parameter ("process_value", 0);
				$percentileitem_width = (int) get_parameter ("percentileitem_width", 0);
				$max_value = (int) get_parameter ("max_value", 0);
				$type_percentile = get_parameter ("type_percentile", 'percentile');
				$value_show = get_parameter ("value_show", 'percent');
				$label_type = get_parameter ("label_type", 'agent_module');
				$enable_link = get_parameter ("enable_link", 'enable_link');
				$show_on_top = get_parameter ("show_on_top", 0);
				
				// This var switch between creation of items, item_per_agent = 0 => item per module; item_per_agent <> 0  => item per agent
				$item_per_agent = get_parameter ("item_per_agent", 0);
				$id_server = (int)get_parameter('servers', 0);
				
				$kind_relationship = (int)get_parameter('kind_relationship',
					VISUAL_MAP_WIZARD_PARENTS_NONE);
				$item_in_the_map = (int)get_parameter('item_in_the_map', 0);
				
				$message = '';
				
				if (($width == 0) && ($height == 0) && ($type == MODULE_GRAPH)) {
					$width = 400;
					$height = 180;
				}
				
				// One item per agent
				if ($item_per_agent == 1) {
					$id_agents_result = array();
					foreach ($id_agents as $id_agent_key => $id_agent_id) {
						if (defined("METACONSOLE")) {
							$row = db_get_row_filter(
								'tmetaconsole_agent',
								array('id_tagente' => $id_agent_id));
							$id_server = $row['id_tmetaconsole_setup'];
							$id_agent_id = $row['id_tagente'];
							
							$id_agents_result[] = array(
								'id_agent' => $id_agent_id,
								'id_server' => $id_server);
						}
						else {
							$id_agents_result[] = $id_agent_id;
						}
					}
					
					$message .= visual_map_process_wizard_add_agents(
						$id_agents_result,
						$image,
						$idVisualConsole,
						$range,
						$width,
						$height,
						$period,
						$process_value,
						$percentileitem_width,
						$max_value,
						$type_percentile,
						$value_show,
						$label_type,
						$type,
						$enable_link,
						$id_server,
						$kind_relationship,
						$item_in_the_map,$fontf,$fonts);
						
					$statusProcessInDB = array('flag' => true,
						'message' => $message);
					
				}
				else {
					
					
					// One item per module
					if (empty($name_modules)) {
						$statusProcessInDB = array('flag' => true,
							'message' => ui_print_error_message (
								__('No modules selected'), '', true));
					}
					else {
						
						
						if (defined("METACONSOLE")) {
							$agents_ids = array();
							foreach ($id_agents as $id_agent_id) {
								$server_and_agent = explode("|",$id_agent_id);
								
								$agents_ids[] = $server_and_agent[1];
							}
							$rows = db_get_all_rows_filter(
								'tmetaconsole_agent',
								array('id_tagente' => $agents_ids));
							
							$agents = array();
							foreach ($rows as $row) {
								$agents[$row['id_tmetaconsole_setup']][] =
									$row['id_tagente'];
							}
						}
						else {
							$agents[0] = $id_agents;
						}
					}
					
					
					
					foreach ($agents as $id_server => $id_agents) {
						
						
						
						//Any module
						if ($name_modules[0] == '0') {
													
							$message .= visual_map_process_wizard_add_agents(
								$id_agents,
								$image,
								$idVisualConsole,
								$range,
								$width,
								$height,
								$period,
								$process_value,
								$percentileitem_width,
								$max_value,
								$type_percentile,
								$value_show,
								'agent',
								$type,
								$enable_link,
								$id_server,
								$kind_relationship,
								$item_in_the_map,$fontf,$fonts);
							
							
						}
						else {
							$id_modules = array();
							
							if ($id_server != 0) {
								foreach ($name_modules as $serial_data) {
									$modules_serial = explode(';', $serial_data);
									
									foreach ($modules_serial as $data_serialized) {
										$data = explode('|', $data_serialized);
										$id_modules[] = $data[0];
									}
								}
							}
							else {
								foreach ($name_modules as $mod) {

									foreach ($id_agents as $ag) {
										$id_module = agents_get_modules($ag,
											array('id_agente_modulo'),
											array('nombre' => $mod));
										
										
										
										if (empty($id_module))
											continue;
										else {
											$id_module = reset($id_module);
											$id_module = $id_module['id_agente_modulo'];
										}
										
										$id_modules[] = $id_module;
									}
								}
							}
							
							$message .= visual_map_process_wizard_add_modules(
								$id_modules,
								$image,
								$idVisualConsole,
								$range,
								$width,
								$height,
								$period,
								$process_value,
								$percentileitem_width,
								$max_value,
								$type_percentile,
								$value_show,
								$label_type,
								$type,
								$enable_link,
								$id_server,
								$kind_relationship,
								$item_in_the_map,$fontf,$fonts);
						}
						
						
					}
					
					$statusProcessInDB = array(
						'flag' => true, 'message' => $message);
				}
				$action = 'edit';
				break;
		}
		break;
	case 'wizard_services':
		$visualConsoleName = $visualConsole['name'];
		switch ($action) {
			case 'update':
				enterprise_include_once("/include/functions_visual_map.php");
				
				$icon = (string) get_parameter('icon');
				$id_services = (array) get_parameter('services_selected');
				
				$result = enterprise_hook('enterprise_visual_map_process_services_wizard_add', array($id_services, $idVisualConsole, $icon));
				if ($result != ENTERPRISE_NOT_HOOK) {
					$statusProcessInDB = array('flag' => $result['status'], 'message' => $result['message']);
				}
				
				$action = 'edit';
				break;
		}
		break;
	case 'editor':
		switch ($action) {
			case 'new':
			case 'update':
			case 'edit':
				$visualConsoleName = $visualConsole['name'];
				$action = 'edit';
				break;
		}
		break;
}

if (isset($config['vc_refr']) and $config['vc_refr'] != 0)
	$view_refresh = $config['vc_refr'];
else
	$view_refresh = '300';

if (!defined('METACONSOLE')) {
	$url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';
	$url_view = 'index.php?sec=network&sec2=operation/visual_console/render_view&id=' . $idVisualConsole . '&refr=' . $view_refresh;
}
else {
	$url_base = 'index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&action2=';
	$url_view = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id_visualmap=' . $idVisualConsole . '&refr=' . $view_refresh;
}

// Hash for auto-auth in public link
$hash = md5($config["dbpass"] . $idVisualConsole . $config["id_user"]);

$buttons = array();

$buttons['consoles_list'] = array('active' => false,
	'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder&refr=' . $refr . '">' .
		html_print_image ("images/visual_console.png", true, array ("title" => __('Visual consoles list'))) .'</a>');
$buttons['public_link'] = array('active' => false,
	'text' => '<a href="' . ui_get_full_url('operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$idVisualConsole.'&id_user='.$config["id_user"]) . '">'.
		html_print_image ("images/camera_mc.png", true, array ("title" => __('Show link to public Visual Console'))).'</a>');
$buttons['data'] = array('active' => false,
	'text' => '<a href="' . $url_base . $action . '&tab=data&id_visual_console=' . $idVisualConsole . '">' . 
		html_print_image ("images/op_reporting.png", true, array ("title" => __('Main data'))) .'</a>');
$buttons['list_elements'] = array('active' => false,
	'text' => '<a href="' . $url_base . $action . '&tab=list_elements&id_visual_console=' . $idVisualConsole . '">' .
		html_print_image ("images/list.png", true, array ("title" => __('List elements'))) .'</a>');

if (enterprise_installed()) {
	$buttons['wizard_services'] = array('active' => false,
		'text' => '<a href="' . $url_base . $action . '&tab=wizard_services&id_visual_console=' . $idVisualConsole . '">' .
			html_print_image ("images/wand_services.png", true, array ("title" => __('Services wizard'))) .'</a>');
}

$buttons['wizard'] = array('active' => false,
	'text' => '<a href="' . $url_base . $action . '&tab=wizard&id_visual_console=' . $idVisualConsole . '">' .
		html_print_image ("images/wand.png", true, array ("title" => __('Wizard'))) .'</a>');
$buttons['editor'] = array('active' => false,
	'text' => '<a href="' . $url_base . $action . '&tab=editor&id_visual_console=' . $idVisualConsole . '">' .
		html_print_image ("images/builder.png", true, array ("title" => __('Builder'))) .'</a>');
$buttons['view'] = array('active' => false,
	'text' => '<a href="' . $url_view . '">' .
		html_print_image ("images/operation.png", true, array ("title" => __('View'))) .'</a>');

if ($action == 'new' || $idVisualConsole === false) {
	$buttons = array('data' => $buttons['data']); //Show only the data tab
	// If it is a fail try, reset the values
	$action = 'new';
	$visualConsoleName = __("New visual console");
}

$buttons[$activeTab]['active'] = true;

if (!defined('METACONSOLE')) {
	ui_print_page_header($visualConsoleName,
		"images/visual_console.png", false,
		"visual_console_editor_" . $activeTab . "_tab", false,
		$buttons);
}

if ($statusProcessInDB !== null) {
	echo $statusProcessInDB['message'];
}

//The source code for PAINT THE PAGE
switch ($activeTab) {
	case 'wizard':
		require_once($config['homedir'] . '/godmode/reporting/visual_console_builder.wizard.php');
		break;
	case 'wizard_services':
		if (enterprise_installed()) {
			enterprise_include('/godmode/reporting/visual_console_builder.wizard_services.php');
		}
		break;
	case 'data':
		require_once($config['homedir'] . '/godmode/reporting/visual_console_builder.data.php');
		break;
	case 'list_elements':
		require_once($config['homedir'] . '/godmode/reporting/visual_console_builder.elements.php');
		break;
	case 'editor':
		require_once($config['homedir'] . '/godmode/reporting/visual_console_builder.editor.php');
		break;
}
?>
