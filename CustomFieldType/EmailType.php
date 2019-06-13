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
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Symfony\Component\Validator\Constraints\EmailValidator;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

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

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\EmailType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSymfonyFormConstraints(): array
    {
        return [
            new \Symfony\Component\Validator\Constraints\Email(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(CustomFieldValueInterface $valueEntity, ExecutionContextInterface $context): void
    {
        parent::validateValue($valueEntity, $context);

        $value = $valueEntity->getValue();
        
        if (empty($value)) {
            return;
        }

        $emailValidator = new EmailValidator();
        $emailValidator->initialize($context);

        try {
            $emailValidator->validate($value, $this->getSymfonyFormConstraints()[0]);
        } catch (InvalidEmailException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('value')
                ->addViolation();
        }
    }
}
