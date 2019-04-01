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

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
    public function validateValue($value = null, ExecutionContextInterface $context): void
    {
        if (empty($value)) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);

            if (false === $phoneUtil->isValidNumber($phoneNumber)) {
                $context->buildViolation("'{$value} is not valid phone number'")
                    ->atPath('value')
                    ->addViolation();
            }
        } catch (NumberParseException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('value')
                ->addViolation();
        }
    }
}
