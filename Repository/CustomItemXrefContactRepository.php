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

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

class CustomItemXrefContactRepository extends CommonRepository
{
    /**
     * @param Lead $contact
     *
     * @return int[]
     */
    public function getCustomObjectsRelatedToContact(Lead $contact): array
    {
        $q = $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS);
        $q->select(CustomObject::TABLE_ALIAS.'.id');
        $q->addSelect(CustomObject::TABLE_ALIAS.'.alias');
        $q->innerJoin(CustomItemXrefContact::TABLE_ALIAS.'.customItem', CustomItem::TABLE_ALIAS);
        $q->innerJoin(CustomItem::TABLE_ALIAS.'.customObject', CustomObject::TABLE_ALIAS);
        $q->where(CustomItemXrefContact::TABLE_ALIAS.'.contact = :contactId');
        $q->groupBy(CustomObject::TABLE_ALIAS.'.id');
        $q->andWhere(CustomObject::TABLE_ALIAS.'.isPublished = 1');
        $q->setParameter('contactId', $contact->getId());

        return $q->getQuery()->getResult();
    }

    /**
     * Used by internal Mautic methods. Use the contstant directly instead.
     *
     * @return string
     */
    public function getTableAlias(): string
    {
        return CustomItemXrefContact::TABLE_ALIAS;
    }
}
