<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;

class CustomItemXrefCustomItemRepository extends CommonRepository
{
    public function deleteAllLinksForCustomItem(int $customItemId): void
    {
        $queryBuilder = $this->createQueryBuilder(CustomItemXrefCustomItem::TABLE_ALIAS);
        $queryBuilder->delete();
        $queryBuilder->where(CustomItemXrefCustomItem::TABLE_ALIAS.'.customItemLower = :customItemId');
        $queryBuilder->orWhere(CustomItemXrefCustomItem::TABLE_ALIAS.'.customItemHigher = :customItemId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->getQuery()->execute();
    }
}
