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
use Mautic\FormBundle\Validator\Constraint\PhoneNumberConstraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Mautic\FormBundle\Validator\Constraint\PhoneNumberConstraintValidator;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

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
    public function getSymfonyFormConstraints(): array
    {
        $phoneNumberConstraint          = new PhoneNumberConstraint();
        $phoneNumberConstraint->message = $this->translator->trans('mautic.form.submission.phone.invalid');

        return [
            $phoneNumberConstraint,
        ];
    }

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

        $validator = new PhoneNumberConstraintValidator();

        $validator->initialize($context);
        $validator->validate($value, $this->getSymfonyFormConstraints()[0]);
    }
}
