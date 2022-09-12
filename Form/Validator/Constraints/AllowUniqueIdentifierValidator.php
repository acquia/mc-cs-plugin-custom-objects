<?php

namespace MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AllowUniqueIdentifierValidator extends ConstraintValidator
{
    private CustomFieldModel $customFieldModel;

    private CustomItemModel $customItemModel;

    /**
     * @param CustomFieldModel $customFieldModel
     */
    public function __construct(CustomFieldModel $customFieldModel, CustomItemModel $customItemModel)
    {
        $this->customFieldModel = $customFieldModel;
        $this->customItemModel = $customItemModel;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof CustomField) {
            throw new UnexpectedTypeException($value, CustomField::class);
        }

        // If record is new then allow to add unique identifier field
        if ($value->isNew()) {
            return;
        }

        // If isUniqueIdentifier is not changed then allow to edit custom field
        if (!$value->wasChangeIsUniqueIdentifier()) {
            return;
        }

        if ($this->checkCustomItemCount($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('isUniqueIdentifier')
                ->addViolation();
        }
    }

    /**
     * @param CustomField $customField
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function checkCustomItemCount(CustomField $customField): bool
    {
        $customObjectId = $customField->getCustomObject()->getId();

        /** @var CustomItemRepository $customItemRepository */
        $customItemRepository = $this->customItemModel->getRepository();
        return (bool) $customItemRepository->getItemCount($customObjectId);
    }
}