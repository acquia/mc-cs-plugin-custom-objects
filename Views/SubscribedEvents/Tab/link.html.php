<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<li>
    <a href="#<?php echo $tabId; ?>-container" role="tab" data-toggle="tab" id="<?php echo $tabId; ?>">
        <?php if (isset($count)) : ?>
        <span class="label label-primary mr-sm" id="<?php echo $tabId; ?>-count">
            <?php echo $count; ?>
        </span>
        <?php endif; ?>
        <?php echo $view->escape($view['translator']->trans($title)); ?>
    </a>
</li>