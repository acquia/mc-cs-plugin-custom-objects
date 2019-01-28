<div class="panel form-field-wrapper ui-sortable-handle" data-sortable-id="mauticform_1">
    <div class="form-buttons btn-group" role="group" aria-label="Field options" style="width: 77px;">
        <button type="button" data-toggle="ajaxmodal" data-target="#objectFieldModal" href="<?php
        echo $view['router']->path(
            \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider::ROUTE_FORM,
            [
                'fieldId' => $customField->getId(),
                'objectId' => $customObject->getId(),
                'fieldType' => $customField->getType()->getKey(),
            ]
        );
        ?>" class="btn btn-default btn-edit">
            <i class="fa fa-pencil-square-o text-primary"></i>
        </button>
        <a type="button" data-hide-panel="true" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php
        echo $view['router']->path(
            'mautic_custom_field_delete',
            ['fieldId' => $customField->getId()]
        )
        ?>" class="btn btn-default">
            <i class="fa fa-trash-o text-danger"></i>
        </a>
    </div>
    <div class="row ml-0 mr-0" style="width: 707px;">
        <div id="mauticform_1" data-validate="name" data-validation-type="text" class="mauticform-row mauticform-text mauticform-field-1 mauticform-required">
            <label id="mauticform_label_name" for="mauticform_input_name" class="mauticform-label">
                Label
            </label>
            <input id="mauticform_input_name" name="mauticform[name]" value="<?php echo $customField->getLabel() ?>" disabled="disabled" class="mauticform-input" type="text">
            <span class="mauticform-errormsg" style="display: none;">This is required.</span>
        </div>
    </div>
</div>