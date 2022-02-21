<?php declare(strict_types=1);

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

    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans(
    'mautic.core.details'
); ?></a></li>
                    <li id="fields-tab"><a href="#fields-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans(
    'mautic.form.tab.fields'
); ?></a></li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="details-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['nameSingular']); ?>
                                <?php echo $view['form']->row($form['namePlural']); ?>

                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['alias']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['description']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="fields-container">
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
                                <?php include '_form-fields.html.php'; ?>
                            </div>
                            <?php if (!count($customFields)): ?>
                                <div class="alert alert-info" id="form-field-placeholder">
                                    <p><?php echo $view['translator']->trans('mautic.form.form.addfield'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['type']); ?>
            <?php echo $view['form']->row($form['masterObject']); ?>
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
