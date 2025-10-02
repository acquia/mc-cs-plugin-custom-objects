<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use Symfony\Component\HttpFoundation\Response;

class ApiSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'custom_item',
        ]);
    }

    /**
     * A custom object with alias "unicorn" does not exist.
     * In this case no contact and no custom item should be created.
     */
    public function testCreatingContactWithCustomItemsWithUnexistingCustomObject(): void
    {
        $contact = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => 'unicorn',
                        'data'  => [
                            [
                                'name' => 'Custom Item Created Via Contact API',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Custom Object with alias = unicorn was not found', $responseData['errors'][0]['message']);

        /** @var CustomItemRepository $customItemRepository */
        $customItemRepository = self::$container->get('custom_item.repository');

        /** @var LeadModel $contactModel */
        $contactModel = self::$container->get('mautic.lead.model.lead');

        $this->assertNull($customItemRepository->findOneBy(['name' => 'Custom Item Created Via Contact API 2']));
        $this->assertNull($contactModel->getRepository()->findOneBy(['email' => 'contact1@api.test']));
    }

    /**
     * The custom objects will be returned in the payload only if the query parameter `includeCustomObjects=true` exists.
     */
    public function testCreatingContactWithCustomItemsWithoutTheFlagToReturnCustomObjects(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');

        $contact = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => $customObject->getAlias(),
                        'data'  => [
                            [
                                'name'       => 'Custom Item Created Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'         => 'Yellow snake',
                                    'textarea-test-field'     => "Multi\nline\nvalue",
                                    'url-test-field'          => 'https://mautic.org',
                                    'multiselect-test-field'  => ['option_b'],
                                    'select-test-field'       => 'option_a',
                                    'phone-number-test-field' => '+420775308002',
                                    'number-test-field'       => 123,
                                    'hidden-test-field'       => 'secret',
                                    'email-test-field'        => 'john@doe.email',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertTrue(empty($responseData['contact']['customObjects']));
    }

    public function testCreatingContactWithCustomItemsButFieldDoesNotExist(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API 2',
                                'attributes' => [
                                    'unicorn' => 'Yellow snake',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), $response->getContent());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $responseData['errors'][0]['code']);
        $this->assertSame('Custom field with alias unicorn was not found.', $responseData['errors'][0]['message']);
    }

    public function testCreatingContactWithCustomItemsWithMultiselectAsString(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API 2',
                                'attributes' => [
                                    'multiselect-test-field' => ['option_a'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);

        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $responseData           = json_decode($response->getContent(), true);
        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];

        $this->assertSame(['option_a'], $customItemFromResponse['attributes']['multiselect-test-field']);
    }

    public function testCreatingContactWithCustomItemsAndEditAndClearValues(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'           => 'Yellow snake',
                                    'textarea-test-field'       => "Multi\nline\nvalue",
                                    'url-test-field'            => 'https://mautic.org',
                                    'multiselect-test-field'    => ['option_b'],
                                    'select-test-field'         => 'option_a',
                                    'phone-number-test-field'   => '+420775308002',
                                    'number-test-field'         => 123,
                                    'hidden-test-field'         => 'secret',
                                    'email-test-field'          => 'john@doe.email',
                                    'date-test-field'           => '2019-06-21',
                                    'datetime-test-field'       => '2019-06-21T11:29:34+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertSame($customObject->getAlias(), $responseData['contact']['customObjects']['data'][0]['alias']);
        $this->assertSame($customObject->getId(), $responseData['contact']['customObjects']['data'][0]['id']);
        $this->assertSame(10, $responseData['contact']['customObjects']['meta']['page']['size']);
        $this->assertSame(1, $responseData['contact']['customObjects']['meta']['page']['number']);
        $this->assertSame('-dateAdded', $responseData['contact']['customObjects']['meta']['sort']);
        $this->assertNull($responseData['contact']['fields']['all']['firstname']);
        $this->assertFalse(empty($responseData['contact']['customObjects']['data']), 'Contact response does not contain the customObjects property. '.$response->getContent());
        $this->assertCount(1, $responseData['contact']['customObjects']['data']);

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('Custom Item Created Via Contact API 2', $customItemFromResponse['name']);
        $this->assertSame('Yellow snake', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame("Multi\nline\nvalue", $customItemFromResponse['attributes']['textarea-test-field']);
        $this->assertSame('https://mautic.org', $customItemFromResponse['attributes']['url-test-field']);
        $this->assertSame(['option_b'], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('option_a', $customItemFromResponse['attributes']['select-test-field']);
        $this->assertSame('+420775308002', $customItemFromResponse['attributes']['phone-number-test-field']);
        $this->assertSame(123, $customItemFromResponse['attributes']['number-test-field']);
        $this->assertSame('secret', $customItemFromResponse['attributes']['hidden-test-field']);
        $this->assertSame('john@doe.email', $customItemFromResponse['attributes']['email-test-field']);
        $this->assertSame('2019-06-21', $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame('2019-06-21T11:29:34+00:00', $customItemFromResponse['attributes']['datetime-test-field']);

        // Let's try to update the contact and the custom item with different values.

        $contact = [
            'email'         => 'contact1@api.test',
            'firstname'     => 'Contact1',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => $customObject->getAlias(),
                        'data'  => [
                            [
                                'id'         => 1,
                                'name'       => 'Custom Item Modified Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'           => 'Yellow cake',
                                    'textarea-test-field'       => "Multi\nnine\nvalue",
                                    'url-test-field'            => 'https://mautic.com',
                                    'multiselect-test-field'    => ['option_a'],
                                    'select-test-field'         => 'option_b',
                                    'phone-number-test-field'   => '+420775308003',
                                    'number-test-field'         => 123456,
                                    // 'hidden-test-field'         => 'secret sauce', // Test the value stick if not in the request.
                                    'email-test-field'          => 'john@doe.com',
                                    'date-test-field'           => '2019-06-23',
                                    'datetime-test-field'       => '2019-06-23T11:29:34+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->restart();
        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertSame('Contact1', $responseData['contact']['fields']['all']['firstname']);
        $this->assertCount(1, $responseData['contact']['customObjects']['data'][0]['data'], 'Contact response does not contain the customObjects array. '.$response->getContent());

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('Custom Item Modified Via Contact API 2', $customItemFromResponse['name']);
        $this->assertSame('Yellow cake', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame("Multi\nnine\nvalue", $customItemFromResponse['attributes']['textarea-test-field']);
        $this->assertSame('https://mautic.com', $customItemFromResponse['attributes']['url-test-field']);
        $this->assertSame(['option_a'], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('option_b', $customItemFromResponse['attributes']['select-test-field']);
        $this->assertSame('+420775308003', $customItemFromResponse['attributes']['phone-number-test-field']);
        $this->assertSame(123456, $customItemFromResponse['attributes']['number-test-field']);
        $this->assertSame('secret', $customItemFromResponse['attributes']['hidden-test-field']);
        $this->assertSame('john@doe.com', $customItemFromResponse['attributes']['email-test-field']);
        $this->assertSame('2019-06-23', $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame('2019-06-23T11:29:34+00:00', $customItemFromResponse['attributes']['datetime-test-field']);

        // Let's try to update the contact and the custom item with empty values.

        $contact = [
            'email'         => 'contact1@api.test',
            'firstname'     => 'Contact1',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => $customObject->getAlias(),
                        'data'  => [
                            [
                                'id'         => 1,
                                'name'       => 'Custom Item Modified Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'           => null,
                                    'textarea-test-field'       => null,
                                    'url-test-field'            => null,
                                    'multiselect-test-field'    => null,
                                    'select-test-field'         => null,
                                    'phone-number-test-field'   => null,
                                    'number-test-field'         => null,
                                    'hidden-test-field'         => null,
                                    'email-test-field'          => null,
                                    'date-test-field'           => '',
                                    'datetime-test-field'       => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->restart();
        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertSame('Contact1', $responseData['contact']['fields']['all']['firstname']);
        $this->assertCount(1, $responseData['contact']['customObjects']['data'][0]['data'], 'Contact response does not contain the customObjects array. '.$response->getContent());

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('Custom Item Modified Via Contact API 2', $customItemFromResponse['name']);
        $this->assertSame('', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['textarea-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['url-test-field']);
        $this->assertSame([], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['select-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['phone-number-test-field']);
        $this->assertSame(0, $customItemFromResponse['attributes']['number-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['hidden-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['email-test-field']);
        $this->assertSame(null, $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame(null, $customItemFromResponse['attributes']['datetime-test-field']);
    }

    public function testCreatingContactWithCustomItemsWithDefaultValue(): void
    {
        $configureFieldCallback = function (CustomField $customField): void {
            if ('date' === $customField->getType()) {
                $customField->setDefaultValue('2019-06-21');
            }
            if ('text' === $customField->getType()) {
                $customField->setDefaultValue('A default value');
            }
            if ('multiselect' === $customField->getType()) {
                $customField->setDefaultValue(['option_b']);
            }
        };

        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product', $configureFieldCallback);
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API for default value field test',
                                'attributes' => [
                                    'datetime-test-field' => '2019-06-26 13:29:43',
                                    // 'date-test-field' => '', // Intentionally not provided in the request.
                                    // 'text-test-field' => '', // Intentionally not provided in the request.
                                    // 'multiselect-test-field' => '', // Intentionally not provided in the request.
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);

        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $responseData           = json_decode($response->getContent(), true);
        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];

        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('2019-06-21', $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame('2019-06-26T13:29:43+00:00', $customItemFromResponse['attributes']['datetime-test-field']);
        $this->assertSame('A default value', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame(['option_b'], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['url-test-field']);
    }

    public function testCreatingContactWithCustomItemsWithOverwrittenDefaultValue(): void
    {
        $configureFieldCallback = function (CustomField $customField): void {
            if ('date' === $customField->getType()) {
                $customField->setDefaultValue('2019-06-21');
            }
            if ('text' === $customField->getType()) {
                $customField->setDefaultValue('A default value');
            }
            if ('multiselect' === $customField->getType()) {
                $customField->setDefaultValue(['option_b']);
            }
        };

        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product', $configureFieldCallback);
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API for default value field test',
                                'attributes' => [
                                    'datetime-test-field'       => '2019-06-26 13:29:43',
                                    'date-test-field'           => '',
                                    'text-test-field'           => '',
                                    'multiselect-test-field'    => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];

        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame(null, $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame('2019-06-26T13:29:43+00:00', $customItemFromResponse['attributes']['datetime-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame([], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('', $customItemFromResponse['attributes']['url-test-field']);
    }

    public function testCreatingContactWithCustomItemsWithDefaultDateButEmptyValue(): void
    {
        $configureFieldCallback = function (CustomField $customField): void {
            if ('date' === $customField->getType()) {
                $customField->setDefaultValue('2019-06-21');
            }
            if ('datetime' === $customField->getType()) {
                $customField->setDefaultValue('2019-06-21 11:29:34');
            }
        };

        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product', $configureFieldCallback);
        $contact      = [
            'email'         => 'contact1@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'Custom Item Created Via Contact API for Date field test',
                                'attributes' => [
                                    // 'date-test-field' => '', // Intentionally not provided in the request.
                                    // 'datetime-test-field' => '', // Intentionally not provided in the request.
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('2019-06-21', $customItemFromResponse['attributes']['date-test-field']);
        $this->assertSame('2019-06-21T11:29:34+00:00', $customItemFromResponse['attributes']['datetime-test-field']);
    }

    public function testEditingContactWithCustomItems(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');

        $contact = [
            'email'         => 'contact1@api.test',
            'firstname'     => 'Contact',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => $customObject->getAlias(),
                        'data'  => [
                            [
                                'name'       => 'Custom Item Created Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'         => 'Yellow snake',
                                    'textarea-test-field'     => "Multi\nline\nvalue",
                                    'url-test-field'          => 'https://mautic.org',
                                    'multiselect-test-field'  => ['option_b'],
                                    'select-test-field'       => 'option_a',
                                    'phone-number-test-field' => '+420775308002',
                                    'number-test-field'       => 123,
                                    'hidden-test-field'       => 'secret',
                                    'email-test-field'        => 'john@doe.email',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/new?includeCustomObjects=true', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertSame('Contact', $responseData['contact']['fields']['all']['firstname']);
        $this->assertFalse(empty($responseData['contact']['customObjects']['data']), 'Contact response does not contain the customObjects property. '.$response->getContent());
        $this->assertCount(1, $responseData['contact']['customObjects']['data']);

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('Custom Item Created Via Contact API 2', $customItemFromResponse['name']);
        $this->assertSame('Yellow snake', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame("Multi\nline\nvalue", $customItemFromResponse['attributes']['textarea-test-field']);
        $this->assertSame('https://mautic.org', $customItemFromResponse['attributes']['url-test-field']);
        $this->assertSame(['option_b'], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('option_a', $customItemFromResponse['attributes']['select-test-field']);
        $this->assertSame('+420775308002', $customItemFromResponse['attributes']['phone-number-test-field']);
        $this->assertSame(123, $customItemFromResponse['attributes']['number-test-field']);
        $this->assertSame('secret', $customItemFromResponse['attributes']['hidden-test-field']);
        $this->assertSame('john@doe.email', $customItemFromResponse['attributes']['email-test-field']);

        // Let's try to update the contact and the custom item.

        $contact = [
            'firstname'     => 'Contact1',
            'customObjects' => [
                'data' => [
                    [
                        'alias' => $customObject->getAlias(),
                        'data'  => [
                            [
                                'id'         => 1,
                                'name'       => 'Custom Item Modified Via Contact API 2',
                                'attributes' => [
                                    'text-test-field'         => 'Yellow cake',
                                    'textarea-test-field'     => "Multi\nnine\nvalue",
                                    'url-test-field'          => 'https://mautic.com',
                                    'multiselect-test-field'  => ['option_a'],
                                    'select-test-field'       => 'option_b',
                                    'phone-number-test-field' => '+420775308003',
                                    'number-test-field'       => 123456,
                                    'hidden-test-field'       => 'secret sauce',
                                    'email-test-field'        => 'john@doe.com',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->restart();
        $this->client->request('PATCH', "api/contacts/{$responseData['contact']['id']}/edit?includeCustomObjects=true", $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
        $this->assertSame('Contact1', $responseData['contact']['fields']['all']['firstname']);
        $this->assertFalse(empty($responseData['contact']['customObjects']['data'][0]['data']), 'The contact does not contain the `customObjects[data][0][data]` parameter containg custom objects');
        $this->assertCount(1, $responseData['contact']['customObjects']['data'][0]['data']);

        $customItemFromResponse = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame(1, $customItemFromResponse['id']);
        $this->assertSame('Custom Item Modified Via Contact API 2', $customItemFromResponse['name']);
        $this->assertSame('Yellow cake', $customItemFromResponse['attributes']['text-test-field']);
        $this->assertSame("Multi\nnine\nvalue", $customItemFromResponse['attributes']['textarea-test-field']);
        $this->assertSame('https://mautic.com', $customItemFromResponse['attributes']['url-test-field']);
        $this->assertSame(['option_a'], $customItemFromResponse['attributes']['multiselect-test-field']);
        $this->assertSame('option_b', $customItemFromResponse['attributes']['select-test-field']);
        $this->assertSame('+420775308003', $customItemFromResponse['attributes']['phone-number-test-field']);
        $this->assertSame(123456, $customItemFromResponse['attributes']['number-test-field']);
        $this->assertSame('secret sauce', $customItemFromResponse['attributes']['hidden-test-field']);
        $this->assertSame('john@doe.com', $customItemFromResponse['attributes']['email-test-field']);
    }

    public function testBatchCreatingContactWithCustomItems(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contacts     = [
            [
                'email'         => 'contact3@api.test',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => $customObject->getAlias(),
                            'data'  => [
                                [
                                    'name'       => 'Custom Item Created Via Contact API 3',
                                    'attributes' => [
                                        'text-test-field' => 'Take a brake',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'email'         => 'contact4@api.test',
                'customObjects' => [
                    'data' => [
                        [
                            'alias' => $customObject->getAlias(),
                            'data'  => [
                                [
                                    'name'       => 'Custom Item Created Via Contact API 4',
                                    'attributes' => [
                                        'text-test-field' => 'Make a milkshake',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', 'api/contacts/batch/new?includeCustomObjects=true', $contacts);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
        $this->assertFalse(empty($responseData['contacts']), 'The payload must contain the "contacts" param. '.$response->getContent());
        $this->assertFalse(empty($responseData['contacts'][0]), 'The payload must contain the "contacts[0]" param. '.$response->getContent());
        $this->assertFalse(empty($responseData['contacts'][1]), 'The payload must contain the "contacts[1]" param. '.$response->getContent());
        $this->assertFalse(empty($responseData['contacts'][0]['customObjects']['data']), 'Contact3 response does not contain the customObjects property. '.$response->getContent());
        $this->assertFalse(empty($responseData['contacts'][1]['customObjects']['data']), 'Contact4 response does not contain the customObjects property. '.$response->getContent());

        $contact3           = $responseData['contacts'][0];
        $contact4           = $responseData['contacts'][1];
        $contact3CustomItem = $contact3['customObjects']['data'][0]['data'][0];
        $contact4CustomItem = $contact4['customObjects']['data'][0]['data'][0];

        $this->assertSame(1, $contact3['id']);
        $this->assertSame(2, $contact4['id']);
        $this->assertSame('contact3@api.test', $contact3['fields']['all']['email']);
        $this->assertSame('contact4@api.test', $contact4['fields']['all']['email']);
        $this->assertCount(1, $contact3['customObjects']['data']);
        $this->assertCount(1, $contact4['customObjects']['data']);

        $this->assertSame(1, $contact3CustomItem['id']);
        $this->assertSame(2, $contact4CustomItem['id']);
        $this->assertSame('Custom Item Created Via Contact API 3', $contact3CustomItem['name']);
        $this->assertSame('Custom Item Created Via Contact API 4', $contact4CustomItem['name']);
        $this->assertSame('Take a brake', $contact3CustomItem['attributes']['text-test-field']);
        $this->assertSame('Make a milkshake', $contact4CustomItem['attributes']['text-test-field']);
    }
}
