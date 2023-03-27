<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class PhoneType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.phone';

    /**
     * @var string
     */
    protected $key = 'phone';

    /**
     * {@inheritdoc}
     */
    public function validateValue(CustomField $customField, $value): void
    {
        parent::validateValue($customField, $value);

        if (empty($value)) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $message   = $this->translator->trans('custom.field.phone.invalid', ['%value%' => $value], 'validators');

        try {
            $phoneNumber = $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException $e) {
            throw new \UnexpectedValueException($message);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            throw new \UnexpectedValueException($message);
        }
    }
}
