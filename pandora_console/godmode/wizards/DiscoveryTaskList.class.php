<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
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

global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_reports.php';
require_once $config['homedir'].'/include/functions_cron.php';
enterprise_include_once('include/functions_tasklist.php');
enterprise_include_once('include/functions_cron.php');

ui_require_css_file('task_list');
ui_require_css_file('simTree');
ui_require_javascript_file('simTree');

/**
 * Defined as wizard to guide user to explore running tasks.
 */
class DiscoveryTaskList extends HTML
{


    /**
     * Constructor.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Custom default mesage.
     * @param string  $icon  Custom icon.
     * @param string  $label Custom label.
     *
     * @return class HostDevices
     */
    public function __construct(
        int $page=0,
        string $msg='Default message. Not set.',
        string $icon='images/wizard/tasklist.png',
        string $label='Task list'
    ) {
        $this->setBreadcrum([]);

        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = __($label);
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist'
        );

        return $this;
    }


    /**
     * Implements run method.
     *
     * @param string  $message Redirected input.
     * @param boolean $status  Redirected input.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run($message='', $status=null)
    {
        global $config;
        // Load styles.
        parent::run();

        $this->prepareBreadcrum(
            [
                [
                    'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                    'label' => 'Discovery',
                ],
            ]
        );

        // Header.
        ui_print_page_header(
            __('Task list'),
            '',
            false,
            '',
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

        // Show redirected messages from discovery.php.
        if ($status === 0) {
            ui_print_success_message($message);
        } else if ($status !== null) {
            ui_print_error_message($message);
        }

        $force_run = (bool) get_parameter('force_run');
        $force = (bool) get_parameter('force');
        if ($force_run === true || $force === true) {
            return $this->forceTask();
        }

        $delete_console_task = (bool) get_parameter('delete_console_task');
        if ($delete_console_task === true) {
            return $this->deleteConsoleTask();
        }

        $delete = (bool) get_parameter('delete', false);
        if ($delete === true) {
            return $this->deleteTask();
        }

        if (enterprise_installed()) {
            // This check only applies to enterprise users.
            enterprise_hook('tasklist_checkrunning');

            $ret = $this->showListConsoleTask();
        } else {
            $ret = false;
        }

        $ret2 = $this->showList();

        if ($ret === false && $ret2 === false) {
            include_once $config['homedir'].'/general/first_task/recon_view.php';
        } else {
            $form = [
                'form'   => [
                    'method' => 'POST',
                    'action' => ui_get_full_url(
                        'index.php?sec=gservers&sec2=godmode/servers/discovery'
                    ),
                ],
                'inputs' => [
                    [
                        'arguments' => [
                            'name'       => 'submit',
                            'label'      => __('Go back'),
                            'type'       => 'submit',
                            'attributes' => 'class="sub cancel"',
                            'return'     => true,
                        ],
                    ],[
                        'class'     => 'action-buttons rule-builder-actions',
                        'arguments' => [
                            'name'       => 'refresh',
                            'label'      => __('Refresh'),
                            'type'       => 'button',
                            'attributes' => 'class="sub upd"',
                            'return'     => true,
                            'script'     => 'location.reload();',
                        ],
                    ],
                ],
            ];

            $this->printForm($form);
        }

        return $ret;
    }


    /**
     * Implements load method.
     *
     * @return mixed Skeleton for button.
     */
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,

        ];

    }


    /**
     * Delete a recon task.
     *
     * @return void
     */
    public function deleteTask()
    {
        global $config;

        if (! check_acl($config['id_user'], 0, 'AW')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $task = get_parameter('task', null);

        if ($task !== null) {
            $result = db_process_sql_delete(
                'trecon_task',
                ['id_rt' => $task]
            );

            if ($result == 1) {
                return [
                    'result' => 0,
                    'msg'    => __('Task successfully deleted'),
                    'id'     => false,
                ];
            }

            // Trick to avoid double execution.
            header('Location: '.$this->url);
        }

    }


    /**
     * Force console task.
     *
     * @return void
     */
    public function forceTask()
    {
        global $config;

        if (!$this->aclMulticheck('RR|RW|RM|PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $id_console_task = (int) get_parameter('id_console_task');

        if ($id_console_task != null) {
            // --------------------------------
            // FORCE A CONSOLE TASK
            // --------------------------------
            enterprise_hook('cron_task_run', [$id_console_task, true]);
            // Trick to avoid double execution.
            header('Location: '.$this->url);
        } else {
            // --------------------------------
            // FORCE A RECON TASK
            // --------------------------------
            if (check_acl($config['id_user'], 0, 'AW')) {
                if (isset($_GET['force'])) {
                    $id = (int) get_parameter_get('force', 0);
                    // Schedule execution.
                    $review_mode = db_get_value(
                        'review_mode',
                        'trecon_task',
                        'id_rt',
                        $id
                    );

                    if ($review_mode != DISCOVERY_STANDARD) {
                        // Force re-scan for supervised tasks.
                        $review_mode = DISCOVERY_REVIEW;
                    }

                    db_process_sql_update(
                        'trecon_task',
                        [
                            'utimestamp'  => 0,
                            'status'      => 1,
                            'review_mode' => $review_mode,
                        ],
                        ['id_rt' => $id]
                    );
                    header('Location: '.$this->url);
                }
            }
        }
    }


    /**
     * Delete a Console task.
     *
     * @return void
     */
    public function deleteConsoleTask()
    {
        global $config;

        if (! check_acl($config['id_user'], 0, 'RM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $id_console_task = (int) get_parameter('id_console_task');

        if ($id_console_task !== null) {
            $result = db_process_sql_delete(
                'tuser_task_scheduled',
                ['id' => $id_console_task]
            );

            if ($result == 1) {
                return [
                    'result' => 0,
                    'msg'    => __('Console Task successfully deleted'),
                    'id'     => false,
                ];
            }

            // Trick to avoid double execution.
            header('Location: '.$this->url);
        }

    }


    /**
     * Show complete list of running tasks.
     *
     * @return boolean Success or not.
     */
    public function showList()
    {
        global $config;

        check_login();

        if (!$this->aclMulticheck('AR|AW|AM')) {
            // Tasklist are allowed only of agent managers.
            return '';
        }

        // Get all discovery servers.
        $servers = db_get_all_rows_sql('SELECT * FROM tserver WHERE server_type = 3');
        if ($servers === false) {
            $servers = [];
            ui_print_error_message(__('Discovery Server is disabled'));
            $check = db_get_all_rows_sql('SELECT * FROM trecon_task');
            return (bool) $check;
        } else {
            include_once $config['homedir'].'/include/functions_graph.php';
            include_once $config['homedir'].'/include/functions_servers.php';
            include_once $config['homedir'].'/include/functions_network_profiles.php';

            $recon_tasks = db_get_all_rows_sql('SELECT * FROM trecon_task');
            // Show network tasks for Recon Server.
            if ($recon_tasks === false) {
                $recon_tasks = [];
            }

            $url_ajax = $config['homeurl'].'ajax.php';

            $table = new StdClass();
            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->width = '100%';
            $table->class = 'info_table';
            $table->head = [];
            $table->data = [];
            $table->align = [];
            $table->headstyle = [];
            $table->style = [];
            $table->style[4] = 'word-break: break-word;';
            for ($i = 0; $i < 9; $i++) {
                $table->headstyle[$i] = 'text-align: left;';
            }

            // Name.
            $table->headstyle[4] .= 'min-width: 100px; width: 600px;';
            // Status.
            $table->headstyle[5] .= 'min-width: 50px; width: 100px;';
            // Task type.
            $table->headstyle[6] .= 'min-width: 150px; width: 150px;';
            // Progress.
            $table->headstyle[7] .= 'min-width: 50px; width: 150px;';
            // Updated at.
            $table->headstyle[8] .= 'min-width: 50px; width: 150px;';
            // Operations.
            $table->headstyle[9] .= 'min-width: 150px; width: 150px;';

            if (check_acl($config['id_user'], 0, 'AW')) {
                $table->head[0] = __('Force');
                $table->align[0] = 'left';
            }

            $table->head[1] = __('Task name');
            $table->align[1] = 'left';

            $table->head[2] = __('Server name');
            $table->align[2] = 'left';

            $table->head[3] = __('Interval');
            $table->align[3] = 'left';

            $table->head[4] = __('Network');
            $table->align[4] = 'left';

            $table->head[5] = __('Status');
            $table->align[5] = 'left';

            $table->head[6] = __('Task type');
            $table->align[6] = 'left';

            $table->head[7] = __('Progress');
            $table->align[7] = 'left';

            $table->head[8] = __('Updated at');
            $table->align[8] = 'left';

            $table->head[9] = __('Operations');
            $table->align[9] = 'left';

            foreach ($recon_tasks as $task) {
                $no_operations = false;
                $data = [];
                $server_name = servers_get_name($task['id_recon_server']);

                // By default.
                $subnet = $task['subnet'];

                // Exceptions: IPAM.
                $ipam = false;
                if ($task['id_recon_script'] != null) {
                    $recon_script_data = db_get_row(
                        'trecon_script',
                        'id_recon_script',
                        $task['id_recon_script']
                    );
                    if ($recon_script_data !== false) {
                        $recon_script_name = $recon_script_data['name'];
                        if (io_safe_output($recon_script_name) == 'IPAM Recon'
                            && enterprise_installed()
                        ) {
                            $subnet_obj = json_decode($task['macros'], true);
                            $subnet = $subnet_obj['1']['value'];
                            $tipam_task_id = db_get_value(
                                'id',
                                'tipam_network',
                                'id_recon_task',
                                $task['id_rt']
                            );
                            $ipam = true;
                        }
                    }
                } else {
                    $recon_script_data = false;
                    $recon_script_name = false;
                }

                if ($task['disabled'] == 0 && $server_name !== '') {
                    if (check_acl($config['id_user'], 0, 'AW')) {
                        $data[0] = '<span class="link" onclick="force_task(\'';
                        $data[0] .= ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&server_id='.$id_server.'&force='.$task['id_rt']
                        );
                        $data[0] .= '\'';
                        if ($task['type'] == DISCOVERY_HOSTDEVICES) {
                            $title = __('Are you sure?');
                            $message = 'This action will rescan the target networks.';
                            $data[0] .= ', {title: \''.$title.'\', message: \''.$message.'\'}';
                        }

                        $data[0] .= ');" >';
                        $data[0] .= html_print_image('images/target.png', true, ['title' => __('Force')]);
                        $data[0] .= '</span>';
                    }
                } else if ($task['disabled'] == 2) {
                    $data[0] = ui_print_help_tip(
                        __('This task has not been completely defined, please edit it'),
                        true
                    );
                } else {
                    $data[0] = '';
                }

                // Name task.
                $data[1] = '';
                if ($task['disabled'] != 2) {
                    $data[1] .= '<span class="link" onclick="progress_task_list('.$task['id_rt'].',\''.$task['name'].'\')">';
                }

                $data[1] .= '<b>'.$task['name'].'</b>';
                if ($task['disabled'] != 2) {
                    $data[1] .= '</span>';
                }

                $data[2] = $server_name;

                if ($task['interval_sweep'] > 0) {
                    $data[3] = human_time_description_raw(
                        $task['interval_sweep']
                    );
                } else {
                    $data[3] = __('Manual');
                }

                if ($task['id_recon_script'] == 0 || $ipam === true) {
                    $data[4] = $subnet;
                } else {
                    $data[4] = '-';
                }

                $can_be_reviewed = false;
                if ($task['review_mode'] == DISCOVERY_STANDARD) {
                    if ($task['status'] <= 0
                        && empty($task['summary']) === false
                    ) {
                        $data[5] = __('Done');
                    } else if ($task['utimestamp'] == 0
                        && empty($task['summary'])
                    ) {
                        $data[5] = __('Not started');
                    } else {
                        $data[5] = __('Pending');
                    }
                } else {
                    if ($task['status'] <= 0
                        && empty($task['summary']) === false
                    ) {
                        $can_be_reviewed = true;
                        $data[5] = '<span class="link review" onclick="show_review('.$task['id_rt'].',\''.$task['name'].'\')">';
                        $data[5] .= __('Review');
                        $data[5] .= '</span>';
                    } else if ($task['utimestamp'] == 0
                        && empty($task['summary'])
                    ) {
                        $data[5] = __('Not started');
                    } else {
                        if ($task['review_mode'] == DISCOVERY_RESULTS) {
                            $data[5] = __('Processing');
                        } else {
                            $data[5] = __('Searching');
                        }
                    }
                }

                switch ($task['type']) {
                    case DISCOVERY_CLOUD_AZURE_COMPUTE:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/plugin.png',
                            true,
                            ['title' => __('Discovery Cloud Azure Compute')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Cloud.Azure.Compute');
                    break;

                    case DISCOVERY_CLOUD_AWS_EC2:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/plugin.png',
                            true,
                            ['title' => __('Discovery Cloud AWS EC2')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Cloud.AWS.EC2');
                    break;

                    case DISCOVERY_CLOUD_AWS_RDS:
                        // Discovery Cloud RDS.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Cloud RDS')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.Cloud.Aws.RDS');
                    break;

                    case DISCOVERY_APP_MYSQL:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Applications MySQL')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.App.MySQL');
                    break;

                    case DISCOVERY_APP_ORACLE:
                        // Discovery Applications Oracle.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Applications Oracle')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.App.Oracle');
                    break;

                    case DISCOVERY_DEPLOY_AGENTS:
                        // Internal deployment task.
                        $no_operations = true;
                        $data[6] = html_print_image(
                            'images/deploy.png',
                            true,
                            ['title' => __('Agent deployment')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.Agent.Deployment');
                    break;

                    case DISCOVERY_HOSTDEVICES:
                    default:
                        if ($task['id_recon_script'] == 0) {
                            // Discovery NetScan.
                            $data[6] = html_print_image(
                                'images/network.png',
                                true,
                                ['title' => __('Discovery NetScan')]
                            ).'&nbsp;&nbsp;';
                            $data[6] .= __('Discovery.NetScan');
                        } else {
                            // APP or external script recon task.
                            $data[6] = html_print_image(
                                'images/plugin.png',
                                true
                            ).'&nbsp;&nbsp;';
                            $data[6] .= $recon_script_name;
                        }
                    break;
                }

                if ($task['status'] <= 0 || $task['status'] > 100) {
                    $data[7] = '-';
                } else {
                    $data[7] = ui_progress(
                        $task['status'],
                        '100%',
                        1.9,
                        // Color.
                        '#82b92e',
                        // Return.
                        true,
                        // Text.
                        '',
                        // Ajax.
                        [
                            'page'     => 'godmode/servers/discovery',
                            'interval' => 10,
                            'simple'   => 1,
                            'data'     => [
                                'wiz'    => 'tasklist',
                                'id'     => $task['id_rt'],
                                'method' => 'taskProgress',
                            ],
                        ]
                    );
                }

                if ($task['utimestamp'] > 0) {
                    $data[8] = ui_print_timestamp(
                        $task['utimestamp'],
                        true
                    );
                } else {
                    $data[8] = __('Not executed yet');
                }

                if (!$no_operations) {
                    if ($task['disabled'] != 2) {
                        $data[9] = '';
                        if ($can_be_reviewed) {
                            $data[9] .= '<a href="#" onclick="show_review('.$task['id_rt'].',\''.$task['name'].'\')">';
                            $data[9] .= html_print_image(
                                'images/expand.png',
                                true,
                                ['title' => __('Review results')]
                            );
                            $data[9] .= '</a>';
                        }

                        $data[9] .= '<a href="#" onclick="progress_task_list('.$task['id_rt'].',\''.$task['name'].'\')">';
                        $data[9] .= html_print_image(
                            'images/eye.png',
                            true,
                            ['title' => __('View summary')]
                        );
                        $data[9] .= '</a>';
                    }

                    if ($task['disabled'] != 2 && $task['utimestamp'] > 0
                        && $task['type'] != DISCOVERY_APP_MYSQL
                        && $task['type'] != DISCOVERY_APP_ORACLE
                        && $task['type'] != DISCOVERY_CLOUD_AWS_RDS
                    ) {
                        if (check_acl($config['id_user'], 0, 'MR')) {
                            $data[9] .= '<a href="#" onclick="show_map('.$task['id_rt'].',\''.$task['name'].'\')">';
                            $data[9] .= html_print_image(
                                'images/dynamic_network_icon.png',
                                true,
                                ['title' => __('View map')]
                            );
                            $data[9] .= '</a>';
                        }
                    }

                    if (check_acl(
                        $config['id_user'],
                        $task['id_group'],
                        'AW'
                    )
                    ) {
                        if ($ipam === true) {
                            $data[9] .= '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=godmode/extensions&sec2=enterprise/extensions/ipam&action=edit&id=%d',
                                    $tipam_task_id
                                )
                            ).'">'.html_print_image(
                                'images/config.png',
                                true,
                                ['title' => __('Edit task')]
                            ).'</a>';
                            $data[9] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=godmode/extensions&sec2=enterprise/extensions/ipam&action=delete&id='.$tipam_task_id
                            ).'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image(
                                'images/cross.png',
                                true,
                                ['title' => __('Delete task')]
                            ).'</a>';
                        } else {
                            // Check if is a H&D, Cloud or Application or IPAM.
                            $data[9] .= '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&task=%d',
                                    $this->getTargetWiz($task, $recon_script_data),
                                    $task['id_rt']
                                )
                            ).'">'.html_print_image(
                                'images/config.png',
                                true,
                                ['title' => __('Edit task')]
                            ).'</a>';
                            $data[9] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&delete=1&task='.$task['id_rt']
                            ).'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image(
                                'images/cross.png',
                                true,
                                ['title' => __('Delete task')]
                            ).'</a>';
                        }
                    } else {
                        $data[9] = '';
                    }
                } else {
                    $data[9] = '-';
                }

                $table->cellclass[][9] = 'action_buttons';

                // Div neccesary for modal progress task.
                echo '<div id="progress_task_'.$task['id_rt'].'" style="display:none"></div>';

                array_push($table->data, $data);
            }

            if (empty($table->data)) {
                $content = '<div class="nf">'.__('Server').' '.$server_name.' '.__('has no discovery tasks assigned').'</div>';
                $return = false;
            } else {
                $content = html_print_table($table, true);
                $return = true;
            }

            ui_toggle($content, __('Server Tasks'), '', '', false);

            // Div neccesary for modal map task.
            echo '<div id="map_task" style="display:none"></div>';
            echo '<div id="task_review" style="display:none"></div>';
            echo '<div id="msg" style="display:none"></div>';
            echo '<input type="hidden" id="ajax-url" value="'.ui_get_full_url('ajax.php').'"/>';
            echo '<input type="hidden" id="success-str" value="'.__('Success').'"/>';
            echo '<input type="hidden" id="failed-str" value="'.__('Failed').'"/>';

            unset($table);

            ui_require_javascript_file('pandora_ui');
            ui_require_javascript_file('pandora_taskList');

            return $return;
        }

        return true;
    }


    /**
     * Show complete list of running tasks.
     *
     * @return boolean Success or not.
     */
    public function showListConsoleTask()
    {
        return enterprise_hook('tasklist_showListConsoleTask', [$this]);
    }


    /**
     * Return target url sub-string to edit target task.
     *
     * @param array $task   With all data.
     * @param array $script With all script data or false if undefined.
     *
     * @return string
     */
    public function getTargetWiz($task, $script=false)
    {
        if ($script !== false) {
            switch ($script['type']) {
                case DISCOVERY_SCRIPT_APP_VMWARE:
                return 'wiz=app&mode=vmware&page=0';

                case DISCOVERY_SCRIPT_IPAM_RECON:
                return '';

                case DISCOVERY_SCRIPT_IPMI_RECON:
                default:
                return 'wiz=hd&mode=customnetscan';

                case DISCOVERY_SCRIPT_CLOUD_AWS:
                    switch ($task['type']) {
                        case DISCOVERY_CLOUD_AWS_EC2:
                        return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&page=1';

                        case DISCOVERY_CLOUD_AZURE_COMPUTE:
                        return 'wiz=cloud&mode=azure&ki='.$task['auth_strings'].'&sub=compute&page=0';

                        default:
                        return 'wiz=cloud';
                    }
            }
        }

        switch ($task['type']) {
            case DISCOVERY_APP_MYSQL:
            return 'wiz=app&mode=mysql&page=0';

            case DISCOVERY_APP_ORACLE:
            return 'wiz=app&mode=oracle&page=0';

            case DISCOVERY_CLOUD_AWS:
            case DISCOVERY_CLOUD_AWS_EC2:
            return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&page=1';

            case DISCOVERY_CLOUD_AWS_RDS:
            return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&sub=rds&page=0';

            case DISCOVERY_APP_SAP:
            return 'wiz=app&mode=SAP&page=0';

            default:
                if ($task['description'] == 'console_task') {
                    return 'wiz=ctask';
                } else {
                    return 'wiz=hd&mode=netscan';
                }
            break;
        }
    }


    /**
     * Returns percent of completion of target task.
     *
     * @return void
     */
    public function taskProgress()
    {
        if (!is_ajax()) {
            echo json_encode(['error' => true]);
            return;
        }

        $id_task = get_parameter('id', 0);

        if ($id_task <= 0) {
            echo json_encode(['error' => true]);
            return;
        }

        $status = db_get_value('status', 'trecon_task', 'id_rt', $id_task);
        if ($status < 0) {
            $status = 100;
        }

        echo json_encode($status);
    }


    /**
     * Generates charts for progress popup.
     *
     * @param array $task Task.
     *
     * @return string Charts in HTML.
     */
    private function progressTaskGraph($task)
    {
        $result .= '<div style="display: flex;">';
        $result .= '<div class="subtitle">';
        $result .= '<span>'._('Overall Progress').'</span>';

        $result .= '<div style="margin-top: 25px;">';
        $result .= progress_circular_bar(
            $task['id_rt'],
            ($task['status'] < 0) ? 100 : $task['status'],
            200,
            200,
            '#7eb641',
            '%',
            '',
            '#3A3A3A',
            0
        );

        $result .= '</div>';
        if ($task['status'] > 0) {
            switch ($task['stats']['step']) {
                case STEP_SCANNING:
                    $str = __('Scanning network');
                break;

                case STEP_CAPABILITIES:
                    $str = __('Checking');
                break;

                case STEP_AFT:
                    $str = __('Finding AFT connectivity');
                break;

                case STEP_TRACEROUTE:
                    $str = __('Finding traceroute connectivity');
                break;

                case STEP_GATEWAY:
                    $str = __('Finding gateway connectivity');
                break;

                case STEP_STATISTICS:
                    $str = __('Searching for devices...');
                break;

                case STEP_APP_SCAN:
                    $str = __('Analyzing application...');
                break;

                case STEP_CUSTOM_QUERIES:
                    $str = __('Executing custom queries...');
                break;

                case STEP_MONITORING:
                    $str = __('Testing modules...');
                break;

                case STEP_PROCESSING:
                    $str = __('Processing results...');
                break;

                default:
                    $str = __('Processing...');
                break;
            }

            $result .= '</div>';
            $result .= '<div class="subtitle">';
            $result .= '<span>'.$str.' ';
            if (empty($str) === false) {
                $result .= $task['stats']['c_network_name'];
            }

            $result .= '</span>';

            $result .= '<div style="margin-top: 25px;">';
            $result .= progress_circular_bar(
                $task['id_rt'].'_detail',
                $task['stats']['c_network_percent'],
                200,
                200,
                '#7eb641',
                '%',
                '',
                '#3A3A3A',
                0
            );
            $result .= '</div></div>';
        }

        if ($task['review_mode'] == DISCOVERY_REVIEW) {
            if ($task['status'] <= 0
                && empty($task['summary']) === false
            ) {
                $result .= '<span class="link review" onclick="show_review('.$task['id_rt'].',\''.$task['name'].'\')">';
                $result .= '&raquo;'.__('Review');
                $result .= '</span>';
            }
        }

        $result .= '</div></div>';

        return $result;
    }


    /**
     * Generates a summary table for given task.
     *
     * @param array $task Task.
     *
     * @return html code with summary.
     */
    private function progressTaskSummary($task)
    {
        global $config;
        include_once $config['homedir'].'/include/graphs/functions_d3.php';

        if (is_array($task) === false) {
            return '';
        }

        $output = '';

        if (is_array($task['stats']) === false) {
            $task['stats'] = json_decode($task['summary'], true);
        }

        if (is_array($task['stats'])) {
            $i = 0;
            $table = new StdClasS();
            $table->class = 'databox data';
            $table->width = '75%';
            $table->styleTable = 'margin: 2em auto 0;border: 1px solid #ddd;background: white;';
            $table->rowid = [];
            $table->data = [];

            if ($task['review_mode'] == DISCOVERY_RESULTS) {
                $agents_review = db_get_all_rows_filter(
                    'tdiscovery_tmp_agents',
                    ['id_rt' => $task['id_rt']]
                );

                $agents = 0;
                $total = 0;
                if (is_array($agents_review)) {
                    foreach ($agents_review as $agent) {
                        $data = json_decode(base64_decode($agent['data']), true);

                        if (is_array($data) === false) {
                            continue;
                        }

                        if (is_array($data['agent']) === false) {
                            continue;
                        }

                        // Ensure agent_id really exists.
                        $agent_id = agents_get_agent_id(
                            $data['agent']['nombre'],
                            true
                        );

                        if ($agent_id > 0) {
                            $agents++;
                        }

                        $total++;
                    }
                }

                // Content.
                $table->data[$i][0] = '<b>'.__('Host&devices total').'</b>';
                $table->data[$i][1] = '<span id="discovered">';
                $table->data[$i][1] .= $total;
                $table->data[$i++][1] .= '</span>';

                $table->data[$i][0] = '<b>'.__('Agents monitored').'</b>';
                $table->data[$i][1] = '<span id="alive">';
                $table->data[$i][1] .= $agents;
                $table->data[$i++][1] .= '</span>';

                $table->data[$i][0] = '<b>'.__('Agents pending').'</b>';
                $table->data[$i][1] = '<span id="alive">';
                $table->data[$i][1] .= ($total - $agents);
                $table->data[$i++][1] .= '</span>';
            } else {
                // Content.
                $table->data[$i][0] = '<b>'.__('Hosts discovered').'</b>';
                $table->data[$i][1] = '<span id="discovered">';
                $table->data[$i][1] .= $task['stats']['summary']['discovered'];
                $table->data[$i++][1] .= '</span>';

                $table->data[$i][0] = '<b>'.__('Alive').'</b>';
                $table->data[$i][1] = '<span id="alive">';
                $table->data[$i][1] .= $task['stats']['summary']['alive'];
                $table->data[$i++][1] .= '</span>';

                $table->data[$i][0] = '<b>'.__('Not alive').'</b>';
                $table->data[$i][1] = '<span id="not_alive">';
                $table->data[$i][1] .= $task['stats']['summary']['not_alive'];
                $table->data[$i++][1] .= '</span>';

                if ($task['type'] == DISCOVERY_HOSTDEVICES) {
                    $table->data[$i][0] = '<b>'.__('Responding SNMP').'</b>';
                    $table->data[$i][1] = '<span id="SNMP">';
                    $table->data[$i][1] .= $task['stats']['summary']['SNMP'];
                    $table->data[$i++][1] .= '</span>';

                    $table->data[$i][0] = '<b>'.__('Responding WMI').'</b>';
                    $table->data[$i][1] = '<span id="WMI">';
                    $table->data[$i][1] .= $task['stats']['summary']['WMI'];
                    $table->data[$i++][1] .= '</span>';
                }
            }

            $output = '<div class="subtitle"><span>'.__('Summary').'</span></div>';
            $output .= html_print_table($table, true).'</div>';
        }

        return $output;
    }


    /**
     * Content of modal 'task progress', ajax only.
     *
     * @return void
     */
    public function progressTaskDiscovery()
    {
        if (!is_ajax()) {
            return;
        }

        $id_task = get_parameter('id', 0);

        if ($id_task <= 0) {
            echo json_encode(['error' => true]);
            return;
        }

        $task = db_get_row('trecon_task', 'id_rt', $id_task);
        $task['stats'] = json_decode($task['summary'], true);
        $summary = $this->progressTaskSummary($task);

        $output = '';

        // Header information.
        if ((int) $task['status'] <= 0 && empty($summary)) {
            $output .= ui_print_info_message(
                __('This task has never executed'),
                '',
                true
            );
        } else if ($task['status'] == 1
            || ($task['utimestamp'] == 0 && $task['interval_sweep'])
        ) {
            $output .= ui_print_info_message(
                __('Task queued, please wait.'),
                '',
                true
            ).'</div>';
        } else {
            $output .= $this->progressTaskGraph($task);
        }

        $output .= $summary;

        echo json_encode(['html' => $output]);
    }


    /**
     * Get a map of target task.
     *
     * @return void
     */
    public function taskShowmap()
    {
        global $config;
        include_once $config['homedir'].'/include/class/NetworkMap.class.php';
        $id_task = get_parameter('id', 0);

        $map = new NetworkMap(
            [
                'id_task'     => $id_task,
                'pure'        => 1,
                'widget'      => true,
                'map_options' => [
                    'map_filter' => [
                        'x_offs'   => 120,
                        'node_sep' => 10,
                    ],
                ],
            ]
        );
        $map->printMap();
    }


    /**
     * Shows a modal to review results found by discovery task.
     *
     * @return void
     */
    public function showTaskReview()
    {
        $id_task = get_parameter('id', 0);
        if ($id_task <= 0) {
            ui_print_error_message(__('Invalid task'));
            return;
        }

        $task_data = db_get_all_rows_filter(
            'tdiscovery_tmp_agents',
            ['id_rt' => $id_task]
        );
        $task = db_get_row('trecon_task', 'id_rt', $id_task);

        $simple_data = [];
        if (is_array($task_data)) {
            foreach ($task_data as $agent) {
                $data = json_decode(base64_decode($agent['data']), true);
                if (is_array($data) === false) {
                    continue;
                }

                if (is_array($data['agent']) === false) {
                    continue;
                }

                $id = $data['agent']['nombre'];

                // Partial.
                $tmp = [
                    'id'      => $id,
                    'name'    => $id,
                    'checked' => $data['agent']['checked'],
                ];

                // Ensure agent_id really exists.
                $agent_id = agents_get_agent_id($data['agent']['nombre'], true);

                if ($agent_id > 0) {
                    $tmp['disabled'] = 1;
                    $tmp['agent_id'] = $agent_id;
                    $tmp['checked'] = 1;
                }

                // Store.
                $simple_data[] = $tmp;

                if (is_array($data['modules'])) {
                    $simple_data = array_merge(
                        $simple_data,
                        array_reduce(
                            $data['modules'],
                            function ($carry, $item) use ($id, $agent_id) {
                                if (empty($item['name'])) {
                                    $item['name'] = $item['nombre'];
                                }

                                if ($item['name'] == 'Host Alive') {
                                    return $carry;
                                }

                                if (empty($item['name'])) {
                                    $item['name'] = $item['nombre'];
                                }

                                $tmp = [
                                    'name'    => $item['name'],
                                    'id'      => $id.'-'.$item['name'],
                                    'pid'     => $id,
                                    'checked' => $item['checked'],
                                ];

                                $agentmodule_id = modules_get_agentmodule_id(
                                    io_safe_input($item['name']),
                                    $agent_id
                                );

                                if ($agentmodule_id > 0) {
                                    $tmp['disabled'] = 1;
                                    $tmp['checked'] = 1;
                                    $tmp['module_id'] = $agentmodule_id;
                                }

                                $carry[] = $tmp;
                                return $carry;
                            },
                            []
                        )
                    );
                }
            }
        }

        echo '<div>';
        echo $this->progressTaskSummary($task);
        echo '</div>';

        if (count($simple_data) > 0) {
            echo '<div class="subtitle">';
            echo '<span>';
            echo __('Please select devices to be monitored');
            echo '</span><div class="manage">';
            echo '<button onclick="$(\'.sim-tree li:not(.disabled) a\').each(function(){simTree_tree.doCheck($(this), false); simTree_tree.clickNode($(this));});">';
            echo __('select all');
            echo '</button>';
            echo '<button onclick="$(\'.sim-tree li:not(.disabled) a\').each(function(){simTree_tree.doCheck($(this), true); simTree_tree.clickNode($(this));});">';
            echo __('deselect all');
            echo '</button>';
            echo '<button onclick="$(\'.sim-tree-spread.sim-icon-r\').click();">';
            echo __('expand all');
            echo '</button>';
            echo '<button onclick="$(\'.sim-tree-spread.sim-icon-d\').click();">';
            echo __('collapse all');
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<form id="review">';
            echo '<div id="tree"></div>';
            echo parent::printTree(
                'tree',
                $simple_data
            );
            echo '</form>';
        } else {
            echo '<div class="subtitle">';
            echo '<span>';
            echo __('No devices found in temporary resources, please re-launch.');
            echo '</span>';
            echo '</div>';
        }

    }


    /**
     * Processes a review over temporary results found by discovery task.
     *
     * @return void
     */
    public function parseTaskReview()
    {
        $id_task = get_parameter('id', 0);
        if ($id_task <= 0) {
            echo $this->error(__('Invalid task'));
            return;
        }

        $ids = [];
        $n_agents = 0;
        $selection = io_safe_output(get_parameter('tree-data-tree', ''));
        if (empty($selection) === false) {
            $selection = json_decode($selection, true);
            $ids = array_reduce(
                $selection,
                function ($carry, $item) use (&$n_agents) {
                    // String is agent-module.
                    $fields = explode('-', $item['id']);
                    $agent_name = $fields[0];
                    $module_name = $fields[1];
                    if ($module_name === null) {
                        // Do not count if already created.
                        if (db_get_value(
                            'id_agente',
                            'tagente',
                            'nombre',
                            io_safe_input($agent_name)
                        ) === false
                        ) {
                            $n_agents++;
                        }
                    }

                    $carry[] = $item['id'];
                    return $carry;
                }
            );
        }

        $task_data = db_get_all_rows_filter(
            'tdiscovery_tmp_agents',
            ['id_rt' => $id_task]
        );

        // License precheck.
        $license = enterprise_hook('license_get_info');

        if (is_array($license) === true
            && $n_agents > ($license['limit'] - $license['count'])
        ) {
            $limit = ($license['limit'] - $license['count']);
            echo json_encode(
                [
                    'error' => __(
                        'Your selection exceeds the agents available on your license. Limit %d',
                        $limit
                    ),
                ]
            );
            return;
        }

        $summary = [];
        if (is_array($ids)) {
            foreach ($task_data as $row) {
                $data = json_decode(base64_decode($row['data']), true);

                if (is_array($data)) {
                    // Analize each agent.
                    $agent_name = $data['agent']['nombre'];
                    if (in_array($agent_name, $ids)) {
                        if ($data['agent']['checked'] != 1) {
                            $summary[] = '<li class="added">'.$agent_name.'</li>';
                        }

                        $data['agent']['checked'] = 1;
                    } else {
                        if ($data['agent']['checked'] == 1) {
                            $summary[] = '<li class="removed">'.__('Removed').' '.$agent_name.'</li>';
                        }

                        $data['agent']['checked'] = 0;
                    }

                    // Modules.
                    if (is_array($data['modules'])) {
                        $n_modules = count($data['modules']);
                        foreach ($data['modules'] as $module_name => $module) {
                            if (in_array($agent_name.'-'.$module_name, $ids)) {
                                if ($data['modules'][$module_name]['checked'] != 1) {
                                    $summary[] = '<li class="added">'.$agent_name.' - '.$module_name.'</li>';
                                }

                                $data['modules'][$module_name]['checked'] = 1;
                            } else {
                                if ($data['modules'][$module_name]['checked'] == 1) {
                                    if ($module_name != 'Host Alive') {
                                        $summary[] = '<li class="removed">'.__('Removed').' '.$agent_name.' - '.$module_name.'</li>';
                                    }
                                }

                                $data['modules'][$module_name]['checked'] = 0;
                            }
                        }
                    }

                    // Update data.
                    db_process_sql_update(
                        'tdiscovery_tmp_agents',
                        [
                            'data'        => base64_encode(json_encode($data)),
                            'review_date' => date('Y-m-d H:i:s'),
                        ],
                        [
                            'id_rt' => $id_task,
                            'label' => $agent_name,
                        ]
                    );
                }
            }
        }

        // Schedule execution.
        db_process_sql_update(
            'trecon_task',
            [
                'utimestamp'  => 0,
                'status'      => 1,
                'review_mode' => DISCOVERY_RESULTS,
            ],
            ['id_rt' => $id_task]
        );

        if (empty($summary)) {
            $out .= __('No changes. Re-Scheduled');
        } else {
            $out .= __('Scheduled for creation');
            $out .= '<ul>';
            $out .= join('', $summary);
            $out .= '</ul>';
        }

        echo json_encode(
            ['result' => $out]
        );
    }


}
