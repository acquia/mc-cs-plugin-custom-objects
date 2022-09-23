<?php

namespace MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AllowUniqueIdentifierValidator extends ConstraintValidator
{
    private CustomItemModel $customItemModel;

    public function __construct(CustomItemModel $customItemModel)
    {
        $this->customItemModel  = $customItemModel;
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
        if (!$value->hasChangedIsUniqueIdentifier()) {
            return;
        }

        \assert($constraint instanceof AllowUniqueIdentifier);
        if ($this->checkCustomItemCount($value)) {
            /** @phpstan-ignore-next-line */
            $this->context->buildViolation($constraint->message)
                ->atPath('isUniqueIdentifier')
                ->addViolation();
        }
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function checkCustomItemCount(CustomField $customField): bool
    {
        $customObjectId = (int) $customField->getCustomObject()->getId();

        $customItemRepository = $this->customItemModel->getRepository();
        \assert($customItemRepository instanceof CustomItemRepository);

        return (bool) $customItemRepository->getItemCount($customObjectId);
    }
}
