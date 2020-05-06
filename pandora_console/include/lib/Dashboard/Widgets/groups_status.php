<?php
/**
 * Widget Group status Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Group status
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
 * Group status Widgets.
 */
class GroupsStatusWidget extends Widget
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
        $this->title = __('General group status');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'groups_status';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['groupId']) === true) {
            $this->configurationRequired = true;
        }

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

        if (isset($decoder['groups']) === true) {
            $values['groupId'] = $decoder['groups'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
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

        // Restrict access to group.
        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId',
                'returnAllGroup' => false,
                'privilege'      => 'AR',
                'selected'       => $values['groupId'],
                'return'         => true,
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

        $values['groupId'] = \get_parameter('groupId', 0);

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

        include_once $config['homedir'].'/include/functions_reporting.php';
        include_once $config['homedir'].'/include/functions_graph.php';

        $output = '';
        // Check ACL.
        $user_groups = \users_get_groups(false, 'AR', false);

        if (empty($this->values['groupId']) === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('You don\'t have access'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        // Sanity check for user access.
        if (isset($user_groups[$this->values['groupId']]) === false) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('You don\'t have access'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $stats = \reporting_get_group_stats_resume($this->values['groupId']);

        $data = '<div class="widget-groups-status"><span>';
        $data .= ui_print_group_icon(
            $this->values['groupId'],
            true,
            'groups_small',
            'width:50px; padding-right: 10px'
        );
        $data .= '</span>';

        $url = $config['homeurl'];
        $url .= 'index.php?sec=estado&sec2=operation/agentes/estado_agente';
        $url .= '&refr=60&group_id='.$this->values['groupId'];
        $data .= '<h1>';
        $data .= '<a href="'.$url.'">';
        $data .= $user_groups[$this->values['groupId']];
        $data .= '</a>';
        $data .= '</h1></div>';

        $data .= '<div class="div_groups_status" style="clear: both;">';

        $table = new \stdClass();
        $table->class = 'widget_groups_status';
        $table->cellspacing = '0';
        $table->width = '100%';
        $table->data = [];
        $table->size = [];
        $table->colspan = [];
        $table->cellstyle = [];

        $table->size[0] = '50%';
        $table->size[1] = '50%';

        $style  = 'border-bottom:1px solid #ECECEC; text-align: center;';
        $table->cellstyle[0][0] = $style;
        $table->cellstyle[0][1] = $style;
        $table->cellstyle[1][0] = 'padding-top: 10px;';
        $table->cellstyle[1][1] = 'padding-top: 10px;';

        // Head  agents.
        $table->data[0][0] = '<span>';
        $table->data[0][0] .= html_print_image(
            'images/agent.png',
            true,
            ['alt' => __('Agents')]
        );
        $table->data[0][0] .= ' <b>';
        $table->data[0][0] .= __('Agents');
        $table->data[0][0] .= '</b>';
        $table->data[0][0] .= '</span>';
        $table->data[0][1] = '<span>';
        $table->data[0][1] .= '<b>';
        $table->data[0][1] .= $stats['total_agents'];
        $table->data[0][1] .= '</b>';
        $table->data[0][1] .= '</span>';

        if ($stats['total_agents'] !== 0) {
            // Agent Critical.
            $table->data[1][0] = $this->getCellCounter(
                $stats['agent_critical'],
                '#e63c52'
            );

            // Agent Warning.
            $table->data[2][0] = $this->getCellCounter(
                $stats['agent_warning'],
                '#f3b200'
            );

            // Agent OK.
            $table->data[3][0] = $this->getCellCounter(
                $stats['agent_ok'],
                '#82b92e'
            );

            // Agent Unknown.
            $table->data[1][1] = $this->getCellCounter(
                $stats['agent_unknown'],
                '#B2B2B2'
            );

            // Agent Not Init.
            $table->data[2][1] = $this->getCellCounter(
                $stats['agent_not_init'],
                '#5bb6e5'
            );

            $data .= html_print_table($table, true);
            $data .= '</div>';

            $data .= '<div class="div_groups_status">';

            $table = new \stdClass();
            $table->class = 'widget_groups_status';
            $table->cellspacing = '0';
            $table->width = '100%';
            $table->data = [];
            $table->size = [];
            $table->colspan = [];
            $table->cellstyle = [];

            $table->size[0] = '50%';
            $table->size[1] = '50%';

            $style  = 'border-bottom:1px solid #ECECEC; text-align: center;';
            $table->cellstyle[0][0] = $style;
            $table->cellstyle[0][1] = $style;
            $table->cellstyle[1][0] = 'padding-top: 20px;';
            $table->cellstyle[1][1] = 'padding-top: 20px;';

            // Head  Modules.
            $table->data[0][0] = '<span>';
            $table->data[0][0] .= html_print_image(
                'images/module.png',
                true,
                ['alt' => __('Modules')]
            );

            $table->data[0][0] .= '<b>';
            $table->data[0][0] .= __('Modules');
            $table->data[0][0] .= '</b>';
            $table->data[0][0] .= '</span>';
            $table->data[0][1] = '<span>';
            $table->data[0][1] .= '<b>';
            $table->data[0][1] .= $stats['monitor_total'];
            $table->data[0][1] .= '</b>';
            $table->data[0][1] .= '</span>';

            // Modules Critical.
            $table->data[1][0] = $this->getCellCounter(
                $stats['monitor_critical'],
                '#e63c52'
            );

            // Modules Warning.
            $table->data[2][0] = $this->getCellCounter(
                $stats['monitor_warning'],
                '#f3b200'
            );

            // Modules OK.
            $table->data[3][0] = $this->getCellCounter(
                $stats['monitor_ok'],
                '#82b92e'
            );

            // Modules Unknown.
            $table->data[1][1] = $this->getCellCounter(
                $stats['monitor_unknown'],
                '#B2B2B2'
            );

            // Modules Not Init.
            $table->data[2][1] = $this->getCellCounter(
                $stats['monitor_not_init'],
                '#5bb6e5'
            );

            $data .= html_print_table($table, true);
            $data .= '</div>';
        } else {
            // Not agents in this group.
            $table->colspan[1][0] = 2;
            $table->data[1][0] = __('Not agents in this group');
            $data .= html_print_table($table, true);
            $data .= '</div>';
        }

        $style = 'min-width:200px; min-height:460px;';
        $output = '<div class="container-center" style="'.$style.'">';
        $output .= $data;
        $output .= '</div>';

        return $output;
    }


    /**
     * Draw cell.
     *
     * @param integer|null $count Counter.
     * @param string       $color Background color cell.
     *
     * @return string
     */
    protected function getCellCounter(?int $count, string $color):string
    {
        $output = '<div class= ""';
        $output .= 'style= "background-color:'.$color.'">';
        if (isset($count) === true
            && $count !== 0
        ) {
            $output .= $count;
        } else {
            $output .= 0;
        }

        $output .= '</div>';
        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('General group status');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'groups_status';
    }


}
