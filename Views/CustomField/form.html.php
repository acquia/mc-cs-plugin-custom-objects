<?php declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \MauticPlugin\CustomObjectsBundle\Entity\CustomField $customField */
$customField;

$title = $customField->getId() ? $customField->getLabel() : $customField->getTypeObject()->getName();
?>

<div class="bundle-form">

    <div class="bundle-form-header">
        <h3 class="mb-lg"><?php echo $title ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>

    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-4">
                    <?php echo $view['form']->row($form['label']); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo $view['form']->end($form); ?>

</div>