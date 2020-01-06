<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use MauticPlugin\CustomObjectsBundle\EventListener\SerializerSubscriber;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\HttpFoundation\Request;
use Mautic\PageBundle\Entity\Page;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use Symfony\Component\Translation\TranslatorInterface;
use JMS\Serializer\EventDispatcher\Events;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDate;

class SerializerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $configProvider;

    private $customItemXrefContactRepository;

    private $customItemModel;

    private $requestStack;

    private $request;

    private $objectEvent;

    /**
     * @var SerializerSubscriber
     */
    private $serializerSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider                  = $this->createMock(ConfigProvider::class);
        $this->customItemXrefContactRepository = $this->createMock(CustomItemXrefContactRepository::class);
        $this->customItemModel                 = $this->createMock(CustomItemModel::class);
        $this->requestStack                    = $this->createMock(RequestStack::class);
        $this->objectEvent                     = $this->createMock(ObjectEvent::class);
        $this->request                         = $this->createMock(Request::class);
        $this->serializerSubscriber            = new SerializerSubscriber(
            $this->configProvider,
            $this->customItemXrefContactRepository,
            $this->customItemModel,
            $this->requestStack
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                [
                    'event'  => Events::POST_SERIALIZE,
                    'method' => 'addCustomItemsIntoContactResponse',
                ],
            ],
            $this->serializerSubscriber->getSubscribedEvents()
        );
    }

    public function testAddCustomItemsIntoContactResponseWithoutAnyReuqest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->objectEvent->expects($this->never())
            ->method('getObject');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWithoutIncludeCustomObjectsFlagInTheRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(false);

        $this->objectEvent->expects($this->never())
            ->method('getObject');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWithNotContactEntity(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn(new Page());

        $this->configProvider->expects($this->never())
            ->method('pluginIsEnabled');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWhenPluginDisabled(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn(new Lead());

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->customItemXrefContactRepository->expects($this->never())
            ->method('getCustomObjectsRelatedToContact');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWhenNoRelatedObjects(): void
    {
        $contact = new Lead();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn($contact);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customItemXrefContactRepository->expects($this->once())
            ->method('getCustomObjectsRelatedToContact')
            ->with($contact, $this->callback(function (TableConfig $tableConfig): bool {
                $this->assertSame(10, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame(CustomObject::TABLE_ALIAS.'.dateAdded', $tableConfig->getOrderBy());
                $this->assertSame('DESC', $tableConfig->getOrderDirection());

                return true;
            }))
            ->willReturn([]);

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponse(): void
    {
        $contact         = $this->createMock(Lead::class);
        $customItem      = $this->createMock(CustomItem::class);
        $customFieldText = $this->createMock(CustomField::class);
        $customFieldDate = $this->createMock(CustomField::class);
        $context         = $this->createMock(Context::class);
        $visitor         = $this->createMock(JsonSerializationVisitor::class);

        $contact->method('getId')->willReturn(345);
        $customFieldDate->method('getAlias')->willReturn('text-field-1');
        $customFieldText->method('getAlias')->willReturn('text-field-2');
        $customFieldDate->method('getTypeObject')->willReturn(new DateType($this->createMock(TranslatorInterface::class)));
        $customFieldText->method('getTypeObject')->willReturn(new TextType($this->createMock(TranslatorInterface::class)));
        $customItem->method('getId')->willReturn(567);
        $customItem->method('getName')->willReturn('Test Item');
        $customItem->method('getDateAdded')->willReturn(new \DateTimeImmutable('2019-06-12T13:24:00+00:00'));
        $customItem->method('getDateModified')->willReturn(new \DateTimeImmutable('2019-06-12T13:24:00+00:00'));
        $customItem->method('getCustomFieldValues')->willReturn(new ArrayCollection([
            new CustomFieldValueDate($customFieldDate, $customItem, new \DateTimeImmutable('2019-06-12T00:00:00+00:00')),
            new CustomFieldValueText($customFieldText, $customItem, 'a text value'),
        ]));

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn($contact);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customItemXrefContactRepository->expects($this->once())
            ->method('getCustomObjectsRelatedToContact')
            ->with($contact, $this->callback(function (TableConfig $tableConfig): bool {
                $this->assertSame(10, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame(CustomObject::TABLE_ALIAS.'.dateAdded', $tableConfig->getOrderBy());
                $this->assertSame('DESC', $tableConfig->getOrderDirection());

                return true;
            }))
            ->willReturn([['id' => 123, 'alias' => 'products']]);

        $this->customItemModel->expects($this->once())
            ->method('getTableData')
            ->with($this->callback(function (TableConfig $tableConfig): bool {
                $this->assertSame(10, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame(CustomItem::TABLE_ALIAS.'.dateAdded', $tableConfig->getOrderBy());
                $this->assertSame('DESC', $tableConfig->getOrderDirection());
                $this->assertSame(123, $tableConfig->getParameter('customObjectId'));
                $this->assertSame(345, $tableConfig->getParameter('filterEntityId'));
                $this->assertSame('contact', $tableConfig->getParameter('filterEntityType'));

                return true;
            }))
            ->willReturn([$customItem]);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->with($customItem);

        $this->objectEvent->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $context->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

        $expectedPayload = [
            'data' => [
                [
                    'id'    => 123,
                    'alias' => 'products',
                    'data'  => [
                        [
                            'id'           => 567,
                            'name'         => 'Test Item',
                            'language'     => null,
                            'category'     => null,
                            'isPublished'  => null,
                            'dateAdded'    => '2019-06-12T13:24:00+00:00',
                            'dateModified' => '2019-06-12T13:24:00+00:00',
                            'createdBy'    => null,
                            'modifiedBy'   => null,
                            'attributes'   => [
                                'text-field-1' => '2019-06-12',
                                'text-field-2' => 'a text value',
                            ],
                        ],
                    ],
                    'meta' => [
                        'page' => [
                            'number' => 1,
                            'size'   => 10,
                        ],
                        'sort' => '-dateAdded',
                    ],
                ],
            ],
            'meta' => [
                'page' => [
                    'number' => 1,
                    'size'   => 10,
                ],
                'sort' => '-dateAdded',
            ],
        ];

        $visitor->expects($this->once())
            ->method('addData')
            ->with('customObjects', $expectedPayload);

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }
}
