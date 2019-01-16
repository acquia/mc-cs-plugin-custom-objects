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
    <a class="custom-object-tab" href="#custom-object-<?php echo $customObject->getId(); ?>-container" role="tab" data-toggle="tab" data-custom-object-id="<?php echo $customObject->getId(); ?>" id="custom-object-<?php echo $customObject->getId(); ?>">
        <span class="label label-primary mr-sm" id="custom-object-<?php echo $customObject->getId(); ?>-count">
            <?php echo $count; ?>
        </span>
        <?php echo $view['translator']->trans($customObject->getNamePlural()); ?>
    </a>
</li>