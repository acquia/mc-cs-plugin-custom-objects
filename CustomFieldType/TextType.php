<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class TextType extends AbstractTextType
{
    protected $key = 'text';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvtext';
    }
}
