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

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\ORM\EntityManager;

class CustomFieldOptionModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * CustomFieldOptionModel constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @todo Move this logic into repo.
     *
     * @param int $customFieldId
     */
    public function deleteByCustomFieldId(int $customFieldId): void
    {
        $this->entityManager->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'custom_field_option')
            ->where('custom_field_id = :customFieldId')
            ->setParameter('customFieldId', $customFieldId)
            ->execute();
    }
}
