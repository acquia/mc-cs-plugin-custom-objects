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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IgnoreAnnotation("dataProvider")
 */
final class CustomFieldFunctionalTest extends MauticMysqlTestCase
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
        $this->client->followRedirects(true);
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
    public function testCustomFieldCRUD(
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
        // CREATE
        $customObject = $this->createCustomObject();
        $clientCreateResponse = $this->createField($customObject);
        $this->assertEquals($httpCreated, $clientCreateResponse->getStatusCode());
        // RETRIEVE
        if (!property_exists(json_decode($clientCreateResponse->getContent()), '@id')) {
            return;
        }
        $createdId = json_decode($clientCreateResponse->getContent())->{'@id'};
        $clientRetrieveResponse = $this->retrieveField($createdId);
        $this->assertEquals($httpRetrieved, $clientRetrieveResponse->getStatusCode());
        if ($retrievedLabel) {
            $this->assertEquals($retrievedLabel, json_decode($clientRetrieveResponse->getContent())->label);
        }
        // UPDATE
        $clientUpdateResponse = $this->updateField($createdId);
        $this->assertEquals($httpUpdated, $clientUpdateResponse->getStatusCode());
        if ($updatedLabel) {
            $this->assertEquals($updatedLabel, json_decode($clientUpdateResponse->getContent())->label);
        }
        // DELETE
        $clientDeleteResponse = $this->deleteField($createdId);
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

    private function createField($customObject): Response
    {
        $payload = $this->getCreatePayload('/api/v2/custom_objects/'.$customObject->getId());
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'ACCEPT' => 'application/ld+json'];
        $this->client->request('POST', '/api/v2/custom_fields.jsonld', [], [], $server, json_encode($payload));
        return $this->client->getResponse();
    }

    private function retrieveField(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'ACCEPT' => 'application/ld+json'];
        $this->client->request('GET', $createdId.'.jsonld', [], [], $server);
        return $this->client->getResponse();
    }

    private function updateField(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'ACCEPT' => 'application/ld+json'];
        $payload = $this->getEditPayload();
        $this->client->request('PUT', $createdId.'.jsonld', [], [], $server, json_encode($payload));
        return $this->client->getResponse();
    }

    private function deleteField(string $createdId): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'ACCEPT' => 'application/ld+json'];
        $this->client->request('DELETE', $createdId.'.jsonld', [], [], $server);
        return $this->client->getResponse();
    }

    private function getCreatePayload(string $customObject): array
    {
        return [
            "label"        => "New Custom Field",
            "alias"        => "custom_field_alias",
            "type"         => "text",
            "customObject" => $customObject,
            "order"        => 0,
            "required"     => true,
            "defaultValue" => "string",
            "params"       => [
                "string1",
                "string2"
            ],
            "published"    => false
        ];
    }

    private function getEditPayload(): array
    {
        return [
            "label"        => "Edited Custom Field",
            "type"         => "text",
            "order"        => 1,
            "required"     => false,
            "defaultValue" => "default",
            "params"       => [
                "string1",
                "string2"
            ],
            "published"    => true
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
                    "New Custom Field",
                    Response::HTTP_OK,
                    "Edited Custom Field",
                    Response::HTTP_NO_CONTENT
                ],
            "no_delete" =>
                [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    "New Custom Field",
                    Response::HTTP_OK,
                    "Edited Custom Field",
                    Response::HTTP_FORBIDDEN
                ],
            "no_update" =>
                [
                    ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    "New Custom Field",
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
