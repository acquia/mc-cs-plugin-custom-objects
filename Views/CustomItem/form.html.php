<?php declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

/** @var CustomObject $customObject */

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'customItem');

if ($entity->getId()) {
    $header = $view['translator']->trans(
        'custom.item.edit',
        [
            '%object%' => $view['translator']->trans($customObject->getNameSingular()),
            '%item%'   => $view['translator']->trans($entity->getName()),
        ]
    );
} else {
    $header = $view['translator']->trans(
        'custom.item.new',
        ['%object%' => $view['translator']->trans($customObject->getNameSingular())]
    );
}

$view['slots']->set('headerTitle', $header);

$hideCategories = CustomObject::TYPE_RELATIONSHIP === $customObject->getType() ? 'hide' : null;
?>

<?php echo $view['form']->start($form); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-4">
                    <?php echo $view['form']->row($form['name']); ?>
                    <?php echo $view['form']->row($form['custom_field_values']); ?>
                    <?php echo $view['form']->rowIfExists($form, 'child_custom_field_values'); ?>
                    <?php echo $view['form']->row($form['contact_id']); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto <?php echo $hideCategories ?>">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>
</div>

<?php echo $view['form']->end($form); ?>
