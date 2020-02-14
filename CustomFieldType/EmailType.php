<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class EmailType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.email';

    /**
     * @var string
     */
    protected $key = 'email';

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\EmailType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(CustomField $customField, $value): void
    {
        parent::validateValue($customField, $value);

        if (empty($value)) {
            return;
        }

        if (!preg_match('/^.+\@\S+\.\S+$/', $value)) {
            throw new \UnexpectedValueException($this->translator->trans('custom.field.email.invalid', ['%value%' => $value], 'validators'));
        }
    }
}
