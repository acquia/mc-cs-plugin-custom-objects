<?php declare(strict_types=1);

use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

/* @var PhpEngine $view */
/* @var CustomFieldValueInterface $fieldValue */

$customFieldType = $fieldValue->getCustomField()->getType();
?>
<?php if ($fieldValue->getValue() instanceof DateTimeInterface) : ?>
    <?php if ('date' === $customFieldType) : ?>
        <?php echo $view['date']->toDate($fieldValue->getValue()); ?>
    <?php else : // This must be 'datetime' field?>
        <?php echo $view['date']->toFull($fieldValue->getValue()); ?>
    <?php endif; ?>
<?php elseif (in_array($customFieldType, ['select', 'multiselect'])) : ?>
    <?php echo $view->escape($fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue)); ?>
<?php elseif (is_array($fieldValue->getValue())) : ?>
    <?php echo $view->escape($view['formatter']->arrayToString($fieldValue->getValue())); ?>
<?php else : ?>
    <?php echo $view->escape($fieldValue->getValue()); ?>
<?php endif; ?>