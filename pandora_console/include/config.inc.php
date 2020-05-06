<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Config
 */

// Default values
// $config["dbname"]="pandora";
// $config["dbuser"]="pandora";
// $config["dbpass"]="pandora";
// $config["dbhost"]="localhost";
// This is used for reporting, please add "/" character at the end
// $config["homedir"]="/var/www/pandora_console/";
// $config["homeurl"]="/pandora_console/";
// $config["auth"]["scheme"] = "mysql";

/**
 * Do not display any ERROR
 */
error_reporting(E_ALL);

// Display ALL errors
// error_reporting(E_ERROR);
$ownDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
require $ownDir.'config_process.php';
