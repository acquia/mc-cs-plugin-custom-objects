<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemXrefContactModelTest extends \PHPUnit_Framework_TestCase
{
    private $customItem;

    private $customItemXrefContact;

    private $user;

    private $entityManager;

    private $queryBuilder;

    private $query;

    private $translator;

    private $dispatcher;

    private $customItemXrefContactModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->customItemXrefContact        = $this->createMock(CustomItemXrefContact::class);
        $this->user                         = $this->createMock(User::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->queryBuilder                 = $this->createMock(QueryBuilder::class);
        $this->query                        = $this->createMock(AbstractQuery::class);
        $this->translator                   = $this->createMock(TranslatorInterface::class);
        $this->dispatcher                   = $this->createMock(EventDispatcherInterface::class);
        $this->customItemXrefContactModel   = new CustomItemXrefContactModel(
            $this->entityManager,
            $this->translator,
            $this->dispatcher
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testLinkContactIfReferenceExists(): void
    {
        $customItemId = 36;
        $contactId    = 22;

        $this->privateTestGetContactReference();

        $this->entityManager->expects($this->never())->method('getReference');

        $this->assertSame(
            $this->customItemXrefContact,
            $this->customItemXrefContactModel->linkContact($customItemId, $contactId)
        );
    }

    public function testLinkContactIfReferenceDoesNotExist(): void
    {
        $customItemId = 36;
        $contactId    = 22;

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->will($this->throwException(new NoResultException()));

        $this->entityManager->expects($this->exactly(2))
            ->method('getReference')
            ->withConsecutive(
                [CustomItem::class, $customItemId],
                [Lead::class, $contactId]
            )
            ->will($this->onConsecutiveCalls($this->customItem, $this->createMock(Lead::class)));

        $this->assertInstanceOf(
            CustomItemXrefContact::class,
            $this->customItemXrefContactModel->linkContact($customItemId, $contactId)
        );
    }

    public function testGetLinksLineChartData(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $from         = new DateTime('2019-03-02 12:30:00');
        $to           = new DateTime('2019-04-02 12:30:00');
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(DBALQueryBuilder::class);
        $statement    = $this->createMock(Statement::class);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $chartData = $this->customItemXrefContactModel->getLinksLineChartData(
            $from,
            $to,
            $this->customItem
        );

        $this->assertCount(32, $chartData['labels']);
        $this->assertCount(32, $chartData['datasets'][0]['data']);
    }

    private function privateTestGetContactReference(): void
    {
        $customItemId = 36;
        $contactId    = 22;

        $this->queryBuilder->expects($this->once())->method('select')->with('cixcont');
        $this->queryBuilder->expects($this->once())->method('from')->with(CustomItemXrefContact::class, 'cixcont');
        $this->queryBuilder->expects($this->once())->method('where')->with('cixcont.customItem = :customItemId');
        $this->queryBuilder->expects($this->once())->method('andWhere')->with('cixcont.contact = :contactId');
        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customItemId', $customItemId],
                ['contactId', $contactId]
            );
        
        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($this->customItemXrefContact);
    }
}
