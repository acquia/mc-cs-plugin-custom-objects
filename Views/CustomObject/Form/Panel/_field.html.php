<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Panels rendered in CO form
 */

$customFieldEntity          = $customField->vars['data'];
$customField->vars['index'] = $customField->vars['name'];
$order                      = (int) $customField->vars['value']->getOrder();
$deleted                    = !empty($_POST['custom_object']['customFields'][$order]['deleted']) ? 'style="display:none;"' : '';
?>
<div class="panel form-field-wrapper ui-sortable-handle" id="customField_<?php echo (int) $customField->vars['value']->getOrder() ?>" <?php echo $deleted ?>>

    <div class="form-buttons btn-group" role="group" aria-label="Field options" style="width: 77px;">

        <button type="button" data-toggle="ajaxmodal" data-target="#objectFieldModal" href="<?php
        echo $view['router']->path(
            \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider::ROUTE_FORM,
            [
                'fieldId'   => $customFieldEntity->getId(),
                'objectId'  => $customObject->getId(),
                'fieldType' => $customFieldEntity->getTypeObject()->getKey(),
            ]
        );
        ?>" class="btn btn-default btn-edit">
            <i class="fa fa-pencil-square-o text-primary"></i>
        </button>

        <a type="button" data-hide-panel="true" class="btn btn-default">
            <i class="fa fa-trash-o text-danger"></i>
        </a>

    </div>

    <div class="row ml-0 mr-0">
        <div id="mauticform_1" data-validate="name" data-validation-type="text" class="mauticform-row mauticform-text mauticform-field-1 mauticform-required">
            <?php echo $view['form']->row($customField['label']) ?>
        </div>
    </div>
    <?php echo $view['form']->rest($customField) ?>
</div>