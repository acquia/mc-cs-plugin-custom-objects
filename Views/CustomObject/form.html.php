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

if ($entity->getId()) {
    $header = $view['translator']->trans(
        'custom.object.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]
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
                            <?php foreach ($availableFields as $fieldType => $field): ?>

                                <option data-toggle="ajaxmodal"
                                        data-target="#formComponentModal"
                                        data-href="<?php echo $view['router']->path(
                                            'mautic_formfield_action',
                                            [
                                                'objectAction' => 'new',
                                                'type'         => $fieldType,
                                                'tmpl'         => 'field',
                                                'formId'       => $formId,
                                                'inBuilder'    => $inBuilder,
                                            ]
                                        ); ?>">
                                    <?php echo $field; ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                </div>
                <div class="drop-here">
                    <?php foreach ($formFields as $field): ?>
                        <?php if (!in_array($field['id'], $deletedFields)) : ?>
                            <?php if (!empty($field['isCustom'])):
                                $params   = $field['customParameters'];
                                $template = $params['template'];
                            else:
                                $template = 'MauticFormBundle:Field:'.$field['type'].'.html.php';
                            endif; ?>
                            <?php echo $view->render(
                                'MauticFormBundle:Builder:fieldwrapper.html.php',
                                [
                                    'template'      => $template,
                                    'field'         => $field,
                                    'inForm'        => true,
                                    'id'            => $field['id'],
                                    'formId'        => $formId,
                                    'contactFields' => $contactFields,
                                    'companyFields' => $companyFields,
                                    'inBuilder'     => $inBuilder,
                                ]
                            ); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!count($formFields)): ?>
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
