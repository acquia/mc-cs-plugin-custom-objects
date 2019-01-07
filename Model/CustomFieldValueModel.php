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

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;

class CustomFieldValueModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param CustomItem $customItem
     * 
     * @return array
     */
    public function getValuesForItem(CustomItem $customItem): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('cfvt');
        $qb->from(CustomFieldValueText::class, 'cfvt');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('cfvt.customItem', ':customItem')
            )
        );
        $qb->setParameter('customItem', $customItem->getId());

        return $qb->getQuery()->getResult();
    }
}
