<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="row">
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['field']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['operator']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['value']); ?>
    </div>
</div>
