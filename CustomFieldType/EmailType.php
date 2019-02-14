<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class EmailType extends AbstractTextType
{
    protected $key = 'email';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        \Symfony\Component\Form\Extension\Core\Type\EmailType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvemail';
    }
}