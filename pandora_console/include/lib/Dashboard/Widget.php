<?php


namespace PandoraFMS\Dashboard;

/**
 * Dashboard manager.
 */
class Widget
{

    /**
     * Dasboard ID.
     *
     * @var integer
     */
    private $dashboardId;

    /**
     * Cell ID.
     *
     * @var integer
     */
    private $cellId;

    /**
     * Widget Id.
     *
     * @var integer
     */
    private $widgetId;

    /**
     * Values widget.
     *
     * @var array
     */
    private $values;


    /**
     * Contructor widget.
     *
     * @param integer $cellId      Cell Id.
     * @param integer $dashboardId Dashboard Id.
     * @param integer $widgetId    Widget Id.
     */
    public function __construct(
        int $cellId,
        int $dashboardId,
        int $widgetId
    ) {
        // Check exists Cell id.
        if (empty($widgetId) === false) {
            $this->widgetId = $widgetId;
            $this->cellId = $cellId;
            $this->dashboardId = $dashboardId;
            $this->fields = $this->get();

            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $this->dataCell = $cellClass->get();
            $this->values = $this->decoders($this->getOptionsWidget());
        }

        return $this;
    }


    /**
     * Retrieve a cell definition.
     *
     * @return array cell data.
     */
    public function get()
    {
        global $config;

        $sql = sprintf(
            'SELECT *
            FROM twidget
            WHERE id = %d',
            $this->widgetId
        );

        $data = \db_get_row_sql($sql);

        if ($data === false) {
            return [];
        }

        return $data;
    }


    /**
     * Get options Cell widget configuration.
     *
     * @return array
     */
    public function getOptionsWidget():array
    {
        global $config;

        $result = [];
        if (empty($this->dataCell['options']) === false) {
            $result = \json_decode($this->dataCell['options'], true);

            // Hack retrocompatibility.
            if ($result === null) {
                $result = \unserialize($this->dataCell['options']);
            }
        }

        return $result;
    }


    /**
     * Get options Cell widget configuration.
     *
     * @return array
     */
    public function getPositionWidget():array
    {
        global $config;

        $result = [];
        if (empty($this->dataCell['position']) === false) {
            $result = \json_decode($this->dataCell['position'], true);

            // Hack retrocompatibility.
            if ($result === null) {
                $result = \unserialize($this->dataCell['position']);
            }
        }

        return $result;
    }


    /**
     * Insert widgets.
     *
     * @return void
     */
    public function install()
    {
        $id = db_get_value(
            'id',
            'twidget',
            'unique_name',
            $this->getName()
        );

        if ($id !== false) {
            return;
        }

        $values = [
            'unique_name' => $this->getName(),
            'description' => $this->getDescription(),
            'options'     => '',
            'page'        => $this->page,
            'class_name'  => $this->className,
        ];

        $res = db_process_sql_insert('twidget', $values);
        return $res;
    }


    /**
     * Get all dashboard user can you see.
     *
     * @param integer     $offset Offset query.
     * @param integer     $limit  Limit query.
     * @param string|null $search Search word.
     *
     * @return array Return info all dasboards.
     */
    static public function getWidgets(
        int $offset=-1,
        int $limit=-1,
        ?string $search=''
    ):array {
        global $config;

        $sql_limit = '';
        if ($offset !== -1 && $limit !== -1) {
            $sql_limit = ' LIMIT '.$offset.','.$limit;
        }

        $sql_search = '';
        if (empty($search) === false) {
            $sql_search = 'AND description LIKE "%'.$search.'%" ';
        }

        // User admin view all dashboards.
        $sql_widget = \sprintf(
            'SELECT * FROM twidget
            WHERE unique_name <> "agent_module"
            %s
            ORDER BY `description` %s',
            $sql_search,
            $sql_limit
        );

        $widgets = \db_get_all_rows_sql($sql_widget);

        if ($widgets === false) {
            $widgets = [];
        }

        return $widgets;
    }


    /**
     * Install Widgets.
     *
     * @param integer $cellId Cell ID.
     *
     * @return void
     */
    public static function dashboardInstallWidgets(int $cellId)
    {
        global $config;

        $dir = $config['homedir'].'/include/lib/Dashboard/Widgets/';
        $handle = opendir($dir);
        if ($handle === false) {
            return;
        }

        $file = readdir($handle);
        $ignores = [
            '.',
            '..',
        ];

        while ($file !== false) {
            if (in_array($file, $ignores) === true) {
                $file = readdir($handle);
                continue;
            }

            $filepath = realpath($dir.'/'.$file);
            if (is_readable($filepath) === false
                || is_dir($filepath) === true
                || preg_match('/.*\.php$/', $filepath) === false
            ) {
                $file = readdir($handle);
                continue;
            }

            $name = preg_replace('/.php/', '', $file);
            $className = 'PandoraFMS\Dashboard';
            $not_installed = false;
            switch ($name) {
                case 'agent_module':
                    $not_installed = true;
                    $className .= '\AgentModuleWidget';
                break;

                case 'alerts_fired':
                    $className .= '\AlertsFiredWidget';
                break;

                case 'clock':
                    $className .= '\ClockWidget';
                break;

                case 'custom_graph':
                    $className .= '\CustomGraphWidget';
                break;

                case 'events_list':
                    $className .= '\EventsListWidget';
                break;

                case 'example':
                    $className .= '\WelcomeWidget';
                break;

                case 'graph_module_histogram':
                    $className .= '\GraphModuleHistogramWidget';
                break;

                case 'groups_status':
                    $className .= '\GroupsStatusWidget';
                break;

                case 'maps_made_by_user':
                    $className .= '\MapsMadeByUser';
                break;

                case 'maps_status':
                    $className .= '\MapsStatusWidget';
                break;

                case 'module_icon':
                    $className .= '\ModuleIconWidget';
                break;

                case 'module_status':
                    $className .= '\ModuleStatusWidget';
                break;

                case 'module_table_value':
                    $className .= '\ModuleTableValueWidget';
                break;

                case 'module_value':
                    $className .= '\ModuleValueWidget';
                break;

                case 'monitor_health':
                    $className .= '\MonitorHealthWidget';
                break;

                case 'network_map':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\NetworkMapWidget';
                break;

                case 'post':
                    $className .= '\PostWidget';
                break;

                case 'reports':
                    $className .= '\ReportsWidget';
                break;

                case 'service_map':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\ServiceMapWidget';
                break;

                case 'single_graph':
                    $className .= '\SingleGraphWidget';
                break;

                case 'sla_percent':
                    $className .= '\SLAPercentWidget';
                break;

                case 'system_group_status':
                    $className .= '\SystemGroupStatusWidget';
                break;

                case 'tactical':
                    $className .= '\TacticalWidget';
                break;

                case 'top_n_events_by_module':
                    $className .= '\TopNEventByModuleWidget';
                break;

                case 'top_n_events_by_group':
                    $className .= '\TopNEventByGroupWidget';
                break;

                case 'top_n':
                    $className .= '\TopNWidget';
                break;

                case 'tree_view':
                    $className .= '\TreeViewWidget';
                break;

                case 'url':
                    $className .= '\UrlWidget';
                break;

                case 'wux_transaction_stats':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\WuxStatsWidget';
                break;

                case 'wux_transaction':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\WuxWidget';
                break;

                default:
                    $className = false;
                break;
            }

            if ($not_installed === false && $className !== false) {
                include_once $filepath;
                $instance = new $className($cellId, 0, 0);
                if (method_exists($instance, 'install') === true) {
                    $instance->install();
                }
            }

            // Check next.
            $file = readdir($handle);
        }
    }


    /**
     * Draw html.
     *
     * @return string Html data.
     */
    public function printHtml()
    {
        global $config;

        $output = '';

        if ($this->configurationRequired === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Please configure this widget before usage'),
                '',
                true
            );
            $output .= '</div>';
        } else if ($this->loadError === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('Widget cannot be loaded'),
                '',
                true
            );
            $output .= __('Please, configure the widget again to recover it');
            $output .= '</div>';
        } else {
            $output .= $this->load();
        }

        return $output;
    }


    /**
     * Generates inputs for form.
     *
     * @return array Of inputs.
     */
    public function getFormInputs(): array
    {
        $inputs = [];

        $values = $this->values;

        // Default values.
        if (isset($values['title']) === false) {
            $values['title'] = $this->getDescription();
        }

        if (empty($values['background']) === true) {
            $values['background'] = '#ffffff';
        }

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'dashboardId',
                'value' => $this->dashboardId,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'cellId',
                'value' => $this->cellId,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'widgetId',
                'value' => $this->widgetId,
            ],
        ];

        $inputs[] = [
            'label'     => __('Title'),
            'arguments' => [
                'type'   => 'text',
                'name'   => 'title',
                'value'  => $values['title'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        $inputs[] = [
            'label'     => __('Background'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'background',
                'type'    => 'color',
                'value'   => $values['background'],
                'return'  => true,
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
        $values = [];
        $values['title'] = \get_parameter('title', '');
        $values['background'] = \get_parameter('background', '#ffffff');

        return $values;

    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder):array
    {
        $values = [];

        if (isset($decoder['title']) === true) {
            $values['title'] = $decoder['title'];
        }

        if (isset($decoder['background-color']) === true) {
            $values['background'] = $decoder['background-color'];
        }

        if (isset($decoder['background']) === true) {
            $values['background'] = $decoder['background'];
        }

        return $values;

    }


    /**
     * Size Cell.
     *
     * @return array
     */
    protected function getSize():array
    {
        $gridWidth = $this->gridWidth;
        if ($this->gridWidth === 0) {
            $gridWidth = 1170;
        }

        if ($this->width === 0) {
            $width = (((int) $this->position['width'] / 12 * $gridWidth) - 50);
        } else {
            $width = (((int) $this->width / 12 * $gridWidth) - 50);
        }

        if ($this->height === 0) {
            $height = ((((int) $this->position['height'] - 1) * 80) + 60 - 30);
        } else {
            $height = ((((int) $this->height - 1) * 80) + 60 - 30);
        }

        $result = [
            'width'  => $width,
            'height' => $height,
        ];

        return $result;
    }


}
