<?php
/**
 * Widget Tree view Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Tree view
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

namespace PandoraFMS\Dashboard;

/**
 * Tree view Widgets.
 */
class TreeViewWidget extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        ui_require_css_file('tree');
        ui_require_css_file('fixed-bottom-box');

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Tree view');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'tree_view';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;

        $this->overflow_scrollbars = false;
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['tab']) === true) {
            $values['typeTree'] = $decoder['tab'];
        }

        if (isset($decoder['typeTree']) === true) {
            $values['typeTree'] = $decoder['typeTree'];
        }

        if (isset($decoder['search_group']) === true) {
            $values['groupId'] = $decoder['search_group'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
        }

        if (isset($decoder['open_all_nodes']) === true) {
            $values['openAllGroups'] = $decoder['open_all_nodes'];
        }

        if (isset($decoder['openAllGroups']) === true) {
            $values['openAllGroups'] = $decoder['openAllGroups'];
        }

        if (isset($decoder['status_agent']) === true) {
            switch ((int) $decoder['status_agent']) {
                case 0:
                    $values['agentStatus'] = AGENT_STATUS_NORMAL;
                break;

                case 1:
                    $values['agentStatus'] = AGENT_STATUS_CRITICAL;
                break;

                case 2:
                    $values['agentStatus'] = AGENT_STATUS_WARNING;
                break;

                case 3:
                    $values['agentStatus'] = AGENT_STATUS_UNKNOWN;
                break;

                case 5:
                    $values['agentStatus'] = AGENT_STATUS_NOT_INIT;
                break;

                default:
                case -1:
                    $values['agentStatus'] = AGENT_STATUS_ALL;
                break;
            }
        }

        if (isset($decoder['agentStatus']) === true) {
            $values['agentStatus'] = $decoder['agentStatus'];
        }

        if (isset($decoder['search_agent']) === true) {
            $values['filterAgent'] = $decoder['search_agent'];
        }

        if (isset($decoder['filterAgent']) === true) {
            $values['filterAgent'] = $decoder['filterAgent'];
        }

        if (isset($decoder['status_module']) === true) {
            switch ((int) $decoder['status_module']) {
                case 0:
                    $values['moduleStatus'] = AGENT_MODULE_STATUS_NORMAL;
                break;

                case 1:
                    $values['moduleStatus'] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                break;

                case 2:
                    $values['moduleStatus'] = AGENT_MODULE_STATUS_WARNING;
                break;

                case 3:
                    $values['moduleStatus'] = AGENT_MODULE_STATUS_UNKNOWN;
                break;

                case 5:
                    $values['moduleStatus'] = AGENT_MODULE_STATUS_NOT_INIT;
                break;

                default:
                case -1:
                    $values['moduleStatus'] = -1;
                break;
            }

            $values['moduleStatus'] = $decoder['status_module'];
        }

        if (isset($decoder['moduleStatus']) === true) {
            $values['moduleStatus'] = $decoder['moduleStatus'];
        }

        if (isset($decoder['search_module']) === true) {
            $values['filterModule'] = $decoder['search_module'];
        }

        if (isset($decoder['filterModule']) === true) {
            $values['filterModule'] = $decoder['filterModule'];
        }

        return $values;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Type tree view.
        $fields = [
            'group'        => __('Groups'),
            'tag'          => __('Tags'),
            'module_group' => __('Module groups'),
            'module'       => __('Modules'),
            'os'           => __('OS'),
            'policies'     => __('Policies'),
        ];

        $inputs[] = [
            'label'     => __('Type tree'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'typeTree',
                'selected' => $values['typeTree'],
                'return'   => true,
            ],
        ];

        // Groups.
        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => $values['groupId'],
                'return'         => true,
            ],
        ];

        // Open all groups.
        $inputs[] = [
            'label'     => __('Open all groups'),
            'arguments' => [
                'name'  => 'openAllGroups',
                'id'    => 'openAllGroups',
                'type'  => 'switch',
                'value' => $values['openAllGroups'],
            ],
        ];

        // Agents status.
        $fields = [
            AGENT_STATUS_ALL      => __('All'),
            AGENT_STATUS_NORMAL   => __('Normal'),
            AGENT_STATUS_WARNING  => __('Warning'),
            AGENT_STATUS_CRITICAL => __('Critical'),
            AGENT_STATUS_UNKNOWN  => __('Unknown'),
            AGENT_STATUS_NOT_INIT => __('Not init'),
        ];

        $inputs[] = [
            'label'     => __('Agents status'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'agentStatus',
                'selected' => $values['agentStatus'],
                'return'   => true,
            ],
        ];

        // Filter agents.
        $inputs[] = [
            'label'     => __('Filter agents'),
            'arguments' => [
                'name'   => 'filterAgent',
                'type'   => 'text',
                'value'  => $values['filterAgent'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        // Modules status.
        $fields = [
            -1                               => __('All'),
            AGENT_MODULE_STATUS_NORMAL       => __('Normal'),
            AGENT_MODULE_STATUS_WARNING      => __('Warning'),
            AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
            AGENT_MODULE_STATUS_UNKNOWN      => __('Unknown'),
            AGENT_MODULE_STATUS_NOT_INIT     => __('Not init'),
        ];

        $inputs[] = [
            'label'     => __('Modules status'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'moduleStatus',
                'selected' => $values['moduleStatus'],
                'return'   => true,
            ],
        ];

        // Filter modules.
        $inputs[] = [
            'label'     => __('Filter modules'),
            'arguments' => [
                'name'   => 'filterModule',
                'type'   => 'text',
                'value'  => $values['filterModule'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['typeTree'] = \get_parameter('typeTree', '');
        $values['groupId'] = \get_parameter('groupId', 0);
        $values['openAllGroups'] = \get_parameter_switch('openAllGroups');
        $values['agentStatus'] = \get_parameter('agentStatus', 0);
        $values['filterAgent'] = \get_parameter('filterAgent', '');
        $values['moduleStatus'] = \get_parameter('moduleStatus', 0);
        $values['filterModule'] = \get_parameter('filterModule', '');

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        $size = parent::getSize();

        $output = '';

        if (check_acl($config['id_user'], 0, 'AR') === 0) {
            $output .= '<div class="container-center">';
            $output .= ui_print_error_message(
                __(
                    'The user doesn\'t have permission to read agents. Please contact with your %s administrator.',
                    get_product_name()
                ),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $id_cell = $this->cellId;
        $all_nodes = $this->values['openAllGroups'];

        $tab = 'group';
        if (empty($this->values['typeTree']) === false) {
            $tab = $this->values['typeTree'];
        }

        $statusAgent = -1;
        if (isset($this->values['agentStatus']) === true
            && $this->values['agentStatus'] !== AGENT_STATUS_ALL
        ) {
            $statusAgent = $this->values['agentStatus'];
        }

        $searchAgent = '';
        if (empty($this->values['filterAgent']) === false) {
            $searchAgent = $this->values['filterAgent'];
        }

        $statusModule = -1;
        if (isset($this->values['moduleStatus']) === true
            && $this->values['moduleStatus'] !== -1
        ) {
            $statusModule = $this->values['moduleStatus'];
        }

        $searchModule = '';
        if (empty($this->values['filterModule']) === false) {
            $searchModule = $this->values['filterModule'];
        }

        $searchGroup = 0;
        if (empty($this->values['groupId']) === false) {
            $searchGroup = $this->values['groupId'];
        }

        $width = $size['width'];
        $height = $size['height'];

        // Css Files.
        \ui_require_css_file('tree', 'include/styles/', true);

        // Javascript Files.
        \ui_include_time_picker();
        \ui_require_jquery_file(
            'ui.datepicker-'.\get_user_language(),
            'include/javascript/i18n/'
        );

        \ui_require_javascript_file(
            'TreeController',
            'include/javascript/tree/',
            true
        );

        \ui_require_javascript_file(
            'fixed-bottom-box',
            'include/javascript/',
            true
        );

        $base_url = \ui_get_full_url(
            false,
            false,
            false,
            \is_metaconsole()
        );

        // Spinner.
        $output .= \html_print_image(
            'images/spinner.gif',
            true,
            [
                'class' => 'loading_tree',
                'style' => 'display: none;',
            ]
        );

        // Container tree.
        $style = 'height:'.$height.'px; width:'.$width.'px;';
        $style .= 'text-align: left; padding:10px;';
        $idTree = 'tree-controller-recipient_'.$id_cell;

        $output .= '<div style="'.$style.'" id="'.$idTree.'">';
        $output .= '</div>';

        $output .= \html_print_input_hidden(
            'publi_dash_tree_view_hash',
            $hash,
            true
        );
        $output .= \html_print_input_hidden(
            'publi_dash_tree_view_id_user',
            $config['id_user'],
            true
        );

        $settings = [
            'page'         => 'include/ajax/tree.ajax',
            'user'         => $config['id_user'],
            'hash'         => $hash,
            'type'         => $tab,
            'cellId'       => $id_cell,
            'ajaxUrl'      => ui_get_full_url('ajax.php', false, false, false),
            'baseUrl'      => $base_url,
            'searchAgent'  => $searchAgent,
            'statusAgent'  => $statusAgent,
            'searchModule' => $searchModule,
            'statusModule' => $statusModule,
            'searchGroup'  => $searchGroup,
            'openAllNodes' => $all_nodes,
            'timeFormat'   => TIME_FORMAT_JS,
            'dateFormat'   => DATE_FORMAT_JS,
            'userLanguage' => get_user_language(),
            'translate'    => [
                'emptyMessage'  => __('No data found'),
                'foundMessage'  => 'not',
                'total'         => [
                    'agents'  => __('Total agents'),
                    'modules' => __('Total modules'),
                    'none'    => __('Total'),
                ],
                'alerts'        => [
                    'agents'  => __('Fired alerts'),
                    'modules' => __('Fired alerts'),
                    'none'    => __('Fired alerts'),
                ],
                'critical'      => [
                    'agents'  => __('Critical agents'),
                    'modules' => __('Critical modules'),
                    'none'    => __('Critical'),
                ],
                'warning'       => [
                    'agents'  => __('Warning agents'),
                    'modules' => __('Warning modules'),
                    'none'    => __('Warning'),
                ],
                'unknown'       => [
                    'agents'  => __('Unknown agents'),
                    'modules' => __('Unknown modules'),
                    'none'    => __('Unknown'),
                ],
                'not_init'      => [
                    'agents'  => __('Not init agents'),
                    'modules' => __('Not init modules'),
                    'none'    => __('Not init'),
                ],
                'ok'            => [
                    'agents'  => __('Normal agents'),
                    'modules' => __('Normal modules'),
                    'none'    => __('Normal'),
                ],
                'module'        => __('Module'),
                'timeOnlyTitle' => __('Choose time'),
                'timeText'      => __('Time'),
                'hourText'      => __('Hour'),
                'minuteText'    => __('Minute'),
                'secondText'    => __('Second'),
                'currentText'   => __('Now'),
                'closeText'     => __('Close'),
            ],

        ];

        // Show the modal window of an module.
        $output .= '<div id="module_details_window" style="display:none;">';
        $output .= '</div>';

        // Script.
        $output .= '<script type="text/javascript">';
        $output .= 'processTreeSearch('.\json_encode($settings).')';
        $output .= '</script>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Tree view');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'tree_view';
    }


}
