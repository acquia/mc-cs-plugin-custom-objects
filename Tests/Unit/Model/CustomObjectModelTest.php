<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDbal;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomObjectModelTest extends TestCase
{
    private $customObject;
    private $customField;
    private $user;
    private $entityManager;
    private $queryBuilder;
    private $queryBuilderDbal;
    private $query;
    private $connection;
    private $statement;
    private $databasePlatform;
    private $customObjectRepository;
    private $customObjectPermissionProvider;
    private $userHelper;
    private $customFieldModel;
    private $dispatcher;
    private $translator;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->customObject                   = $this->createMock(CustomObject::class);
        $this->customField                    = $this->createMock(CustomField::class);
        $this->user                           = $this->createMock(User::class);
        $this->entityManager                  = $this->createMock(EntityManager::class);
        $this->queryBuilder                   = $this->createMock(QueryBuilder::class);
        $this->queryBuilderDbal               = $this->createMock(QueryBuilderDbal::class);
        $this->statement                      = $this->createMock(Statement::class);
        $this->query                          = $this->createMock(AbstractQuery::class);
        $this->connection                     = $this->createMock(Connection::class);
        $this->databasePlatform               = $this->createMock(MySqlPlatform::class);
        $this->customObjectRepository         = $this->createMock(CustomObjectRepository::class);
        $this->customObjectPermissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->userHelper                     = $this->createMock(UserHelper::class);
        $this->customFieldModel               = $this->createMock(CustomFieldModel::class);
        $this->dispatcher                     = $this->createMock(EventDispatcherInterface::class);
        $this->translator                     = $this->createMock(TranslatorInterface::class);
        $this->listModel                      = $this->createMock(ListModel::class);
        $this->customObjectModel              = new CustomObjectModel(
            $this->entityManager,
            $this->customObjectRepository,
            $this->customObjectPermissionProvider,
            $this->userHelper,
            $this->customFieldModel,
            $this->dispatcher,
            $this->listModel
        );

        $this->customObjectModel->setEntityManager($this->entityManager);
        $this->customObjectModel->setTranslator($this->translator);
        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('getDatabasePlatform')->willReturn($this->databasePlatform);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilderDbal);
        $this->queryBuilderDbal->method('execute')->willReturn($this->statement);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->userHelper->method('getUser')->willReturn($this->user);
    }

    public function testSaveNew(): void
    {
        $this->user->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('John Doe');

        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->customObject->expects($this->exactly(2))
            ->method('isNew')
            ->willReturn(true);

        $this->customObject->expects($this->once())
            ->method('getName')
            ->willReturn('Product');

        $this->customObject->expects($this->once())
            ->method('setAlias')
            ->with('product');

        $this->customObject->expects($this->exactly(3))
            ->method('getAlias')
            ->will($this->onConsecutiveCalls(null, 'product', 'product'));

        $this->customObject->expects($this->once())
            ->method('setCreatedBy')
            ->with($this->user);

        $this->customObject->expects($this->once())
            ->method('setCreatedByUser')
            ->with('John Doe');

        $this->customObject->expects($this->once())
            ->method('setDateAdded');

        $this->customObject->expects($this->once())
            ->method('setModifiedBy')->with($this->user);

        $this->customObject->expects($this->once())
            ->method('setModifiedByUser')
            ->with('John Doe');

        $this->customObject->expects($this->once())
            ->method('setDateModified');

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$this->customField]));

        $this->customFieldModel->expects($this->once())
            ->method('setMetadata')
            ->with($this->customField);

        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE, $this->isInstanceOf(CustomObjectEvent::class)],
                [CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE, $this->isInstanceOf(CustomObjectEvent::class)]
            );
        $this->entityManager->expects($this->once())->method('persist')->with($this->customObject);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame($this->customObject, $this->customObjectModel->save($this->customObject));
    }

    public function testSaveNewWhenAliasIsNotUnique(): void
    {
        $this->user->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('John Doe');

        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->customObject->expects($this->exactly(2))
            ->method('isNew')
            ->willReturn(true);

        $this->customObject->expects($this->once())
            ->method('getName')
            ->willReturn('Product');

        $this->customObject->method('getId')
            ->willReturn(123);

        $this->customObject->expects($this->exactly(2))
            ->method('setAlias')
            ->withConsecutive(
                ['product'],
                ['product1']
            );

        $this->customObject->expects($this->exactly(3))
            ->method('getAlias')
            ->will($this->onConsecutiveCalls(null, 'product', 'product'));

        $this->customObjectRepository->expects($this->exactly(2))
            ->method('checkAliasExists')
            ->withConsecutive(
                ['product', 123],
                ['product1', 123]
            )
            ->will($this->onConsecutiveCalls(true, false));

        $this->customObject->expects($this->once())
            ->method('setCreatedBy')
            ->with($this->user);

        $this->customObject->expects($this->once())
            ->method('setCreatedByUser')
            ->with('John Doe');

        $this->customObject->expects($this->once())
            ->method('setDateAdded');

        $this->customObject->expects($this->once())
            ->method('setModifiedBy')->with($this->user);

        $this->customObject->expects($this->once())
            ->method('setModifiedByUser')
            ->with('John Doe');

        $this->customObject->expects($this->once())
            ->method('setDateModified');

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$this->customField]));

        $this->customFieldModel->expects($this->once())
            ->method('setMetadata')
            ->with($this->customField);

        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE, $this->isInstanceOf(CustomObjectEvent::class)],
                [CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE, $this->isInstanceOf(CustomObjectEvent::class)]
            );
        $this->entityManager->expects($this->once())->method('persist')->with($this->customObject);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame($this->customObject, $this->customObjectModel->save($this->customObject));
    }

    public function testSaveEdit(): void
    {
        $this->user->expects($this->exactly(1))
            ->method('getName')
            ->willReturn('John Doe');

        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->customObject->expects($this->exactly(2))
            ->method('isNew')
            ->willReturn(false);

        $this->customObject->expects($this->never())
            ->method('getName');

        $this->customObject->expects($this->exactly(3))
            ->method('getAlias')
            ->will($this->onConsecutiveCalls('product', 'product', 'product'));

        $this->customObject->expects($this->never())
            ->method('setCreatedBy');

        $this->customObject->expects($this->once())
            ->method('setModifiedBy')->with($this->user);

        $this->customObject->expects($this->once())
            ->method('setModifiedByUser')
            ->with('John Doe');

        $this->customObject->expects($this->once())
            ->method('setDateModified');

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$this->customField]));

        $this->customFieldModel->expects($this->once())
            ->method('setMetadata')
            ->with($this->customField);

        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE, $this->isInstanceOf(CustomObjectEvent::class)],
                [CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE, $this->isInstanceOf(CustomObjectEvent::class)]
            );
        $this->entityManager->expects($this->once())->method('persist')->with($this->customObject);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame($this->customObject, $this->customObjectModel->save($this->customObject));
    }

    public function testDelete(): void
    {
        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn(34);

        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_DELETE, $this->isInstanceOf(CustomObjectEvent::class)]
            );

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->customObject);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->customObjectModel->delete($this->customObject);
    }

    public function testFetchEntity(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('getEntity')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('createFieldsSnapshot');

        $this->customObjectModel->fetchEntity(44);
    }

    public function testFetchEntityIfNotFound(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->customObject->expects($this->never())
            ->method('createFieldsSnapshot');

        $this->expectException(NotFoundException::class);

        $this->customObjectModel->fetchEntity(44);
    }

    public function testFetchEntityByAlias(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['alias' => 'alias1'])
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('createFieldsSnapshot');

        $this->customObjectModel->fetchEntityByAlias('alias1');
    }

    public function testFetchAllPublishedEntitiesForCli(): void
    {
        $expectedQuery = [
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => CustomObject::TABLE_ALIAS.'.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ];

        $this->customObjectPermissionProvider->expects($this->never())
            ->method('isGranted');

        $this->customObjectRepository->expects($this->once())
            ->method('getEntities')
            ->with($expectedQuery)
            ->willReturn(['list of custom objects here']);

        $this->customObjectModel->fetchAllPublishedEntities();
    }

    public function testFetchAllPublishedEntitiesForLimitedAccessUser(): void
    {
        $expectedQuery = [
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => CustomObject::TABLE_ALIAS.'.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                    [
                        'column' => CustomObject::TABLE_ALIAS.'.createdBy',
                        'expr'   => 'eq',
                        'value'  => 532,
                    ],
                ],
            ],
        ];

        $this->user->method('getId')
            ->willReturn(532);

        $this->customObjectPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->with('viewother')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->customObjectRepository->expects($this->once())
            ->method('getEntities')
            ->with($expectedQuery)
            ->willReturn(['list of custom objects here']);

        $this->customObjectModel->fetchAllPublishedEntities();
    }

    public function testFetchEntitiesForLimitedAccessUser(): void
    {
        $expectedQuery = [
            'filter' => [
                'force' => [
                    [
                        'column' => CustomObject::TABLE_ALIAS.'.createdBy',
                        'expr'   => 'eq',
                        'value'  => 532,
                    ],
                ],
            ],
        ];

        $this->customObjectPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->with('viewother')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->customObjectRepository->expects($this->once())
            ->method('getEntities')
            ->with($expectedQuery)
            ->willReturn(['list of custom objects here']);

        $this->user->method('getId')
            ->willReturn(532);

        $this->customObjectModel->fetchEntities();
    }

    public function testFetchEntityByAliasIfNotFound(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['alias' => 'alias1'])
            ->willReturn(null);

        $this->customObject->expects($this->never())
            ->method('createFieldsSnapshot');

        $this->expectException(NotFoundException::class);

        $this->customObjectModel->fetchEntityByAlias('alias1');
    }

    public function testGetTableData(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');

        $this->customObjectPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->customObjectModel->getTableData($tableConfig);
    }

    public function testGetCountForTable(): void
    {
        $tableConfig = new TableConfig(10, 3, 'column');
        $expr        = $this->createMock(Expr::class);

        $tableConfig->addParameter('search', 'Unicorn');

        $this->customObjectPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomObject::TABLE_ALIAS],
                ['the select count expr']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setMaxResults')
            ->withConsecutive([10], [1]);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setFirstResult')
            ->withConsecutive([20], [0]);

        $this->queryBuilder->expects($this->once())
            ->method('resetDQLPart')
            ->with('orderBy');

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);

        $expr->expects($this->once())
            ->method('countDistinct')
            ->with('CustomObject')
            ->willReturn('the select count expr');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(CustomObject::class, CustomObject::TABLE_ALIAS);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                [CustomObject::TABLE_ALIAS.'.name LIKE %:search%'],
                [CustomObject::TABLE_ALIAS.'.createdBy', 22]
            );

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('search', 'Unicorn');

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(4);

        $this->assertSame(4, $this->customObjectModel->getCountForTable($tableConfig));
    }

    public function testGetPermissionBase(): void
    {
        $this->assertSame('custom_objects:custom_objects', $this->customObjectModel->getPermissionBase());
    }

    public function testGetItemsLineChartData(): void
    {
        $from = new \DateTime('2019-05-12T17:16:00+00');
        $to   = new \DateTime('2019-06-12T17:16:00+00');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.object.created.items')
            ->willReturn('Items Created');

        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $chartData = $this->customObjectModel->getItemsLineChartData(
            $from,
            $to,
            $this->customObject
        );

        $this->assertCount(32, $chartData['labels']);
        $this->assertCount(32, $chartData['datasets'][0]['data']);
        $this->assertSame('Items Created', $chartData['datasets'][0]['label']);
    }

    public function testRemoveCustomFieldById(): void
    {
        $customField5 = $this->createMock(CustomField::class);
        $customField6 = $this->createMock(CustomField::class);
        $customField5->method('getId')->willReturn(5);
        $customField6->method('getId')->willReturn(6);

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$customField5, $customField6]));

        $this->customObject->expects($this->once())
            ->method('removeCustomField')
            ->with($customField5);

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($customField5)
            ->willReturn(true);

        $this->customFieldModel->expects($this->once())
            ->method('deleteEntity')
            ->with($customField5);

        $this->customObjectModel->removeCustomFieldById($this->customObject, 5);
    }

    public function testIgnoreRemoveNotExistingCustomFieldById(): void
    {
        $customField1 = $this->createMock(CustomField::class);
        $customField1->method('getId')->willReturn(1);

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$customField1]));

        $this->customObject->expects($this->once())
            ->method('removeCustomField')
            ->with($customField1);

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($customField1)
            ->willReturn(false);

        $this->customFieldModel->expects($this->never())
            ->method('deleteEntity');

        $this->customObjectModel->removeCustomFieldById($this->customObject, 1);
    }

    public function testGetMasterCustomObjects()
    {
        $customObject1 = new CustomObject();
        $customObject1->setType(CustomObject::TYPE_MASTER);
        $customObject1->setAlias('master');

        $customObject2 = new CustomObject();
        $customObject2->setType(CustomObject::TYPE_RELATIONSHIP);
        $customObject2->setAlias('relationship');

        // Leave type = null
        $customObject3 = new CustomObject();
        $customObject3->setAlias('null');

        $customObjects = [
            $customObject1, $customObject2, $customObject3,
        ];

        $this->customObjectRepository->method('getEntities')->willReturn($customObjects);

        $masterObjects = $this->customObjectModel->getMasterCustomObjects();

        $this->assertEquals(2, count($masterObjects));
        $this->assertEquals($masterObjects[0]->getAlias(), 'master');
        $this->assertEquals($masterObjects[2]->getAlias(), 'null');
    }
}
