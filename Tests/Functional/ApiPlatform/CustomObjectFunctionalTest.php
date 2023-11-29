<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\ApiPlatform;

use Symfony\Component\HttpFoundation\Response;

final class CustomObjectFunctionalTest extends AbstractApiPlatformFunctionalTest
{
    public function testCustomObjectCRUD(): void
    {
        foreach ($this->getCRUDProvider() as $parameters) {
            $this->runTestCustomObjectCRUD(...$parameters);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function runTestCustomObjectCRUD(
        array $permissions,
        string $httpCreated,
        string $httpRetrieved,
        ?string $retrievedAlias,
        string $httpUpdated,
        ?string $updatedAlias,
        string $httpDeleted
    ): void {
        $user = $this->getUser();
        $this->setPermission($user, 'custom_objects:custom_objects', $permissions);

        // CREATE
        $payloadCreate        = $this->getCreatePayload();
        $clientCreateResponse = $this->createEntity('custom_objects', $payloadCreate);
        $this->assertEquals($httpCreated, $clientCreateResponse->getStatusCode());
        if (Response::HTTP_FORBIDDEN === $clientCreateResponse->getStatusCode()) {
            return;
        }
        // GET ID OF ENTITY
        $createdId = json_decode($clientCreateResponse->getContent())->{'@id'};
        // RETRIEVE
        $clientRetrieveResponse = $this->retrieveEntity($createdId);
        $this->assertEquals($httpRetrieved, $clientRetrieveResponse->getStatusCode());
        if ($retrievedAlias) {
            $this->assertEquals($retrievedAlias, json_decode($clientRetrieveResponse->getContent())->alias);
        }
        // UPDATE
        $payloadUpdate        = $this->getEditPayload();
        $clientUpdateResponse = $this->patchEntity($createdId, $payloadUpdate);
        $this->assertEquals($httpUpdated, $clientUpdateResponse->getStatusCode());
        if ($updatedAlias) {
            $this->assertEquals($updatedAlias, json_decode($clientUpdateResponse->getContent())->alias);
        }
        // DELETE
        $clientDeleteResponse = $this->deleteEntity($createdId);
        $this->assertEquals($httpDeleted, $clientDeleteResponse->getStatusCode());
    }

    private function getCreatePayload(): array
    {
        return
            [
                'alias'        => 'customObjectTest',
                'nameSingular' => 'Test',
                'namePlural'   => 'Tests',
                'description'  => 'string string',
                'language'     => 'en',
                'customFields' => [
                        [
                            'label'        => 'Test Field 1',
                            'alias'        => 'customObjectTestField1',
                            'type'         => 'multiselect',
                            'order'        => 42,
                            'required'     => true,
                            'defaultValue' => 'one',
                            'options'      => [
                                    [
                                        'label' => 'one',
                                        'value' => 'one',
                                        'order' => 0,
                                    ],
                                    [
                                        'label' => 'two',
                                        'value' => 'two',
                                        'order' => 1,
                                    ],
                                ],
                            'params' => [
                                'string',
                            ],
                            'isPublished' => true,
                        ],
                        [
                            'label'        => 'Test Field 2',
                            'alias'        => 'customObjectTestField2',
                            'type'         => 'text',
                            'order'        => 43,
                            'required'     => true,
                            'defaultValue' => 'text',
                            'params'       => [
                                'string',
                            ],
                            'isPublished' => true,
                        ],
                    ],
            ];
    }

    private function getEditPayload(): array
    {
        return
            [
                'alias'        => 'customObjectTestEdited',
                'nameSingular' => 'Test Edited',
                'namePlural'   => 'Tests Edited',
                'description'  => 'string string Edited',
                'language'     => 'en',
            ];
    }

    /**
     * @see self::testCustomObjectCRUD()
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
                    'customObjectTest',
                    Response::HTTP_OK,
                    'customObjectTestEdited',
                    Response::HTTP_NO_CONTENT,
                ],
            'no_delete' => [
                    ['viewown', 'viewother', 'editown', 'editother', 'create', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'customObjectTest',
                    Response::HTTP_OK,
                    'customObjectTestEdited',
                    Response::HTTP_FORBIDDEN,
                ],
            'no_update' => [
                    ['viewown', 'viewother', 'create', 'deleteown', 'deleteother', 'publishown', 'publishother'],
                    Response::HTTP_CREATED,
                    Response::HTTP_OK,
                    'customObjectTest',
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
