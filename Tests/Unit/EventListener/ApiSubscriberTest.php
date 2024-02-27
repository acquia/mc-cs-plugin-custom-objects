<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use InvalidArgumentException;
use Mautic\ApiBundle\Event\ApiEntityEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ApiSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $configProvider;

    private $customObjectModel;

    private $customItemModel;

    private $apiEntityEvent;

    private $request;

    /**
     * @var ApiSubscriber
     */
    private $apiSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider    = $this->createMock(ConfigProvider::class);
        $this->customObjectModel = $this->createMock(CustomObjectModel::class);
        $this->customItemModel   = $this->createMock(CustomItemModel::class);
        $this->apiEntityEvent    = $this->createMock(ApiEntityEvent::class);
        $this->request           = $this->createMock(Request::class);
        $this->apiSubscriber     = new ApiSubscriber(
            $this->configProvider,
            $this->customObjectModel,
            $this->customItemModel
        );

        $this->apiEntityEvent->method('getRequest')->willReturn($this->request);
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([]);

        $this->apiEntityEvent->expects($this->never())
            ->method('getEntity');

        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testGetCustomObjectsFromContactCreateRequestForUnsupportedApiEndpoint(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([]);

        $this->request->method('getPathInfo')->willReturn('/api/unicorn');

        $this->apiEntityEvent->expects($this->never())
            ->method('getEntity');

        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testGetCustomObjectsFromContactCreateRequestWithoutCustomObjects(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn(['email' => 'john@doe.email']);

        $this->apiEntityEvent->expects($this->never())
            ->method('getEntity');

        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testValidateCustomObjectsInContactRequestWhenCustomObjectNotFoundByAlias(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => 'object-1-alias',
                            'data'  => [[]],
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->with('object-1-alias')
            ->will($this->throwException(new NotFoundException('Custom Object not found')));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testValidateCustomObjectsInContactRequestWhenCustomObjectNotFoundById(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'id'   => 123,
                            'data' => [[]],
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(123)
            ->will($this->throwException(new NotFoundException('Custom Object not found')));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testValidateCustomObjectsInContactRequestWhenCustomObjectDoesNotHaveAnyIdentifier(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'data' => [[]],
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testValidateCustomObjectsInContactRequestWhenCustomItemNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => 'object-1-alias',
                            'data'  => [
                                [
                                    'id' => 123,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn(new CustomObject());

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Custom Item not found')));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testValidateCustomObjectsInContactRequestForCustomItemEdit(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => 'object-1-alias',
                            'data'  => [
                                [
                                    'id'         => 123,
                                    'name'       => 'Test Item',
                                    'attributes' => [
                                        'sku'   => 'd2345f',
                                        'price' => 237,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn(new CustomObject());

        $customItem = $this->createMock(CustomItem::class);
        $skuValue   = $this->createMock(CustomFieldValueText::class);

        $skuValue->expects($this->once())
            ->method('setValue')
            ->with('d2345f');

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->with($customItem)
            ->willReturn($customItem);

        $customItem->expects($this->once())
            ->method('setName')
            ->with('Test Item');

        $customItem->expects($this->exactly(2))
            ->method('findCustomFieldValueForFieldAlias')
            ->withConsecutive(['sku'], ['price'])
            ->will($this->onConsecutiveCalls(
                $skuValue,
                $this->throwException(new NotFoundException('Field value for price not found'))
            ));

        $customItem->expects($this->once())
            ->method('createNewCustomFieldValueByFieldAlias')
            ->with('price', 237);

        $customItem->expects($this->once())
            ->method('setDefaultValuesForMissingFields');

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($customItem, true);

        $this->apiSubscriber->validateCustomObjectsInContactRequest($this->apiEntityEvent);
    }

    public function testSaveCustomObjectsInContactRequestForNewCustomItem(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->request->method('getPathInfo')->willReturn('/api/contacts/new');

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntityRequestParameters')
            ->willReturn([
                'email'         => 'john@doe.email',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => 'object-1-alias',
                            'data'  => [
                                [
                                    'name'       => 'Test Item',
                                    'attributes' => [
                                        'sku'   => 'd2345f',
                                        'price' => 237,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'alias' => 'no-data-is-ignored',
                        ],
                    ],
                ],
            ]);

        $this->apiEntityEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Lead());

        $customObject = new CustomObject();
        $skuField     = new CustomField();
        $priceField   = new CustomField();

        $skuField->setId(21);
        $skuField->setAlias('sku');
        $skuField->setTypeObject(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );
        $priceField->setId(34);
        $priceField->setAlias('price');
        $priceField->setTypeObject(
            new IntType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $customObject->addCustomField($skuField);
        $customObject->addCustomField($priceField);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn($customObject);

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->callback(function (CustomItem $customItem) {
                $this->assertSame('Test Item', $customItem->getName());
                $skuValue = $customItem->findCustomFieldValueForFieldAlias('sku');
                $priceValue = $customItem->findCustomFieldValueForFieldAlias('price');
                $this->assertSame('d2345f', $skuValue->getValue());
                $this->assertSame(237, $priceValue->getValue());

                return true;
            }), false);

        $this->apiSubscriber->saveCustomObjectsInContactRequest($this->apiEntityEvent);
    }
}
