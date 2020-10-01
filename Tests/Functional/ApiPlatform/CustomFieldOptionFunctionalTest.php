<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IgnoreAnnotation("dataProvider")
 */
final class CustomFieldOptionFunctionalTest extends AbstractApiPlatformFunctionalTest
{
    /**
     * @dataProvider getCRUDProvider
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCustomFieldOptionCRUD(
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
        $customField = $this->createField($customObject, $user);
        // PERMISSION
        $this->setPermission($user, 'custom_objects:custom_fields', $permissions);
        // CREATE
        $payloadCreate = $this->getCreatePayload('/api/v2/custom_field_options/' . $customField->getId());
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
        $payloadUpdate = $this->getEditPayload();
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
        $customField = new CustomField();
        $customField->setLabel('Test custom field');
        $customField->setAlias('test_custom_field');
        $customField->setCustomObject($customObject);
        $customField->setType('multiselect');
        $customField->setDefaultValue('custom field text');
        $customField->setCreatedBy($user);
        $this->em->persist($customField);
        $this->em->flush();
        return $customField;
    }

    private function getCreatePayload(string $customField): array
    {
        return [
            "label"        => "New Custom Field Option",
            "value"        => "custom_field_option",
            "order"        => 0,
            "customField"  => $customField,
        ];
    }

    private function getEditPayload(): array
    {
        return [
            "label"        => "Edited Custom Field Option",
            "value"        => "custom_field_option",
            "order"        => 0
        ];
    }

    /**
     * @see self::testCustomFieldCRUD()
     *
     * @return array|array[]
     */
    public function getCRUDProvider(): array
    {
        return [
            "all_ok" =>
                [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    "New Custom Field Option",
                    Response::HTTP_OK,
                    "Edited Custom Field Option",
                    Response::HTTP_NO_CONTENT
                ],
            "no_delete" =>
                [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    "New Custom Field Option",
                    Response::HTTP_OK,
                    "Edited Custom Field Option",
                    Response::HTTP_FORBIDDEN
                ],
            "no_update" =>
                [
                    ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    "New Custom Field Option",
                    Response::HTTP_FORBIDDEN,
                    null,
                    Response::HTTP_NO_CONTENT
                ],
            "no_create" =>
                [
                    ['viewown', 'viewother', 'editown', 'editother', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_FORBIDDEN,
                    '',
                    null,
                    '',
                    null,
                    ''
                ],
        ];
    }
}
