<?php

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
    <a class="custom-object-tab" href="#custom-object-<?php echo $customObjectId; ?>-container" role="tab" data-toggle="tab" data-custom-object-id="<?php echo $customObjectId; ?>" id="custom-object-<?php echo $customObjectId; ?>">
        <span class="label label-primary mr-sm" id="custom-object-<?php echo $customObjectId; ?>-count">
            <?php echo $count; ?>
        </span>
        <?php echo $view['translator']->trans($title); ?>
    </a>
</li>