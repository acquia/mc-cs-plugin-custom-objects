<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'customObject');

if ($customObject->getId()) {
    $header = $view['translator']->trans(
        $customObject->getId() ? 'custom.object.edit' : 'custom.object.new',
        ['%name%' => $view['translator']->trans($customObject->getName())]
    );
} else {
    $header = $view['translator']->trans('custom.object.new');
}

$view['slots']->set('headerTitle', $header);
?>

<?php echo $view['form']->start($form); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">

        <div class="pa-md" id="details-container">
            <div class="row">
                <div class="col-md-4">
                    <?php echo $view['form']->row($form['namePlural']); ?>
                    <?php echo $view['form']->row($form['nameSingular']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="pa-md" id="fields-container">
            <?php echo $view->render('MauticFormBundle:Builder:style.html.php'); ?>
            <div id="mauticforms_fields">
                <div class="row">
                    <div class="available-fields mb-md col-sm-4">
                        <select class="chosen form-builder-new-component" data-placeholder="<?php echo $view['translator']->trans('mautic.form.form.component.fields'); ?>">
                            <option value=""></option>
                            <?php foreach ($availableFieldTypes as $fieldType): ?>

                                <option data-toggle="ajaxmodal"
                                        data-target="#objectFieldModal"
                                        data-href="<?php
                                            echo $view['router']->path(
                                                \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider::ROUTE_FORM,
                                                [
                                                    'objectId'  => $customObject->getId(),
                                                    'fieldType' => $fieldType->getKey(),
                                                ]
                                            );
                                        ?>">
                                    <?php echo $fieldType->getName(); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="drop-here">
                    <?php
                        foreach ($form->children['customFields']->getIterator() as $customField):
                            $customFieldEntity = $customField->vars['data'];
                            if (!in_array($customFieldEntity->getId(), $deletedFields)) :
                                echo $view->render(
                                    "CustomObjectsBundle:CustomObject:FormFields\\field.{$customFieldEntity->getType()}.html.php",
                                    ['customField' => $customField, 'customObject' => $customObject]
                                );
                            endif;
                        endforeach;
                        $form->children['customFields']->setRendered();
                    ?>
                </div>
                <?php if (!count($customFields)): ?>
                    <div class="alert alert-info" id="form-field-placeholder">
                        <p><?php echo $view['translator']->trans('mautic.form.form.addfield'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>

</div>

<?php echo $view['form']->end($form); ?>

<?php
$view['slots']->append(
    'modal',
    $view->render(
        'MauticCoreBundle:Helper:modal.html.php',
        [
            'id'            => 'objectFieldModal',
            'header'        => false,
            'footerButtons' => true,
        ]
    )
);