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
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IgnoreAnnotation("dataProvider")
 */
final class CustomFieldOptionFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var array
     */
    private $clientUser = [
        'PHP_AUTH_USER' => 'sales',
        'PHP_AUTH_PW'   => 'mautic',
    ];

    public function setUp(): void
    {
        putenv('MAUTIC_CONFIG_PARAMETERS='.json_encode(
                [
                    'api_enabled'                       => true,
                    'api_enable_basic_auth'             => true,
                    'create_custom_field_in_background' => true,
                ]
            ));
        \Mautic\CoreBundle\ErrorHandler\ErrorHandler::register('prod');
    }
    private function getUser(): User
    {
        $this->loadFixtures([LoadRoleData::class, LoadUserData::class]);
        $this->client = static::createClient([], $this->clientUser);
        $this->client->disableReboot();
        $this->client->followRedirects(false);
        $this->container = $this->client->getContainer();
        $this->em        = $this->container->get('doctrine')->getManager();
        $repository      = $this->em->getRepository(User::class);
        return $repository->findOneBy(['firstName' => 'Sales']);
    }

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
        string $httpDeleted): void
    {
        $user = $this->getUser();
        $this->setPermission($user, $permissions);

        // CREATE OBJECT AND FIELD
        $customObject = $this->createCustomObject();
        $customField = $this->createField($customObject, $user);

        // CREATE
        $clientCreateResponse = $this->createFieldOption($customField);
        $this->assertEquals($httpCreated, $clientCreateResponse->getStatusCode());

        // RETRIEVE
        if (!property_exists(json_decode($clientCreateResponse->getContent()), '@id')) {
            return;
        }
        $createdId = json_decode($clientCreateResponse->getContent())->{'@id'};
        $clientRetrieveResponse = $this->retrieveFieldOption($createdId);
        $this->assertEquals($httpRetrieved, $clientRetrieveResponse->getStatusCode());
        if ($retrievedLabel) {
            $this->assertEquals($retrievedLabel, json_decode($clientRetrieveResponse->getContent())->label);
        }
        // UPDATE
        $clientUpdateResponse = $this->updateFieldOption($createdId);
        $this->assertEquals($httpUpdated, $clientUpdateResponse->getStatusCode());
        if ($updatedLabel) {
            $this->assertEquals($updatedLabel, json_decode($clientUpdateResponse->getContent())->label);
        }
        // DELETE
        $clientDeleteResponse = $this->deleteFieldOption($createdId);
        $this->assertEquals($httpDeleted, $clientDeleteResponse->getStatusCode());
    }

    private function setPermission(User $user, array $permissionArray): void
    {
        $role = $user->getRole();
        $permissions = [
            'custom_objects:custom_fields'  => $permissionArray
        ];
        $roleModel = $this->container->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
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

    private function createFieldOption(CustomField $customField): Response
    {
        $payload = $this->getCreatePayload('/api/v2/custom_fields/'.$customField->getId());
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        $this->client->request('POST', '/api/v2/custom_field_options', [], [], $server, json_encode($payload));
        return $this->client->getResponse();
    }

    private function retrieveFieldOption(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        $this->client->request('GET', $createdId, [], [], $server);
        return $this->client->getResponse();
    }

    private function updateFieldOption(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        $payload = $this->getEditPayload();
        $this->client->request('PUT', $createdId, [], [], $server, json_encode($payload));
        return $this->client->getResponse();
    }

    private function deleteFieldOption(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        $this->client->request('DELETE', $createdId, [], [], $server);
        return $this->client->getResponse();
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
    public function getCRUDProvider(): array {
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
