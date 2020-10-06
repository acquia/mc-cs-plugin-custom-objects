<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiPlatformFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var array
     */
    protected $clientServer = [
        'PHP_AUTH_USER' => 'sales',
        'PHP_AUTH_PW'   => 'mautic',
    ];

    protected function getUser(): ?User
    {
        $repository = $this->em->getRepository(User::class);
        $user       = $repository->findOneBy(['firstName' => 'Sales']);
        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    protected function setPermission(User $user, string $permission, array $permissionArray): void
    {
        $role        = $user->getRole();
        $permissions = [
            $permission => $permissionArray,
        ];
        $roleModel = $this->container->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }

    protected function createEntity(string $shortName, array $payload): Response
    {
        return $this->requestEntity('POST', '/api/v2/'.$shortName, $payload);
    }

    protected function retrieveEntity(string $createdId): Response
    {
        return $this->requestEntity('GET', $createdId);
    }

    protected function updateEntity(string $createdId, array $payload): Response
    {
        return $this->requestEntity('PUT', $createdId, $payload);
    }

    protected function deleteEntity(string $createdId): Response
    {
        return $this->requestEntity('DELETE', $createdId);
    }

    protected function requestEntity(string $method, string $path, $payload = null): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        $this->client->request($method, $path, [], [], $server, $payload ? json_encode($payload) : null);

        return $this->client->getResponse();
    }
}
