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
$title = $customField->getId() ? $customField->getLabel() : $customField->getTypeObject()->getName();

$showProperties = isset($form['options']) || $customField->getTypeObject()->useEmptyValue();
?>

<div class="bundle-form">

    <div class="bundle-form-header">
        <h3 class="mb-lg"><?php echo $title; ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>

    <div role="tabpanel">

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#general" aria-controls="general" role="tab" data-toggle="tab">
                    General
                </a>
            </li>
            <li role="presentation">
                <a href="#validation" aria-controls="validation" role="tab" data-toggle="tab">
                    Validation
                </a>
            </li>
<?php if ($showProperties): ?>
            <li role="presentation">
                <a href="#properties" aria-controls="properties" role="tab" data-toggle="tab">
                    Properties
                </a>
            </li>
<?php endif; ?>
        </ul>

        <div class="tab-content pa-lg">
            <div role="tabpanel" class="tab-pane active" id="general">

                <div class="row">
                    <div class="col-md-6" >
                        <?php echo $view['form']->row($form['label']); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['alias']); ?>
                    </div>
                </div>

                <div class="row default-value">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['defaultValue']); ?>
                    </div>
                </div>

            </div>

            <div role="tabpanel" class="tab-pane" id="validation">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['params']['requiredValidationMessage']); ?>
                    </div>
                    <div class="col-md-6 chosen-required">
                        <?php echo $view['form']->row($form['required']); ?>
                    </div>
                </div>
            </div>

<?php if ($showProperties): ?>
            <div role="tabpanel" class="tab-pane" id="properties">
                <div class="row">
<?php if (isset($form['options'])): ?>
                    <div class="col-md-12">
                        <?php echo $view['form']->row($form['options']); ?>
                    </div>
<?php endif; ?>
                </div>
                <div class="row">
<?php if ($customField->getTypeObject()->useEmptyValue()): ?>
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['params']['emptyValue']); ?>
                    </div>
<?php endif; ?>
                </div>
            </div>
<?php endif; ?>

        </div>

    </div>

    <?php echo $view['form']->rest($form); ?>
    <?php echo $view['form']->end($form); ?>

</div>