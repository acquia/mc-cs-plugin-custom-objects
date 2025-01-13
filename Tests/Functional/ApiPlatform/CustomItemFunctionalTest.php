<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CustomItemFunctionalTest extends AbstractApiPlatformFunctionalTest
{
    private CustomItemModel $customItemModel;

    public function setUp(): void
    {
        $this->configParams['custom_objects_enabled'] = true;

        parent::setUp();

        $customItemModel = $this->getContainer()->get('mautic.custom.model.item');
        \assert($customItemModel instanceof CustomItemModel);

        $this->customItemModel = $customItemModel;
    }

    /**
     * @dataProvider getCustomItemsDataProvider
     *
     * @param array<int, string> $permissions
     */
    public function testGetCustomItem(array $permissions, int $expectedResponse): void
    {
        $customItem = $this->createCustomItem($permissions);
        $response   = $this->retrieveEntity('/api/v2/custom_items/'.$customItem->getId());
        $json       = json_decode($response->getContent(), true);

        self::assertEquals($expectedResponse, $response->getStatusCode());

        if (Response::HTTP_FORBIDDEN == $expectedResponse) {
            $this->assertAccessForbiddenContent($json);

            return;
        }

        $this->assertSuccessContent($json, $customItem);
    }

    /**
     * @return iterable<int, mixed>
     */
    public function getCustomItemsDataProvider(): iterable
    {
        yield [['viewother'], Response::HTTP_OK];

        yield [['editother'], Response::HTTP_OK];

        yield [['deleteother'], Response::HTTP_OK];

        yield [['publishother'], Response::HTTP_OK];

        yield [[], Response::HTTP_FORBIDDEN];

        yield [['viewown', 'editown', 'create', 'deleteown', 'publishown'], Response::HTTP_FORBIDDEN];
    }

    /**
     * @dataProvider postCustomItemsDataProvider
     *
     * @param array<int, string> $permissions
     */
    public function testPostCustomItem(array $permissions, int $expectedResponse): void
    {
        $customObject = $this->createCustomObject();
        $category     = $this->createCategory();
        $customField  = $this->createCustomField($customObject);
        $user         = $this->getUser();

        $this->setPermission($user, 'custom_objects:'.$customObject->getId(), $permissions);

        $response = $this->createEntity('custom_items', [
            'name'         => 'Custom Item',
            'customObject' => '/api/v2/custom_objects/'.$customObject->getId(),
            'language'     => 'en',
            'category'     => '/api/v2/categories/'.$category->getId(),
            'fieldValues'  => [
                [
                    'id'    => '/api/v2/custom_fields/'.$customField->getId(),
                    'value' => 'value',
                ],
            ],
        ]);
        $json     = json_decode($response->getContent(), true);

        self::assertEquals($expectedResponse, $response->getStatusCode());

        if (Response::HTTP_FORBIDDEN == $expectedResponse) {
            $this->assertAccessForbiddenContent($json);

            return;
        }

        $this->em->clear();
        $customItem = $this->em->getRepository(CustomItem::class)->find($json['id']);
        $this->customItemModel->populateCustomFields($customItem);
        $this->assertSuccessContent($json, $customItem);
    }

    /**
     * @return iterable<int, mixed>
     */
    public function postCustomItemsDataProvider(): iterable
    {
        yield [['create'], Response::HTTP_CREATED];

        yield [[], Response::HTTP_FORBIDDEN];

        yield [['viewown', 'viewother', 'editown', 'editother', 'deleteown', 'deleteother', 'publishown', 'publishother'], Response::HTTP_FORBIDDEN];
    }

    /**
     * @dataProvider putCustomItemsDataProvider
     *
     * @param array<int, string> $permissions
     */
    public function testPutCustomItem(array $permissions, int $expectedResponse): void
    {
        $customItem = $this->createCustomItem($permissions);
        $response   = $this->updateEntity('/api/v2/custom_items/'.$customItem->getId(), [
            'name'         => 'Custom Item Edited',
            'fieldValues'  => [
                [
                    'id'    => '/api/v2/custom_fields/'.$customItem->getCustomObject()->getCustomFields()->first()->getId(),
                    'value' => 'test3',
                ],
            ],
        ]);
        $json       = json_decode($response->getContent(), true);

        self::assertEquals($expectedResponse, $response->getStatusCode());

        if (Response::HTTP_FORBIDDEN == $expectedResponse) {
            $this->assertAccessForbiddenContent($json);

            return;
        }

        $this->em->clear();
        $customItem = $this->em->getRepository(CustomItem::class)->find($json['id']);
        $this->customItemModel->populateCustomFields($customItem);
        $this->assertSuccessContent($json, $customItem);
    }

    /**
     * @return iterable<int, mixed>
     */
    public function putCustomItemsDataProvider(): iterable
    {
        yield [['editother'], Response::HTTP_OK];

        yield [['deleteother'], Response::HTTP_OK];

        yield [[], Response::HTTP_FORBIDDEN];

        yield [['viewown', 'viewother', 'editown', 'create', 'deleteown', 'publishown', 'publishother'], Response::HTTP_FORBIDDEN];
    }

    /**
     * @dataProvider putCustomItemsDataProvider
     *
     * @param array<int, string> $permissions
     */
    public function testPatchCustomItem(array $permissions, int $expectedResponse): void
    {
        $customItem = $this->createCustomItem($permissions);
        $response   = $this->patchEntity('/api/v2/custom_items/'.$customItem->getId(),
            [
                'fieldValues'  => [
                    [
                        'id'    => '/api/v2/custom_fields/'.$customItem->getCustomObject()->getCustomFields()->first()->getId(),
                        'value' => 'test2',
                    ],
                ],
            ]);
        $json       = json_decode($response->getContent(), true);

        self::assertEquals($expectedResponse, $response->getStatusCode());

        if (Response::HTTP_FORBIDDEN == $expectedResponse) {
            $this->assertAccessForbiddenContent($json);

            return;
        }

        $this->em->clear();
        $customItem = $this->em->getRepository(CustomItem::class)->find($json['id']);
        $this->customItemModel->populateCustomFields($customItem);
        $this->assertSuccessContent($json, $customItem);
    }

    /**
     * @dataProvider deleteCustomItemsDataProvider
     *
     * @param array<int, string> $permissions
     */
    public function testDeleteCustomItem(array $permissions, int $expectedResponse): void
    {
        $customItem = $this->createCustomItem($permissions);
        $response   = $this->deleteEntity('/api/v2/custom_items/'.$customItem->getId());
        $json       = json_decode($response->getContent(), true);

        self::assertEquals($expectedResponse, $response->getStatusCode());

        if (Response::HTTP_FORBIDDEN == $expectedResponse) {
            $this->assertAccessForbiddenContent($json);

            return;
        }

        $this->em->clear();
        $customItem = $this->em->getRepository(CustomItem::class)->find($customItem->getId());
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNull($json);
        self::assertNull($customItem);
    }

    /**
     * @return iterable<int, mixed>
     */
    public function deleteCustomItemsDataProvider(): iterable
    {
        yield [['deleteother'], Response::HTTP_NO_CONTENT];

        yield [[], Response::HTTP_FORBIDDEN];

        yield [['viewown', 'viewother', 'editown', 'create', 'deleteown', 'editother', 'publishown', 'publishother'], Response::HTTP_FORBIDDEN];
    }

    private function createCustomObject(): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular('Test custom object');
        $customObject->setNamePlural('Test custom objects');
        $customObject->setAlias('test_custom_object');
        $this->em->persist($customObject);
        $this->em->flush();

        return $customObject;
    }

    /**
     * @param array<int, string> $permissions
     */
    private function createCustomItem(array $permissions): CustomItem
    {
        $customObject = $this->createCustomObject();
        $category     = $this->createCategory();
        $customField  = $this->createCustomField($customObject);
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Custom Item');
        $customItem->setLanguage('en');
        $customItem->setCategory($category);
        $customFieldValue = new CustomFieldValueText($customField, $customItem, 'value');
        $customItem->addCustomFieldValue($customFieldValue);

        $this->em->persist($customItem);
        $this->em->flush();

        $user = $this->getUser();
        $this->setPermission($user, 'custom_objects:'.$customObject->getId(), $permissions);

        return $customItem;
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setTitle('Test Category');
        $category->setDescription('Test Category Description');
        $category->setAlias('test_category');
        $category->setBundle('test_bundle');
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function createCustomField(CustomObject $customObject): CustomField
    {
        $translatorMock             = $this->createMock(TranslatorInterface::class);
        $filterOperatorProviderMock = $this->createMock(FilterOperatorProviderInterface::class);
        $customFieldType            = new TextType($translatorMock, $filterOperatorProviderMock);
        $customField                = new CustomField();
        $customField->setLabel('Test Custom Field');
        $customField->setType('text');
        $customField->setTypeObject($customFieldType);
        $customField->setAlias('test_custom_field');
        $customField->setCustomObject($customObject);
        $this->em->persist($customField);
        $customObject->setCustomFields(new ArrayCollection([$customField]));
        $this->em->persist($customObject);
        $this->em->flush();

        return $customField;
    }

    /**
     * @param array<string, mixed> $json
     */
    private function assertAccessForbiddenContent(array $json): void
    {
        self::assertEquals($json['@context'], '/api/v2/contexts/Error');
        self::assertEquals($json['@type'], 'hydra:Error');
        self::assertEquals($json['hydra:title'], 'An error occurred');
        self::assertEquals($json['hydra:description'], 'Access Denied.');
        self::assertCount(4, $json);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function assertSuccessContent(array $json, CustomItem $customItem): void
    {
        self::assertEquals($json['@context'], '/api/v2/contexts/custom_items');
        self::assertEquals($json['@id'], '/api/v2/custom_items/'.$customItem->getId());
        self::assertEquals($json['@type'], 'custom_items');
        self::assertEquals($json['id'], $customItem->getId());
        self::assertEquals($json['name'], $customItem->getName());
        self::assertEquals($json['customObject'], '/api/v2/custom_objects/'.$customItem->getCustomObject()->getId());
        self::assertEquals($json['language'], 'en');
        self::assertEquals($json['category'], '/api/v2/categories/'.$customItem->getCategory()->getId());
        self::assertEquals($json['fieldValues'][0]['id'], '/api/v2/custom_fields/'.$customItem->getCustomFieldValues()->first()->getId());
        self::assertEquals($json['fieldValues'][0]['value'], $customItem->getCustomFieldValues()->first()->getValue());
        self::assertCount(9, $json);
        self::assertCount(1, $json['fieldValues']);
    }
}
