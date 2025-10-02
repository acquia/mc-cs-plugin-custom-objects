<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CustomItemXrefContactModelTest extends \PHPUnit\Framework\TestCase
{
    private $customItem;

    private $entityManager;

    /**
     * @var MockObject|CorePermissions
     */
    private $security;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|UrlGeneratorInterface
     */
    private $router;

    /**
     * @var MockObject|Translator
     */
    private $translator;

    /**
     * @var MockObject|UserHelper
     */
    private $userHelper;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelper;

    private $queryBuilder;

    private $query;

    /**
     * @var CustomItemXrefContactModel
     */
    private $customItemXrefContactModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->queryBuilder                 = $this->createMock(QueryBuilder::class);
        $this->query                        = $this->createMock(AbstractQuery::class);
        $this->translator                   = $this->createMock(Translator::class);
        $this->security                     = $this->createMock(CorePermissions::class);
        $this->dispatcher                   = $this->createMock(EventDispatcherInterface::class);
        $this->router                       = $this->createMock(UrlGeneratorInterface::class);
        $this->userHelper                   = $this->createMock(UserHelper::class);
        $this->logger                       = $this->createMock(LoggerInterface::class);
        $this->coreParametersHelper         = $this->createMock(CoreParametersHelper::class);
        $this->customItemXrefContactModel   = new CustomItemXrefContactModel(
            $this->entityManager,
            $this->security,
            $this->dispatcher,
            $this->router,
            $this->translator,
            $this->userHelper,
            $this->logger,
            $this->coreParametersHelper,
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testGetLinksLineChartData(): void
    {
        $from         = new \DateTime('2019-03-02 12:30:00');
        $to           = new \DateTime('2019-04-02 12:30:00');
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(DBALQueryBuilder::class);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $chartData = $this->customItemXrefContactModel->getLinksLineChartData(
            $from,
            $to,
            $this->customItem
        );

        $this->assertCount(32, $chartData['labels']);
        $this->assertCount(32, $chartData['datasets'][0]['data']);
    }

    public function testGetPermissionBase(): void
    {
        $this->assertSame(
            'custom_objects:custom_items',
            $this->customItemXrefContactModel->getPermissionBase()
        );
    }
}
