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
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefContactEvent;
use DateTimeInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemXrefContactModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager            $entityManager
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->dispatcher    = $dispatcher;
    }

    /**
     * @param int $customItemId
     * @param int $contactId
     */
    public function linkContact(int $customItemId, int $contactId): CustomItemXrefContact
    {
        try {
            $xRef = $this->getContactReference($customItemId, $contactId);
        } catch (NoResultException $e) {
            $xRef = new CustomItemXrefContact(
                $this->entityManager->getReference(CustomItem::class, $customItemId),
                $this->entityManager->getReference(Lead::class, $contactId)
            );

            $this->entityManager->persist($xRef);
            $this->entityManager->flush();

            $this->dispatcher->dispatch(
                CustomItemEvents::ON_CUSTOM_ITEM_LINK_CONTACT,
                new CustomItemXrefContactEvent($xRef)
            );
        }

        return $xRef;
    }

    /**
     * @param int $customItemId
     * @param int $contactId
     */
    public function unlinkContact(int $customItemId, int $contactId): void
    {
        try {
            $xRef = $this->getContactReference($customItemId, $contactId);
            $this->entityManager->remove($xRef);
            $this->entityManager->flush();

            $this->dispatcher->dispatch(
                CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_CONTACT,
                new CustomItemXrefContactEvent($xRef)
            );
        } catch (NoResultException $e) {
            // If not found then we are done here.
        }
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @param CustomItem        $customItem
     *
     * @return mixed[]
     */
    public function getLinksLineChartData(
        DateTimeInterface $from,
        DateTimeInterface $to,
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

    /**
     * @param int $customItemId
     * @param int $contactId
     *
     * @return CustomItemXrefContact
     *
     * @throws NoResultException if the reference does not exist
     */
    private function getContactReference(int $customItemId, int $contactId): CustomItemXrefContact
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('cixcont');
        $queryBuilder->from(CustomItemXrefContact::class, 'cixcont');
        $queryBuilder->where('cixcont.customItem = :customItemId');
        $queryBuilder->andWhere('cixcont.contact = :contactId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->setParameter('contactId', $contactId);

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
