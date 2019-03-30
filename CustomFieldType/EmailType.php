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
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Exception\InvalidEmailException;

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
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @param TranslatorInterface $translator
     * @param EmailValidator      $emailValidator
     */
    public function __construct(TranslatorInterface $translator, EmailValidator $emailValidator)
    {
        parent::__construct($translator);

        $this->emailValidator = $emailValidator;
    }

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
    public function validateValue($value = null, ExecutionContextInterface $context): void
    {
        if (empty($value)) {
            return;
        }

        try {
            $this->emailValidator->validate($value);
        } catch (InvalidEmailException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('value')
                ->addViolation();
        }
    }
}
