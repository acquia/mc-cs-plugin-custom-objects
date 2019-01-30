<?php

$customFieldEntity = $customField->vars['data'];

?>
<div class="panel form-field-wrapper ui-sortable-handle" data-sortable-id="mauticform_1">

    <div class="form-buttons btn-group" role="group" aria-label="Field options" style="width: 77px;">

        <button type="button" data-toggle="ajaxmodal" data-target="#objectFieldModal" href="<?php
        echo $view['router']->path(
            \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider::ROUTE_FORM,
            [
                'fieldId' => $customFieldEntity->getId(),
                'objectId' => $customObject->getId(),
                'fieldType' => $customFieldEntity->getTypeObject()->getKey(),
            ]
        );
        ?>" class="btn btn-default btn-edit">
            <i class="fa fa-pencil-square-o text-primary"></i>
        </button>

        <a type="button" data-hide-panel="true" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php // @todo ?>" class="btn btn-default">
            <i class="fa fa-trash-o text-danger"></i>
        </a>

    </div>

    <div class="row ml-0 mr-0" style="width: 707px;">
        <div id="mauticform_1" data-validate="name" data-validation-type="text" class="mauticform-row mauticform-text mauticform-field-1 mauticform-required">
            <?php echo $view['form']->row($customField['label']) ?>
        </div>
    </div>
    <?php echo $view['form']->rest($customField) ?>
</div>