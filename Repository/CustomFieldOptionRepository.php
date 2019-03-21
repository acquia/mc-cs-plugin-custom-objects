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

class CustomFieldOptionRepository extends CommonRepository
{
    /**
     * @todo Use it before save in \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPreSaveSubscriber::preSave
     *
     * @param int $customFieldId
     */
    public function deleteByCustomFieldId(int $customFieldId): void
    {
        $this->_em->getConnection()->createQueryBuilder()
            ->delete('o')
            ->from($this->getTableName())
            ->where('o.custom_field_id = :customFieldId')
            ->setParameter('customFieldId', $customFieldId)
            ->execute();
    }
}
