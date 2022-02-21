<?php declare(strict_types=1);

/**
 * Panels rendered in CO form.
 */

/** @var \MauticPlugin\CustomObjectsBundle\Entity\CustomField $customFieldEntity */
$customFieldEntity          = $customField->vars['data'];
$customField->vars['index'] = $customField->vars['name'];
$order                      = (int) $customField->vars['value']->getOrder();
$deleted                    = !empty($_POST['custom_object']['customFields'][$order]['deleted']) ? 'style="display:none;"' : '';

$panelId = !empty($panelId) ? $panelId : (int) $customField->vars['value']->getOrder();
?>
<div class="panel form-field-wrapper ui-sortable-handle" id="customField_<?php echo $panelId; ?>" <?php echo $deleted; ?>>

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
        <div class="mauticform-row">
            <div class="col-xs-10">
                <?php echo $view['form']->row($customField['defaultValue']); ?>
            </div>
        </div>
    </div>
    <div class="hidden-fields">
        <?php echo $view['form']->rest($customField); ?>
    </div>
    <div class="panel-footer">
        <i class="fa fa-cog" aria-hidden="true"></i>
        <span class="inline-spacer"><?php echo $customFieldEntity->getTypeObject()->getName(); ?></span>
    </div>
</div>