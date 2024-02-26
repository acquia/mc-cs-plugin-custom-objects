<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use DateTime;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class CustomItemXrefContactModel extends FormModel
{
    /**
     * @return mixed[]
     */
    public function getLinksLineChartData(
        DateTime $from,
        DateTime $to,
        CustomItem $customItem
    ): array {
        $chart = new LineChart(null, $from, $to);
        $query = new ChartQuery($this->em->getConnection(), $from, $to);
        $links = $query->fetchTimeData(
            'custom_item_xref_contact',
            'date_added',
            ['custom_item_id' => $customItem->getId()]
        );
        $chart->setDataset($this->translator->trans('custom.item.linked.contacts'), $links);

        return $chart->render();
    }

    /**
     * Used only by Mautic's generic methods. Use CustomItemPermissionProvider instead.
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_items';
    }
}
