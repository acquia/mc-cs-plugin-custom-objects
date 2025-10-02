<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\ORM\EntityManager;

class CustomFieldOptionModel
{
    public function __construct(
        private EntityManager $entityManager
    ) {
    }

    /**
     * @todo Move this logic into repo.
     */
    public function deleteByCustomFieldId(int $customFieldId): void
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder->delete(MAUTIC_TABLE_PREFIX.'custom_field_option');
        $queryBuilder->where('custom_field_id = :customFieldId');
        $queryBuilder->setParameter('customFieldId', $customFieldId);
        $queryBuilder->execute();
    }
}
