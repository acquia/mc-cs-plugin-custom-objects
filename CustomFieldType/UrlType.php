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
use Symfony\Component\Validator\Constraints\UrlValidator;

class UrlType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.url';

    /**
     * Set default protocol to null so the Symfony URL field won't prefix with http:// automatically
     * as it will pass validations then.
     *
     * @var mixed[]
     */
    protected $formTypeOptions = ['default_protocol' => null];

    /**
     * @var string
     */
    protected $key = 'url';

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\UrlType::class;
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

        $constraint = new \Symfony\Component\Validator\Constraints\Url();
        $pattern    = sprintf(UrlValidator::PATTERN, implode('|', $constraint->protocols));

        if (!preg_match($pattern, $value)) {
            throw new \UnexpectedValueException($this->translator->trans('custom.field.url.invalid', ['%value%' => $value], 'validators'));
        }
    }
}
