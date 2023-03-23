<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Response;

final class CustomFieldFunctionalTest extends AbstractApiPlatformFunctionalTest
{
    public function testCustomFieldCRUD(): void
    {
        foreach ($this->getCRUDProvider() as $parameters) {
            $this->runTestCustomFieldCRUD(...$parameters);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function runTestCustomFieldCRUD(
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
        // OBJECT
        $customObject = $this->createCustomObject();
        // PERMISSION
        $this->setPermission($user, 'custom_objects:custom_fields', $permissions);
        // CREATE
        $payloadCreate        = $this->getCreatePayload('/api/v2/custom_objects/'.$customObject->getId());
        $clientCreateResponse = $this->createEntity('custom_fields', $payloadCreate);
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

    private function getCreatePayload(string $customObject): array
    {
        return [
            'label'        => 'New Custom Field',
            'alias'        => 'custom_field_alias',
            'type'         => 'text',
            'customObject' => $customObject,
            'order'        => 0,
            'required'     => true,
            'defaultValue' => 'string',
            'params'       => [
                'string1',
                'string2',
            ],
            'isPublished'        => false,
            'isUniqueIdentifier' => false,
        ];
    }

    private function getEditPayload(): array
    {
        return [
            'label'        => 'Edited Custom Field',
            'type'         => 'text',
            'order'        => 1,
            'required'     => false,
            'defaultValue' => 'default',
            'params'       => [
                'string1',
                'string2',
            ],
            'isPublished'        => true,
            'isUniqueIdentifier' => true,
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
                    'New Custom Field',
                    Response::HTTP_OK,
                    'Edited Custom Field',
                    Response::HTTP_NO_CONTENT,
                ],
            'no_delete' => [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'New Custom Field',
                    Response::HTTP_OK,
                    'Edited Custom Field',
                    Response::HTTP_FORBIDDEN,
                ],
            'no_update' => [
                    ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'New Custom Field',
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

    public function testCustomFieldWithOptionsCRUD(): void
    {
        foreach ($this->getCRUDWithOptionsProvider() as $parameters) {
            $this->runTestCustomFieldWithOptionsCRUD(...$parameters);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function runTestCustomFieldWithOptionsCRUD(
        array $permissions,
        string $httpCreated,
        string $httpRetrieved,
        ?string $retrievedLabel,
        ?int $retrievedOptionsCount,
        ?array $retrievedOptions,
        ?string $retrievedDeafaultValue,
        string $httpUpdated,
        ?string $updatedLabel,
        ?int $updatedOptionsCount,
        ?array $updatedOptions,
        ?string $updatedDeafaultValue,
        string $httpDeleted
    ): void {
        // USER
        $user = $this->getUser();
        // OBJECT
        $customObject = $this->createCustomObject();
        // PERMISSION
        $this->setPermission($user, 'custom_objects:custom_fields', $permissions);
        // CREATE
        $payloadCreate        = $this->getCreateWithOptionsPayload('/api/v2/custom_objects/'.$customObject->getId());
        $clientCreateResponse = $this->createEntity('custom_fields', $payloadCreate);
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
            $contentArray = json_decode($clientRetrieveResponse->getContent(), true);
            $this->assertEquals($retrievedLabel, $contentArray['label']);
            $this->assertEquals($retrievedOptionsCount, count($contentArray['options']));
            $this->assertSame($retrievedOptions[0], $contentArray['options'][0]['label']);
            $this->assertSame($retrievedOptions[1], $contentArray['options'][1]['label']);
            $this->assertEquals($retrievedDeafaultValue, $contentArray['defaultValue']);
        }
        // UPDATE
        $payloadUpdate        = $this->getEditWithOptionsPayload();
        $clientUpdateResponse = $this->updateEntity($createdId, $payloadUpdate);
        $this->assertEquals($httpUpdated, $clientUpdateResponse->getStatusCode());
        if ($updatedLabel) {
            $contentUpdateArray = json_decode($clientUpdateResponse->getContent(), true);
            $this->assertEquals($updatedLabel, $contentUpdateArray['label']);
            $this->assertEquals($updatedOptionsCount, count($contentUpdateArray['options']));
            $this->assertSame($updatedOptions[0], $contentUpdateArray['options'][2]['label']);
            $this->assertSame($updatedOptions[1], $contentUpdateArray['options'][3]['label']);
            $this->assertEquals($updatedDeafaultValue, $contentUpdateArray['defaultValue']);
        }
        // DELETE
        $clientDeleteResponse = $this->deleteEntity($createdId);
        $this->assertEquals($httpDeleted, $clientDeleteResponse->getStatusCode());
    }

    private function getCreateWithOptionsPayload(string $customObject): array
    {
        return [
            'label'        => 'New Custom Field',
            'alias'        => 'custom_field_alias',
            'type'         => 'multiselect',
            'customObject' => $customObject,
            'order'        => 0,
            'required'     => true,
            'defaultValue' => 'new1',
            'options'      => [
                [
                    'label' => 'new1',
                    'value' => 'new1',
                ],
                [
                    'label' => 'new2',
                    'value' => 'new2',
                ],
            ],
            'params'       => [
                'string1',
                'string2',
            ],
            'published'    => false,
        ];
    }

    private function getEditWithOptionsPayload(): array
    {
        return [
            'label'        => 'Edited Custom Field',
            'defaultValue' => 'edit2',
            'options'      => [
                [
                    'label' => 'edit1',
                    'value' => 'edit1',
                ],
                [
                    'label' => 'edit2',
                    'value' => 'edit2',
                ],
            ],
        ];
    }

    /**
     * @see self::testCustomFieldWithOptionsCRUD()
     *
     * @return array|array[]
     */
    private function getCRUDWithOptionsProvider(): array
    {
        return [
            'all_ok' => [
                ['viewown', 'viewother', 'editown', 'editother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                Response::HTTP_CREATED,
                Response::HTTP_OK,
                'New Custom Field',
                2,
                ['new1', 'new2'],
                'new1',
                Response::HTTP_OK,
                'Edited Custom Field',
                4,
                ['edit1', 'edit2'],
                'edit2',
                Response::HTTP_NO_CONTENT,
            ],
            'no_delete' => [
                ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                Response::HTTP_CREATED,
                Response::HTTP_OK,
                'New Custom Field',
                2,
                ['new1', 'new2'],
                'new1',
                Response::HTTP_OK,
                'Edited Custom Field',
                4,
                ['edit1', 'edit2'],
                'edit2',
                Response::HTTP_FORBIDDEN,
            ],
            'no_update' => [
                ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                Response::HTTP_CREATED,
                Response::HTTP_OK,
                'New Custom Field',
                2,
                ['new1', 'new2'],
                'new1',
                Response::HTTP_FORBIDDEN,
                null,
                null,
                null,
                null,
                Response::HTTP_NO_CONTENT,
            ],
            'no_create' => [
                ['viewown', 'viewother', 'editown', 'editother', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                Response::HTTP_FORBIDDEN,
                '',
                null,
                null,
                null,
                null,
                '',
                null,
                null,
                null,
                null,
                '',
            ],
        ];
    }
}
