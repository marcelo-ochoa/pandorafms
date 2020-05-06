<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;
global $networkmaps_write;
global $networkmaps_manage;
check_login();
ui_require_css_file('first_task');
?>
<?php
ui_print_info_message(['no_close' => true, 'message' => __('There are no transactions defined yet.') ]);

if ($networkmaps_write || $networkmaps_manage) {
    ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_topology.png', true, ['title' => __('Transactions')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Transactions'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                'The new transactional server allows you to execute tasks dependent on the others following a user-defined design. This means that it is possible to coordinate several executions to check a target at a given time.

Transaction graphs represent the different processes within our infrastructure that we use to deliver our service.'
            );
            ?>
                                                                                        </p>
        <form action="index.php?sec=network&sec2=enterprise/operation/agentes/manage_transmap_creation&create_transaction=1" method="post">
            <input type="submit" class="button_task" value="<?php echo __('Create Transactions'); ?>" />
        </form>
    </div>
</div>
    <?php
}
