<?php declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

/* @var PhpEngine $view */
/* @var CustomFieldValueInterface $fieldValue */
?>
<?php if ($fieldValue->getValue() instanceof DateTimeInterface) : ?>
    <?php if ('date' === $fieldValue->getCustomField()->getType()) : ?>
        <?php echo $view['date']->toDate($fieldValue->getValue()); ?>
    <?php else : // This must be 'datetime' field?>
        <?php echo $view['date']->toFull($fieldValue->getValue()); ?>
    <?php endif; ?>
<?php elseif (in_array($fieldValue->getCustomField()->getType(), ['select', 'multiselect'])) : ?>
    <?php echo $view->escape($fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue)); ?>
<?php elseif (is_array($fieldValue->getValue())) : ?>
    <?php echo $view->escape($view['formatter']->arrayToString($fieldValue->getValue())); ?>
<?php else : ?>
    <?php echo $view->escape($fieldValue->getValue()); ?>
<?php endif; ?>