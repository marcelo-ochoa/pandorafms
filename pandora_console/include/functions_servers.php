<?php
/**
 * Auxiliary functions to manage servers.
 *
 * @category   Library
 * @package    Pandora FMS
 * @subpackage Opensource
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

require_once __DIR__.'/constants.php';


/**
 * Get a server.
 *
 * @param integer $id_server Server id to get.
 * @param array   $filter    Extra filter.
 * @param array   $fields    Fields to get.
 *
 * @return Server with the given id. False if not available.
 */
function servers_get_server($id_server, $filter=false, $fields=false)
{
    if (empty($id_server)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_server'] = $id_server;

    return @db_get_row_filter('tserver', $filter, $fields);
}


/**
 * Get all the server availables.
 *
 * @return All the servers available.
 */
function servers_get_names()
{
    $all_servers = db_get_all_rows_sql(
        'SELECT DISTINCT(`name`) as name
        FROM tserver
        WHERE server_type <> 13'
    );

    if ($all_servers === false) {
        return [];
    }

    $servers = [];
    foreach ($all_servers as $server) {
        $servers[$server['name']] = $server['name'];
    }

    return $servers;
}


/**
 * This function forces a recon task to be queued by the server asap.
 *
 * @param integer $id_recon_task Id.
 *
 * @return void
 */
function servers_force_recon_task($id_recon_task)
{
    $values = [
        'utimestamp' => 0,
        'status'     => 1,
    ];
    db_process_sql_update('trecon_task', $values, ['id_rt' => $id_recon_task]);
}


/**
 * Retrieves total number of modules per server.
 *
 * @return array Modules per server (total).
 */
function servers_get_total_modules()
{
    $modules = [];

    $modules_from_monitors = db_get_all_rows_sql(
        'SELECT
          tserver.server_type,
          count(tagente_estado.id_agente_modulo) as modules_assigned
        FROM tserver, tagente_estado, tagente_modulo, tagente
        WHERE tagente.disabled=0
          AND tagente_modulo.id_agente = tagente.id_agente
          AND tagente_modulo.disabled = 0
          AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
          AND tagente_estado.running_by = tserver.id_server
        GROUP BY tserver.server_type;'
    );

    if ($modules_from_monitors !== false) {
        $modules = array_reduce(
            $modules_from_monitors,
            function ($carry, $item) {
                $carry[$item['server_type']] = $item['modules_assigned'];
                return $carry;
            }
        );
    }

    $modules[SERVER_TYPE_INVENTORY] = db_get_sql(
        'SELECT COUNT(tagent_module_inventory.id_agent_module_inventory)
        FROM tagente, tagent_module_inventory
        WHERE tagente.disabled=0
          AND tagent_module_inventory.id_agente = tagente.id_agente'
    );

    $modules[SERVER_TYPE_EXPORT] = db_get_sql(
        'SELECT COUNT(tagente_modulo.id_agente_modulo)
        FROM tagente, tagente_modulo
        WHERE tagente.disabled=0
          AND tagente_modulo.id_agente = tagente.id_agente
          AND tagente_modulo.id_export != 0'
    );

    return $modules;

}


/**
 * This function will get several metrics from the database
 * to get info about server performance.
 *
 * @return array with several data.
 */
function servers_get_performance()
{
    global $config;

    $data = [];
    $data['total_modules'] = 0;
    $data['total_remote_modules'] = 0;
    $data['total_local_modules'] = 0;
    $data['avg_interval_total_modules'] = [];
    $data['avg_interval_remote_modules'] = [];
    $data['avg_interval_local_modules'] = 0;
    $data['local_modules_rate'] = 0;
    $data['network_modules_rate'] = 0;

    if ($config['realtimestats'] == 1) {
        $counts = db_get_all_rows_sql(
            'SELECT tagente_modulo.id_modulo,
				COUNT(tagente_modulo.id_agente_modulo) modules
			FROM tagente_modulo, tagente_estado, tagente
			WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
				AND tagente.id_agente = tagente_estado.id_agente
				AND tagente_modulo.disabled = 0
				AND delete_pending = 0
				AND (utimestamp > 0
                    OR (id_tipo_modulo = 100
                        OR (id_tipo_modulo > 21
                        AND id_tipo_modulo < 23)
                    )
                )
				AND tagente.disabled = 0
			GROUP BY tagente_modulo.id_modulo'
        );

        if (empty($counts)) {
            $counts = [];
        }

        foreach ($counts as $c) {
            switch ($c['id_modulo']) {
                case MODULE_DATA:
                    $data['total_local_modules'] = $c['modules'];
                break;

                case MODULE_NETWORK:
                    $data['total_network_modules'] = $c['modules'];
                break;

                case MODULE_PLUGIN:
                    $data['total_plugin_modules'] = $c['modules'];
                break;

                case MODULE_PREDICTION:
                    $data['total_prediction_modules'] = $c['modules'];
                break;

                case MODULE_WMI:
                    $data['total_wmi_modules'] = $c['modules'];
                break;

                case MODULE_WEB:
                    $data['total_web_modules'] = $c['modules'];
                break;

                default:
                    // Not possible.
                break;
            }

            if ($c['id_modulo'] != MODULE_DATA) {
                $data['total_remote_modules'] += $c['modules'];
            }

            $data['total_modules'] += $c['modules'];
        }
    } else {
        $counts = db_get_all_rows_sql(
            '
			SELECT server_type, my_modules modules
			FROM tserver
			GROUP BY server_type'
        );

        if (empty($counts)) {
            $counts = [];
        }

        foreach ($counts as $c) {
            switch ($c['server_type']) {
                case SERVER_TYPE_DATA:
                    $data['total_local_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_NETWORK:
                case SERVER_TYPE_SNMP:
                case SERVER_TYPE_ENTERPRISE_ICMP:
                case SERVER_TYPE_ENTERPRISE_SNMP:
                    $data['total_network_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_PLUGIN:
                    $data['total_plugin_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_PREDICTION:
                    $data['total_prediction_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_WMI:
                    $data['total_wmi_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_WEB:
                    $data['total_web_modules'] = $c['modules'];
                break;

                case SERVER_TYPE_EXPORT:
                case SERVER_TYPE_INVENTORY:
                case SERVER_TYPE_EVENT:
                case SERVER_TYPE_DISCOVERY:
                case SERVER_TYPE_SYSLOG:
                default:
                    // Nothing.
                break;
            }

            if ($c['server_type'] != SERVER_TYPE_DATA) {
                $data['total_remote_modules'] += $c['modules'];
            }

            $data['total_modules'] += $c['modules'];
        }
    }

    $interval_avgs = [];

    // Avg of modules interval when modules have module_interval > 0.
    $interval_avgs_modules = db_get_all_rows_sql(
        'SELECT count(tagente_modulo.id_modulo) modules ,
			tagente_modulo.id_modulo,
			AVG(tagente_modulo.module_interval) avg_interval
		FROM tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			AND tagente_modulo.disabled = 0
			AND module_interval > 0
			AND (utimestamp > 0 OR (
                    id_tipo_modulo = 100
                    OR (id_tipo_modulo > 21
                        AND id_tipo_modulo < 23
                    )
                )
            )
			AND delete_pending = 0
			AND tagente.disabled = 0
			AND tagente.id_agente = tagente_estado.id_agente
		GROUP BY tagente_modulo.id_modulo'
    );

    if (empty($interval_avgs_modules)) {
        $interval_avgs_modules = [];
    }

    // Transform into a easily format.
    foreach ($interval_avgs_modules as $iamodules) {
        $interval_avgs[$iamodules['id_modulo']]['avg_interval'] = $iamodules['avg_interval'];
        $interval_avgs[$iamodules['id_modulo']]['modules'] = $iamodules['modules'];
    }

    // Avg of agents interval when modules have module_interval == 0.
    $interval_avgs_agents = db_get_all_rows_sql(
        'SELECT count(tagente_modulo.id_modulo) modules ,
			tagente_modulo.id_modulo, AVG(tagente.intervalo) avg_interval
		FROM tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			AND tagente_modulo.disabled = 0
			AND module_interval = 0
			AND (utimestamp > 0 OR (id_tipo_modulo = 100 OR (id_tipo_modulo >= 21 AND id_tipo_modulo <= 23)))
			AND delete_pending = 0
			AND tagente.disabled = 0
			AND tagente.id_agente = tagente_estado.id_agente
		GROUP BY tagente_modulo.id_modulo'
    );

    if (empty($interval_avgs_agents)) {
        $interval_avgs_agents = [];
    }

    // Merge with the previous calculated array.
    foreach ($interval_avgs_agents as $iaagents) {
        if (!isset($interval_avgs[$iaagents['id_modulo']]['modules'])) {
            $interval_avgs[$iaagents['id_modulo']]['avg_interval'] = $iaagents['avg_interval'];
            $interval_avgs[$iaagents['id_modulo']]['modules'] = $iaagents['modules'];
        } else {
            $interval_avgs[$iaagents['id_modulo']]['avg_interval'] = servers_get_avg_interval(
                $interval_avgs[$iaagents['id_modulo']],
                $iaagents
            );
            $interval_avgs[$iaagents['id_modulo']]['modules'] += $iaagents['modules'];
        }
    }

    $info_servers = array_reduce(
        servers_get_info(),
        function ($carry, $item) {
            $carry[$item['server_type']] = $item;
            return $carry;
        }
    );
    foreach ($interval_avgs as $id_modulo => $ia) {
        $module_lag = 0;
        switch ($id_modulo) {
            case MODULE_DATA:
                $module_lag = $info_servers[SERVER_TYPE_DATA]['module_lag'];
                $data['avg_interval_local_modules'] = $ia['avg_interval'];
                $data['local_modules_rate'] = servers_get_rate(
                    $data['avg_interval_local_modules'],
                    ($data['total_local_modules'] - $module_lag)
                );
            break;

            case MODULE_NETWORK:
                $module_lag = $info_servers[SERVER_TYPE_NETWORK]['module_lag'];
                $module_lag += $info_servers[SERVER_TYPE_SNMP]['module_lag'];
                $module_lag += $info_servers[SERVER_TYPE_ENTERPRISE_ICMP]['module_lag'];
                $module_lag += $info_servers[SERVER_TYPE_ENTERPRISE_SNMP]['module_lag'];
                $data['avg_interval_network_modules'] = $ia['avg_interval'];
                $data['network_modules_rate'] = servers_get_rate(
                    $data['avg_interval_network_modules'],
                    ($data['total_network_modules'] - $module_lag)
                );
            break;

            case MODULE_PLUGIN:
                $module_lag = $info_servers[SERVER_TYPE_PLUGIN]['module_lag'];
                $data['avg_interval_plugin_modules'] = $ia['avg_interval'];
                $data['plugin_modules_rate'] = servers_get_rate(
                    $data['avg_interval_plugin_modules'],
                    ($data['total_plugin_modules'] - $module_lag)
                );
            break;

            case MODULE_PREDICTION:
                $module_lag = $info_servers[SERVER_TYPE_PREDICTION]['module_lag'];
                $data['avg_interval_prediction_modules'] = $ia['avg_interval'];
                $data['prediction_modules_rate'] = servers_get_rate(
                    $data['avg_interval_prediction_modules'],
                    ($data['total_prediction_modules'] - $module_lag)
                );
            break;

            case MODULE_WMI:
                $module_lag = $info_servers[SERVER_TYPE_WMI]['module_lag'];
                $data['avg_interval_wmi_modules'] = $ia['avg_interval'];
                $data['wmi_modules_rate'] = servers_get_rate(
                    $data['avg_interval_wmi_modules'],
                    ($data['total_wmi_modules'] - $module_lag)
                );
            break;

            case MODULE_WEB:
                $module_lag = $info_servers[SERVER_TYPE_WEB]['module_lag'];
                $data['avg_interval_web_modules'] = $ia['avg_interval'];
                $data['web_modules_rate'] = servers_get_rate(
                    $data['avg_interval_web_modules'],
                    ($data['total_web_modules'] - $module_lag)
                );
            break;

            default:
                // Not possible.
            break;
        }

        if ($id_modulo != MODULE_DATA) {
            $data['avg_interval_remote_modules'][] = $ia['avg_interval'];
        }

        $data['avg_interval_total_modules'][] = $ia['avg_interval'];
    }

    if (empty($data['avg_interval_remote_modules'])) {
        $data['avg_interval_remote_modules'] = 0;
    } else {
        $data['avg_interval_remote_modules'] = (array_sum($data['avg_interval_remote_modules']) / count($data['avg_interval_remote_modules']));
    }

    if (empty($data['avg_interval_total_modules'])) {
        $data['avg_interval_total_modules'] = 0;
    } else {
        $data['avg_interval_total_modules'] = (array_sum($data['avg_interval_total_modules']) / count($data['avg_interval_total_modules']));
    }

    $total_modules_lag = 0;
    foreach ($info_servers as $key => $value) {
        switch ($key) {
            case SERVER_TYPE_DATA:
            case SERVER_TYPE_NETWORK:
            case SERVER_TYPE_SNMP:
            case SERVER_TYPE_ENTERPRISE_ICMP:
            case SERVER_TYPE_ENTERPRISE_SNMP:
            case SERVER_TYPE_PLUGIN:
            case SERVER_TYPE_PREDICTION:
            case SERVER_TYPE_WMI:
            case SERVER_TYPE_WEB:
                $total_modules_lag += $value['module_lag'];
            break;

            default:
                // Not possible.
            break;
        }
    }

    $data['remote_modules_rate'] = servers_get_rate(
        $data['avg_interval_remote_modules'],
        $data['total_remote_modules']
    );

    $data['total_modules_rate'] = servers_get_rate(
        $data['avg_interval_total_modules'],
        ($data['total_modules'] - $total_modules_lag)
    );

    return ($data);
}


/**
 * Get avg interval.
 *
 * @param array $modules_avg_interval1 Array with avg and count
 * data of first part.
 * @param array $modules_avg_interval2 Array with avg and count
 * data of second part.
 *
 * @return float number of avg modules between two parts.
 */
function servers_get_avg_interval(
    $modules_avg_interval1,
    $modules_avg_interval2
) {
    $total_modules = ($modules_avg_interval1['modules'] + $modules_avg_interval2['modules']);

    $parcial1 = ($modules_avg_interval1['avg_interval'] * $modules_avg_interval1['modules']);
    $parcial2 = ($modules_avg_interval2['avg_interval'] * $modules_avg_interval2['modules']);

    return (($parcial1 + $parcial2) / $total_modules);
}


/**
 * Get server rate
 *
 * @param float   $avg_interval Avg of interval of these modules.
 * @param integer $num_modules  Number of modules.
 *
 * @return float number of modules processed by second
 */
function servers_get_rate($avg_interval, $num_modules)
{
    return ($avg_interval > 0) ? ($num_modules / $avg_interval) : 0;
}


/**
 * This function will get all the server information in an array
 * or a specific server.
 *
 * @param integer $id_server An optional integer or array of integers
 * to select specific servers.
 *
 * @return mixed False in case the server doesn't exist or an array with info.
 */
function servers_get_info($id_server=-1)
{
    global $config;

    if (is_array($id_server)) {
        $select_id = ' WHERE id_server IN ('.implode(',', $id_server).')';
    } else if ($id_server > 0) {
        $select_id = ' WHERE id_server IN ('.(int) $id_server.')';
    } else {
        $select_id = '';
    }

    $sql = '
		SELECT *
		FROM tserver '.$select_id.'
		ORDER BY server_type';
    $result = db_get_all_rows_sql($sql);
    $time = get_system_time();

    if (empty($result)) {
        return false;
    }

    $return = [];
    foreach ($result as $server) {
        switch ($server['server_type']) {
            case SERVER_TYPE_DATA:
                $server['img'] = html_print_image(
                    'images/data.png',
                    true,
                    ['title' => __('Data server')]
                );
                $server['type'] = 'data';
                $id_modulo = 1;
            break;

            case SERVER_TYPE_NETWORK:
                $server['img'] = html_print_image(
                    'images/network.png',
                    true,
                    ['title' => __('Network server')]
                );
                $server['type'] = 'network';
                $id_modulo = 2;
            break;

            case SERVER_TYPE_SNMP:
                $server['img'] = html_print_image(
                    'images/snmp.png',
                    true,
                    ['title' => __('SNMP Trap server')]
                );
                $server['type'] = 'snmp';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_DISCOVERY:
                $server['img'] = html_print_image(
                    'images/recon.png',
                    true,
                    ['title' => __('Discovery server')]
                );
                $server['type'] = 'recon';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_PLUGIN:
                $server['img'] = html_print_image(
                    'images/plugin.png',
                    true,
                    ['title' => __('Plugin server')]
                );
                $server['type'] = 'plugin';
                $id_modulo = 4;
            break;

            case SERVER_TYPE_PREDICTION:
                $server['img'] = html_print_image(
                    'images/chart_bar.png',
                    true,
                    ['title' => __('Prediction server')]
                );
                $server['type'] = 'prediction';
                $id_modulo = 5;
            break;

            case SERVER_TYPE_WMI:
                $server['img'] = html_print_image(
                    'images/wmi.png',
                    true,
                    ['title' => __('WMI server')]
                );
                $server['type'] = 'wmi';
                $id_modulo = 6;
            break;

            case SERVER_TYPE_EXPORT:
                $server['img'] = html_print_image(
                    'images/server_export.png',
                    true,
                    ['title' => __('Export server')]
                );
                $server['type'] = 'export';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_INVENTORY:
                $server['img'] = html_print_image(
                    'images/page_white_text.png',
                    true,
                    ['title' => __('Inventory server')]
                );
                $server['type'] = 'inventory';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_WEB:
                $server['img'] = html_print_image(
                    'images/world.png',
                    true,
                    ['title' => __('Web server')]
                );
                $server['type'] = 'web';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_EVENT:
                $server['img'] = html_print_image(
                    'images/lightning_go.png',
                    true,
                    ['title' => __('Event server')]
                );
                $server['type'] = 'event';
                $id_modulo = 2;
            break;

            case SERVER_TYPE_ENTERPRISE_ICMP:
                $server['img'] = html_print_image(
                    'images/network.png',
                    true,
                    ['title' => __('Enterprise ICMP server')]
                );
                $server['type'] = 'enterprise icmp';
                $id_modulo = 2;
            break;

            case SERVER_TYPE_ENTERPRISE_SNMP:
                $server['img'] = html_print_image(
                    'images/network.png',
                    true,
                    ['title' => __('Enterprise SNMP server')]
                );
                $server['type'] = 'enterprise snmp';
                $id_modulo = 2;
            break;

            case SERVER_TYPE_ENTERPRISE_SATELLITE:
                $server['img'] = html_print_image(
                    'images/satellite.png',
                    true,
                    ['title' => __('Enterprise Satellite server')]
                );
                $server['type'] = 'enterprise satellite';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_ENTERPRISE_TRANSACTIONAL:
                $server['img'] = html_print_image(
                    'images/transactional_map.png',
                    true,
                    ['title' => __('Enterprise Transactional server')]
                );
                $server['type'] = 'enterprise transactional';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_MAINFRAME:
                $server['img'] = html_print_image(
                    'images/mainframe.png',
                    true,
                    ['title' => __('Mainframe server')]
                );
                $server['type'] = 'mainframe';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_SYNC:
                $server['img'] = html_print_image(
                    'images/sync.png',
                    true,
                    ['title' => __('Sync server')]
                );
                $server['type'] = 'sync';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_WUX:
                $server['img'] = html_print_image(
                    'images/icono-wux.png',
                    true,
                    ['title' => __('Wux server')]
                );
                $server['type'] = 'wux';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_SYSLOG:
                $server['img'] = html_print_image(
                    'images/syslog.png',
                    true,
                    ['title' => __('Log server')]
                );
                $server['type'] = 'syslog';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_AUTOPROVISION:
                $server['img'] = html_print_image(
                    'images/autoprovision.png',
                    true,
                    ['title' => __('Autoprovision server')]
                );
                $server['type'] = 'autoprovision';
                $id_modulo = 0;
            break;

            case SERVER_TYPE_MIGRATION:
                $server['img'] = html_print_image(
                    'images/migration.png',
                    true,
                    ['title' => __('Migration server')]
                );
                $server['type'] = 'migration';
                $id_modulo = 0;
            break;

            default:
                $server['img'] = '';
                $server['type'] = 'unknown';
                $id_modulo = 0;
            break;
        }

        if ($config['realtimestats'] == 0) {
            // Take data from database if not realtime stats.
            $server['lag'] = db_get_sql(
                'SELECT lag_time
                FROM tserver
                WHERE id_server = '.$server['id_server']
            );
            $server['module_lag'] = db_get_sql(
                'SELECT lag_modules
                FROM tserver
                WHERE id_server = '.$server['id_server']
            );
            $server['modules'] = db_get_sql(
                'SELECT my_modules
                FROM tserver
                WHERE id_server = '.$server['id_server']
            );
            $server['modules_total'] = db_get_sql(
                'SELECT total_modules_running
                FROM tserver
                WHERE id_server = '.$server['id_server']
            );
        } else {
            // Take data in realtime.
            $server['module_lag'] = 0;
            $server['lag'] = 0;

            // Inventory server.
            if ($server['server_type'] == SERVER_TYPE_INVENTORY) {
                // Get modules exported by this server.
                $server['modules'] = db_get_sql(
                    "SELECT COUNT(tagent_module_inventory.id_agent_module_inventory)
                    FROM tagente, tagent_module_inventory
                    WHERE tagente.disabled=0
                        AND tagent_module_inventory.id_agente = tagente.id_agente
                        AND tagente.server_name = '".$server['name']."'"
                );

                // Get total exported modules.
                $server['modules_total'] = db_get_sql(
                    'SELECT COUNT(tagent_module_inventory.id_agent_module_inventory)
                    FROM tagente, tagent_module_inventory
                    WHERE tagente.disabled=0
                    AND tagent_module_inventory.id_agente = tagente.id_agente'
                );

                $interval_esc = db_escape_key_identifier('interval');

                // Get the module lag.
                $server['module_lag'] = db_get_sql(
                    'SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) AS module_lag
					FROM tagente, tagent_module_inventory
					WHERE utimestamp > 0
					AND tagent_module_inventory.id_agente = tagente.id_agente
					AND tagent_module_inventory.'.$interval_esc." > 0
					AND tagente.server_name = '".$server['name']."'
					AND (UNIX_TIMESTAMP() - utimestamp) < (tagent_module_inventory.".$interval_esc.' * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > tagent_module_inventory.'.$interval_esc
                );

                // Get the lag.
                $server['lag'] = db_get_sql(
                    'SELECT AVG(UNIX_TIMESTAMP() - utimestamp - tagent_module_inventory.'.$interval_esc.')
					FROM tagente, tagent_module_inventory
					WHERE utimestamp > 0
					AND tagent_module_inventory.id_agente = tagente.id_agente
					AND tagent_module_inventory.'.$interval_esc." > 0
					AND tagente.server_name = '".$server['name']."'
					AND (UNIX_TIMESTAMP() - utimestamp) < (tagent_module_inventory.".$interval_esc.' * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > tagent_module_inventory.'.$interval_esc
                );
                // Export server.
            } else if ($server['server_type'] == SERVER_TYPE_EXPORT) {
                // Get modules exported by this server.
                $server['modules'] = db_get_sql(
                    'SELECT COUNT(tagente_modulo.id_agente_modulo)
                    FROM tagente, tagente_modulo, tserver_export
                    WHERE tagente.disabled=0
                        AND tagente_modulo.id_agente = tagente.id_agente
                        AND tagente_modulo.id_export = tserver_export.id
                        AND tserver_export.id_export_server = '.$server['id_server']
                );

                // Get total exported modules.
                $server['modules_total'] = db_get_sql(
                    'SELECT COUNT(tagente_modulo.id_agente_modulo)
                    FROM tagente, tagente_modulo
                    WHERE tagente.disabled=0
                        AND tagente_modulo.id_agente = tagente.id_agente
                        AND tagente_modulo.id_export != 0'
                );

                $server['lag'] = 0;
                $server['module_lag'] = 0;
            } else if ($server['server_type'] == SERVER_TYPE_DISCOVERY) {
                // Discovery server.
                $server['name'] = '<a href="index.php?sec=estado&amp;sec2=operation/servers/recon_view&amp;server_id='.$server['id_server'].'">'.$server['name'].'</a>';

                // Total jobs running on this Discovery server.
                $server['modules'] = db_get_sql(
                    'SELECT COUNT(id_rt)
					FROM trecon_task
					WHERE id_recon_server = '.$server['id_server']
                );

                // Total recon jobs (all servers).
                $server['modules_total'] = db_get_sql(
                    'SELECT COUNT(status) FROM trecon_task'
                );

                // Lag (take average active time of all active tasks).
                $server['module_lag'] = 0;
                $server['lag'] = db_get_sql(
                    'SELECT UNIX_TIMESTAMP() - utimestamp
                    FROM trecon_task
                    WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep)
                    AND id_recon_server = '.$server['id_server']
                );
                $server['module_lag'] = db_get_sql(
                    'SELECT COUNT(id_rt)
                    FROM trecon_task
                    WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep)
                    AND id_recon_server = '.$server['id_server']
                );
            } else {
                // Data, Plugin, WMI, Network and Others.
                $server['modules'] = db_get_sql(
                    'SELECT count(tagente_estado.id_agente_modulo)
                    FROM tagente_estado, tagente_modulo, tagente
                    WHERE tagente.disabled=0
                        AND tagente_modulo.id_agente = tagente.id_agente
                        AND tagente_modulo.disabled = 0
                        AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
                        AND tagente_estado.running_by = '.$server['id_server']
                );

                $server['modules_total'] = db_get_sql(
                    'SELECT count(tagente_estado.id_agente_modulo)
                    FROM tserver, tagente_estado, tagente_modulo, tagente
                    WHERE tagente.disabled=0
                    AND tagente_modulo.id_agente = tagente.id_agente
                    AND tagente_modulo.disabled = 0
                    AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
                    AND tagente_estado.running_by = tserver.id_server
                    AND tserver.server_type = '.$server['server_type']
                );

                // Remote servers LAG Calculation (server_type != 0).
                if ($server['server_type'] != 0) {
                    // MySQL 8.0 has function lag(). So, lag must be enclosed in quotations.
                    $result = db_get_row_sql(
                        'SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag,
                            AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS "lag"
                        FROM tagente_estado, tagente_modulo, tagente
                        WHERE utimestamp > 0
                            AND tagente.disabled = 0
                            AND tagente.id_agente = tagente_estado.id_agente
                            AND tagente_modulo.disabled = 0
                            AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
                            AND current_interval > 0
                            AND running_by = '.$server['id_server'].'
                            AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
                            AND (UNIX_TIMESTAMP() - utimestamp) > current_interval'
                    );
                } else {
                    // Local/Dataserver server LAG calculation.
                    // MySQL 8.0 has function lag(). So, lag must be enclosed in quotations.
                    $result = db_get_row_sql(
                        'SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag,
                            AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS "lag"
                        FROM tagente_estado, tagente_modulo, tagente
                        WHERE utimestamp > 0
                            AND tagente.disabled = 0
                            AND tagente.id_agente = tagente_estado.id_agente
                            AND tagente_modulo.disabled = 0
                            AND tagente_modulo.id_tipo_modulo < 5
                            AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
                            AND current_interval > 0
                            AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
                            AND running_by = '.$server['id_server'].'
                            AND (UNIX_TIMESTAMP() - utimestamp) > (current_interval * 1.1)'
                    );
                }

                // Lag over current_interval * 2 is not lag,
                // it's a timed out module.
                if (!empty($result['lag'])) {
                    $server['lag'] = $result['lag'];
                }

                if (!empty($result['module_lag'])) {
                    $server['module_lag'] = $result['module_lag'];
                }
            }
        }

        if (isset($server['module_lag'])) {
            $server['lag_txt'] = (($server['lag'] == 0) ? '-' : human_time_description_raw($server['lag'])).' / '.$server['module_lag'];
        } else {
            $server['lag_txt'] = '';
        }

        if ($server['modules_total'] > 0) {
            $server['load'] = round(
                ($server['modules'] / $server['modules_total'] * 100)
            );
        } else {
            $server['load'] = 0;
        }

        // Push the raw data on the return stack.
        $return[$server['id_server']] = $server;
    }

    return $return;
}


/**
 * Get server type
 *
 * @param integer $type Type.
 *
 * @return array Result.
 */
function servers_get_servers_type($type)
{
    return db_get_all_rows_filter('tserver', ['server_type' => $type]);
}


/**
 * Get the server name.
 *
 * @param integer $id_server Server id.
 *
 * @return string Name of the given server
 */
function servers_get_name($id_server)
{
    return (string) db_get_value(
        'name',
        'tserver',
        'id_server',
        (int) $id_server
    );
}


/**
 * Get the presence of .conf and .md5 into remote_config dir.
 *
 * @param string $server_name Agent name.
 *
 * @return true If files exist and are writable.
 */
function servers_check_remote_config($server_name)
{
    global $config;

    $server_md5 = md5($server_name, false);

    $filenames = [];
    $filenames['md5'] = io_safe_output(
        $config['remote_config']
    ).'/md5/'.$server_md5.'.srv.md5';
    $filenames['conf'] = io_safe_output(
        $config['remote_config']
    ).'/conf/'.$server_md5.'.srv.conf';

    if (! isset($filenames['conf'])) {
        return false;
    }

    if (! isset($filenames['md5'])) {
        return false;
    }

    return (file_exists($filenames['conf'])
        && is_writable($filenames['conf'])
        && file_exists($filenames['md5'])
        && is_writable($filenames['md5']));
}


/**
 * Return a string containing image tag for a given target id (server).
 * TODO: Make this print_servertype_icon and move to functions_ui.php.
 *      Make XHTML compatible. Make string translatable.
 *
 * @param integer $id Server type id.
 *
 * @deprecated Use print_servertype_icon instead.
 *
 * @return string Fully formatted IMG HTML tag with icon.
 */
function servers_show_type($id)
{
    global $config;

    switch ($id) {
        case 1:
            $return = html_print_image(
                'images/database.png',
                true,
                ['title' => get_product_name().' Data server']
            );
        break;

        case 2:
            $return = html_print_image(
                'images/network.png',
                true,
                ['title' => get_product_name().' Network server']
            );
        break;

        case 4:
            $return = html_print_image(
                'images/plugin.png',
                true,
                ['title' => get_product_name().' Plugin server']
            );
        break;

        case 5:
            $return = html_print_image(
                'images/chart_bar.png',
                true,
                ['title' => get_product_name().' Prediction server']
            );
        break;

        case 6:
            $return = html_print_image(
                'images/wmi.png',
                true,
                ['title' => get_product_name().' WMI server']
            );
        break;

        case 7:
            $return = html_print_image(
                'images/server_web.png',
                true,
                ['title' => get_product_name().' WEB server']
            );
        break;

        case 8:
            $return = html_print_image(
                'images/module-wux.png',
                true,
                ['title' => get_product_name().' WUX server']
            );
        break;

        default:
            $return = '--';
        break;
    }

    return $return;
}


/**
 * Get the numbers of servers up.
 *
 * This check assumes that server_keepalive should be at least 15 minutes.
 *
 * @return integer The number of servers alive.
 */
function servers_check_status()
{
    global $config;

    $sql = 'SELECT COUNT(id_server)
        FROM tserver
        WHERE status = 1
            AND keepalive > NOW() - INTERVAL server_keepalive*2 SECOND';

    $status = (int) db_get_sql($sql);
    // Cast as int will assure a number value
    // This function should just ack of server down, not set it down.
    return $status;
}


/**
 * Get statistical information for a given server
 *
 * @param integer $id_server Server id to get status.
 *
 * @return array Server info array
 */
function servers_get_status($id_server)
{
    $serverinfo = servers_get_info($id_server);
    return $serverinfo[$id_server];
}


/**
 * Return server name based on identifier.
 *
 * @param integer $server Server identifier.
 *
 * @return string Server name
 */
function servers_get_server_string_name(int $server)
{
    switch ($server) {
        case SERVER_TYPE_DATA:
        return __('Data server');

        case SERVER_TYPE_NETWORK:
        return __('Network server');

        case SERVER_TYPE_SNMP:
        return __('SNMP server');

        case SERVER_TYPE_ENTERPRISE_ICMP:
        return __('Enterprise ICMP server');

        case SERVER_TYPE_ENTERPRISE_SNMP:
        return __('Enterprise SNMP server');

        case SERVER_TYPE_PLUGIN:
        return __('Plugin server');

        case SERVER_TYPE_PREDICTION:
        return __('Prediction Server');

        case SERVER_TYPE_WMI:
        return __('WMI server');

        case SERVER_TYPE_WEB:
        return __('Web server');

        case SERVER_TYPE_EXPORT:
        return __('Export server');

        case SERVER_TYPE_INVENTORY:
        return __('Inventory server');

        case SERVER_TYPE_EVENT:
        return __('Event server');

        case SERVER_TYPE_DISCOVERY:
        return __('Discovery server');

        case SERVER_TYPE_SYSLOG:
        return __('Log server');

        case SERVER_TYPE_WUX:
        return __('WUX server');

        case SERVER_TYPE_ENTERPRISE_SATELLITE:
        return __('Satellite');

        default:
        return __('N/A');
    }
}
