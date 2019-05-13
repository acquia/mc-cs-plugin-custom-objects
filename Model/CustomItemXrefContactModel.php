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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use DateTime;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemXrefContactModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * @param DateTime   $from
     * @param DateTime   $to
     * @param CustomItem $customItem
     *
     * @return mixed[]
     */
    public function getLinksLineChartData(
        DateTime $from,
        DateTime $to,
        CustomItem $customItem
    ): array {
        $chart = new LineChart(null, $from, $to);
        $query = new ChartQuery($this->entityManager->getConnection(), $from, $to);
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
     *
     * @return string
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_items';
    }
}
