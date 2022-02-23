<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SelectType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.select';

    /**
     * @var string
     */
    protected $key = 'select';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded' => false,
        'multiple' => false,
    ];

    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'empty', '!empty']);

        return array_intersect_key($allOperators, $allowedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function valueToString(CustomFieldValueInterface $fieldValue): string
    {
        $value = $fieldValue->getValue();

        try {
            return $fieldValue->getCustomField()->valueToLabel((string) $value);
        } catch (NotFoundException $e) {
            // When the value does not exist anymore, use the value instead.
            return $value;
        }
    }
}
