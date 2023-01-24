<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CustomFieldOptionFunctionalTest extends AbstractApiPlatformFunctionalTest
{
    public function testCustomFieldOptionCRUD(): void
    {
        foreach ($this->getCRUDProvider() as $parameters) {
            $this->runTestCustomFieldOptionCRUD(...$parameters);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function runTestCustomFieldOptionCRUD(
        array $permissions,
        string $httpCreated,
        string $httpRetrieved,
        ?string $retrievedLabel,
        string $httpUpdated,
        ?string $updatedLabel,
        string $httpDeleted
    ): void {
        // USER
        $user = $this->getUser();
        // OBJECTS
        $customObject = $this->createCustomObject();
        $customField  = $this->createField($customObject, $user);
        // PERMISSION
        $this->setPermission($user, 'custom_objects:custom_fields', $permissions);
        // CREATE
        $payloadCreate        = $this->getCreatePayload('/api/v2/custom_fields/'.$customField->getId());
        $clientCreateResponse = $this->createEntity('custom_field_options', $payloadCreate);
        $this->assertEquals($httpCreated, $clientCreateResponse->getStatusCode());
        if (!property_exists(json_decode($clientCreateResponse->getContent()), '@id')) {
            return;
        }
        // GET ID OF ENTITY
        $createdId = json_decode($clientCreateResponse->getContent())->{'@id'};
        // RETRIEVE
        $clientRetrieveResponse = $this->retrieveEntity($createdId);
        $this->assertEquals($httpRetrieved, $clientRetrieveResponse->getStatusCode());
        if ($retrievedLabel) {
            $this->assertEquals($retrievedLabel, json_decode($clientRetrieveResponse->getContent())->label);
        }
        // UPDATE
        $payloadUpdate        = $this->getEditPayload();
        $clientUpdateResponse = $this->updateEntity($createdId, $payloadUpdate);
        $this->assertEquals($httpUpdated, $clientUpdateResponse->getStatusCode());
        if ($updatedLabel) {
            $this->assertEquals($updatedLabel, json_decode($clientUpdateResponse->getContent())->label);
        }
        // DELETE
        $clientDeleteResponse = $this->deleteEntity($createdId);
        $this->assertEquals($httpDeleted, $clientDeleteResponse->getStatusCode());
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

    private function createField(CustomObject $customObject, User $user): CustomField
    {
        $translatorMock             = $this->createMock(TranslatorInterface::class);
        $filterOperatorProviderMock = $this->createMock(FilterOperatorProviderInterface::class);
        $csvHelperMock              = $this->createMock(CsvHelper::class);
        $customFieldType            = new MultiselectType($translatorMock, $filterOperatorProviderMock, $csvHelperMock);
        $customField                = new CustomField();
        $customField->setLabel('Test custom field');
        $customField->setAlias('test_custom_field');
        $customField->setCustomObject($customObject);
        $customField->setType('multiselect');
        $customField->setTypeObject($customFieldType);
        $customField->setDefaultValue('custom field text');
        $customField->setCreatedBy($user);
        $this->em->persist($customField);
        $this->em->flush();

        return $customField;
    }

    private function getCreatePayload(string $customField): array
    {
        return [
            'label'        => 'New Custom Field Option',
            'value'        => 'custom_field_option',
            'order'        => 0,
            'customField'  => $customField,
        ];
    }

    private function getEditPayload(): array
    {
        return [
            'label'        => 'Edited Custom Field Option',
            'value'        => 'custom_field_option',
            'order'        => 0,
        ];
    }

    /**
     * @see self::testCustomFieldCRUD()
     *
     * @return array|array[]
     */
    private function getCRUDProvider(): array
    {
        return [
            'all_ok' => [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'New Custom Field Option',
                    Response::HTTP_OK,
                    'Edited Custom Field Option',
                    Response::HTTP_NO_CONTENT,
                ],
            'no_delete' => [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'New Custom Field Option',
                    Response::HTTP_OK,
                    'Edited Custom Field Option',
                    Response::HTTP_FORBIDDEN,
                ],
            'no_update' => [
                    ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'New Custom Field Option',
                    Response::HTTP_FORBIDDEN,
                    null,
                    Response::HTTP_NO_CONTENT,
                ],
            'no_create' => [
                    ['viewown', 'viewother', 'editown', 'editother', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_FORBIDDEN,
                    '',
                    null,
                    '',
                    null,
                    '',
                ],
        ];
    }
}
