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

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

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
     *
     * @throws InvalidOptionsException
     */
    public function validateValue(CustomFieldValueInterface $valueEntity, ExecutionContextInterface $context): void
    {
        parent::validateValue($valueEntity, $context);

        $value = $valueEntity->getValue();

        if (empty($value)) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException $e) {
            $this->addViolation($value, $context);

            return;
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            $this->addViolation($value, $context);
        }
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    private function addViolation(string $value, ExecutionContextInterface $context): void
    {
        $context->buildViolation($this->translator->trans('custom.field.phone.invalid', ['%value%' => $value], 'validators'))
            ->atPath('value')
            ->addViolation();
    }
}
