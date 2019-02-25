<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class CustomFieldValueRepository extends CommonRepository
{
    /**
     * @param CustomItem $customItem
     *
     * @return array
     */
    public function getValuesForItem(CustomItem $customItem)
    {
        $values = [];
        $q      = $this->createQueryBuilder('cfv', 'cfv.id');
        $q->select('DISTINCT(cfvt.id) AS value_id, IDENTITY(cfv.customField) AS field_id, cfvt.value')
            ->innerJoin(CustomFieldValueText::class, 'cfvt')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cfv.customObject', ':customObject')
                )
            )
            ->setParameter('customObject', $customItem->getCustomObject()->getId());

        $rows = $q->getQuery()->getArrayResult();

        foreach ($rows as $row) {
            // @todo make multi-select values an array?
            $values[$row['field_id']] = $row['value'];
        }

        return $values;
    }
}
