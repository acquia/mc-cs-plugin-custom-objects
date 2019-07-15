<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Response;

class CustomObjectFormTest extends MauticMysqlTestCase
{
    /**
     * @var CustomObjectRepository
     */
    private $repo;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->client->getContainer()->get('custom_object.repository');
    }

    public function testCreateEdit(): void
    {
        $payload = [
            'custom_object' => [
                'nameSingular' => 'singularValue',
                'namePlural'   => 'pluralValue',
                'alias'        => 'pluralValue',
                'description'  => 'descriptionValue',
                'customFields' => [
                    0 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'checkbox_group',
                        'order'        => '0',
                        'label'        => 'CheckboxGroup',
                        'alias'        => '2',
                        'required'     => '',
                        'params'       => '{
                            "requiredValidationMessage": "aa",
                            "emptyValue": "bb"
                        }',
                        'options'      => '[
                            {
                                "label": "1",
                                "value": "1",
                                "order": "1"
                            },
                            {
                                "label": "2",
                                "value": "2",
                                "order": "2"
                            }
                        ]',
                        'defaultValue' => [
                            0 => '2',
                        ],
                        'deleted' => '',
                    ],
                ],
                'category'    => '',
                'isPublished' => '1',
                'buttons'     => ['apply' => ''],
            ],
        ];

        // Create CO
        $this->client->request(
            'POST',
            's/custom/object/save',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);

        // Edit CO
        $payload['custom_object']['alias']                           = 'pluralvalue';
        $payload['custom_object']['customFields'][0]['id']           = 1;
        $payload['custom_object']['customFields'][0]['customObject'] = 1;

        $this->client->restart();
        $this->client->request(
            'POST',
            's/custom/object/save/1',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);

        // Delete CF
        $payload['custom_object']['customFields'][0]['deleted'] = 1;

        $this->client->restart();
        $this->client->request(
            'POST',
            's/custom/object/save/1',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);

        // Delete CO
        $this->client->restart();
        $this->client->request(
            'GET',
            's/custom/object/delete/1',
            [],
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('/s/custom/object', $response['route']);
    }

    public function testCreateAll(): void
    {
        $payload = [
            'custom_object' => [
                'nameSingular' => 'Testing',
                'namePlural'   => 'All',
                'alias'        => 'alias4all',
                'description'  => 'description',
                'customFields' => [
                    0 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'checkbox_group',
                        'order'        => '0',
                        'label'        => 'CheckboxGroup',
                        'alias'        => '10',
                        'required'     => '',
                        'params'       => '{
                            "requiredValidationMessage": "This is required",
                            "emptyValue": "empty value"
                        }',
                        'options'      => '[
                            {
                                "label": "aa",
                                "value": "av",
                                "order": "1"
                            },
                            {
                                "label": "bb",
                                "value": "bv",
                                "order": "2"
                            }
                        ]',
                        'defaultValue' => [
                            0 => '1',
                        ],
                        'deleted' => '',
                    ],
                    1 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'country',
                        'order'        => '1',
                        'label'        => 'Country list',
                        'alias'        => '11',
                        'required'     => '',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => 'AF',
                        'deleted'      => '',
                    ],
                    2 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'date',
                        'order'        => '2',
                        'label'        => 'Date',
                        'alias'        => '12',
                        'required'     => '1',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => '2019-07-18',
                        'deleted'      => '',
                    ],
                    3 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'datetime',
                        'order'        => '3',
                        'label'        => 'Datetime',
                        'alias'        => '13',
                        'required'     => '0',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => '2019-07-18 23:02',
                        'deleted'      => '',
                    ],
                    4 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'email',
                        'order'        => '4',
                        'label'        => 'Email',
                        'alias'        => '14',
                        'required'     => '0',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => 'test@test.com',
                        'deleted'      => '',
                    ],
                    5 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'hidden',
                        'order'        => '5',
                        'label'        => 'Hidden',
                        'alias'        => '15',
                        'required'     => '1',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => 'hidden value',
                        'deleted'      => '',
                    ],
                    6 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'int',
                        'order'        => '6',
                        'label'        => 'Number',
                        'alias'        => '16',
                        'required'     => '1',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => '2',
                        'deleted'      => '',
                    ],
                    7 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '0',
                        'type'         => 'phone',
                        'order'        => '7',
                        'label'        => 'Phone',
                        'alias'        => '17',
                        'required'     => '1',
                        'params'       => '[]',
                        'options'      => '[]',
                        'defaultValue' => '2',
                        'deleted'      => '',
                    ],
                    8 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '0',
                        'type'         => 'radio_group',
                        'order'        => '8',
                        'label'        => 'Radio group',
                        'alias'        => '18',
                        'required'     => '1',
                        'options'      => '[
                            {
                                "label": "rl",
                                "value": "rv",
                                "order": "1"
                            },
                            {
                                "label": "rl1",
                                "value": "rl2",
                                "order": "2"
                            }
                        ]',
                        'params'       => '[]',
                        'defaultValue' => 'rv1',
                        'deleted'      => '',
                    ],
                    9 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'select',
                        'order'        => '9',
                        'label'        => 'Select',
                        'alias'        => '19',
                        'required'     => '0',
                        'options'      => '[
                            {
                                "label": "sl",
                                "value": "sv",
                                "order": "1"
                            },
                            {
                                "label": "sl1",
                                "value": "sl2",
                                "order": "2"
                            }
                        ]',
                        'params'       => '[]',
                        'defaultValue' => 'sv',
                        'deleted'      => '',
                    ],
                    10 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'multiselect',
                        'order'        => '10',
                        'label'        => 'Multiselect',
                        'alias'        => '110',
                        'required'     => '0',
                        'options'      => '[
                            {
                                "label": "msl",
                                "value": "msv",
                                "order": "1"
                            },
                            {
                                "label": "msl1",
                                "value": "msl2",
                                "order": "2"
                            }
                        ]',
                        'params'       => '[]',
                        'defaultValue' => ['msv'],
                        'deleted'      => '',
                    ],
                    11 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'text',
                        'order'        => '11',
                        'label'        => 'Text',
                        'alias'        => '111',
                        'required'     => '0',
                        'options'      => '[]',
                        'params'       => '[]',
                        'defaultValue' => 'some text',
                        'deleted'      => '',
                    ],
                    12 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'textarea',
                        'order'        => '12',
                        'label'        => 'Textarea',
                        'alias'        => '112',
                        'required'     => '0',
                        'options'      => '[]',
                        'params'       => '[]',
                        'defaultValue' => 'some text flkasdfj lfasdf',
                        'deleted'      => '',
                    ],
                    13 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'url',
                        'order'        => '13',
                        'label'        => 'Url',
                        'alias'        => '113',
                        'required'     => '0',
                        'options'      => '[]',
                        'params'       => '[]',
                        'defaultValue' => 'http://fjdsakfsd.com',
                        'deleted'      => '',
                    ],
                ],
                'category'    => '',
                'isPublished' => '0',
                'buttons'     => ['apply' => ''],
            ],
        ];

        $this->client->request(
            'POST',
            's/custom/object/save',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);
    }

    public function testCreateWithCorrectOptionToCFAssignment(): void
    {
        $payload = [
            'custom_object' => [
                'nameSingular' => 'singularValue',
                'namePlural'   => 'pluralValue',
                'alias'        => 'aliasValue',
                'description'  => 'descriptionValue',
                'customFields' => [
                    0 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'checkbox_group',
                        'order'        => '0',
                        'label'        => 'CheckboxGroup',
                        'alias'        => '1',
                        'required'     => '',
                        'params'       => '[]',
                        'options'      => '[
                            {
                                "label": "cl1",
                                "value": "cv1",
                                "order": "1"
                            },
                            {
                                "label": "cl2",
                                "value": "cv2",
                                "order": "2"
                            },
                            {
                                "label": "cl3",
                                "value": "cv3",
                                "order": "3"
                            }
                        ]',
                        'defaultValue' => [
                            0 => 'cv1',
                            1 => 'cv2',
                        ],
                        'deleted' => '',
                    ],
                    1 => [
                        'id'           => '',
                        'customObject' => '',
                        'isPublished'  => '1',
                        'type'         => 'multiselect',
                        'order'        => '1',
                        'label'        => 'Multiselect',
                        'alias'        => '2',
                        'required'     => '',
                        'params'       => '[]',
                        'options'      => '[
                            {
                                "label": "ml1",
                                "value": "mv1",
                                "order": "1"
                            },
                            {
                                "label": "ml2",
                                "value": "mv2",
                                "order": "2"
                            },
                            {
                                "label": "ml3",
                                "value": "mv3",
                                "order": "3"
                            }
                        ]',
                        'defaultValue' => [
                            0 => 'mv2',
                            1 => 'mv3',
                        ],
                        'deleted' => '',
                    ],
                ],
                'category'    => '',
                'isPublished' => '1',
                'buttons'     => ['apply' => ''],
            ],
        ];

        $this->client->request(
            'POST',
            's/custom/object/save',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);
    }

    public function testMultiselectWithRadioOrder()
    {
        $payload = ['custom_object' => [
            'nameSingular' => 'Testing',
            'namePlural'   => 'MultiOrder',
            'alias'        => 'alias4all',
            'description'  => 'description',
            'customFields' => [
                0 => [
                    'id'           => '',
                    'customObject' => '',
                    'isPublished'  => '1',
                    'type'         => 'multiselect',
                    'order'        => '0',
                    'label'        => 'Multiselect',
                    'alias'        => '110',
                    'required'     => '0',
                    'options'      => '[
                            {
                                "label": "msl",
                                "value": "msv",
                                "order": "1"
                            },
                            {
                                "label": "msl1",
                                "value": "msl2",
                                "order": "2"
                            }
                        ]',
                    'params'       => '[]',
                    'defaultValue' => ['msv'],
                    'deleted'      => '',
                ],
            ],
            'category'    => '',
            'isPublished' => '0',
            'buttons'     => ['apply' => ''],
        ]];

        $this->client->request(
            'POST',
            's/custom/object/save',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);

        $payload = ['custom_object' => [
            'nameSingular' => 'Testing',
            'namePlural'   => 'Multi',
            'alias'        => 'alias4all',
            'description'  => 'description',
            'customFields' => [
                8 => [
                    'id'           => '',
                    'customObject' => '',
                    'isPublished'  => '0',
                    'type'         => 'radio_group',
                    'order'        => '0',
                    'label'        => 'Radio group',
                    'alias'        => '18',
                    'required'     => '1',
                    'options'      => '[
                            {
                                "label": "rl",
                                "value": "rv",
                                "order": "1"
                            },
                            {
                                "label": "rl1",
                                "value": "rl2",
                                "order": "2"
                            }
                        ]',
                    'params'       => '[]',
                    'defaultValue' => 'rv',
                    'deleted'      => '',
                ],
                1 => [
                    'id'           => 1,
                    'customObject' => 1,
                    'isPublished'  => '1',
                    'type'         => 'multiselect',
                    'order'        => '1',
                    'label'        => 'Multiselect',
                    'alias'        => '110',
                    'required'     => '0',
                    'options'      => '[
                            {
                                "label": "msl",
                                "value": "msv",
                                "order": "1"
                            },
                            {
                                "label": "msl1",
                                "value": "msl2",
                                "order": "2"
                            }
                        ]',
                    'params'       => '[]',
                    'defaultValue' => ['msv'],
                    'deleted'      => '',
                ],
            ],
            'category'    => '',
            'isPublished' => '0',
            'buttons'     => ['apply' => ''],
        ]];

        $this->client->restart();
        $this->client->request(
            'POST',
            's/custom/object/save/1',
            $payload,
            [],
            $this->createHeaders()
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertCount(1, $response);
        $this->assertSame('/s/custom/object/edit/1', $response['redirect']);

        $this->assertCustomObject($payload, 1);
    }

    /**
     * @param string[] $expected
     * @param int      $id
     */
    private function assertCustomObject(array $expected, int $id): void
    {
        $expected = $expected['custom_object'];

        /** @var CustomObject $customObject */
        $customObject = $this->repo->findOneById($id);

        $this->assertSame($id, $customObject->getId());
        $this->assertSame($expected['nameSingular'], $customObject->getNameSingular());
        $this->assertSame($expected['namePlural'], $customObject->getNamePlural());
        $this->assertSame(strtolower($expected['alias']), $customObject->getAlias());
        $this->assertSame($expected['description'], $customObject->getDescription());
        $this->assertSame((bool) $expected['isPublished'], $customObject->isPublished());

        $customFields = $customObject->getCustomFields();

        if (!$customFields->count()) {
            return;
        }

        /*
         * @var int
         * @var CustomField $customField
         */
        foreach ($expected['customFields'] as $key => $expectedCf) {
            $customField = $customFields->get($key);
            $this->assertSame($customObject, $customField->getCustomObject());

            $this->assertSame((bool) $expectedCf['isPublished'], $customField->isPublished());
            $this->assertSame($expectedCf['type'], $customField->getType());
            $this->assertSame((int) $expectedCf['order'], $customField->getOrder());
            $this->assertSame($expectedCf['label'], $customField->getLabel());
            $this->assertSame($expectedCf['alias'], $customField->getAlias());
            $this->assertSame((bool) $expectedCf['required'], $customField->isRequired());

            $expectedOptions = json_decode($expectedCf['options'], true);
            if ($expectedOptions) {
                foreach ($expectedOptions as $key => $expectedOption) {
                    /** @var CustomFieldOption $option */
                    $option = $customField->getOptions()[$key];

                    $this->assertSame($expectedOption['label'], $option->getLabel());
                    $this->assertSame($expectedOption['value'], $option->getValue());
                    $this->assertSame((int) $expectedOption['order'], $option->getOrder());
                }
            }

            $expectedParams = json_decode($expectedCf['params'], true);
            if ($expectedParams) {
                foreach ($expectedParams as $key => $value) {
                    // It should be Params object but it work fine
                    $this->assertSame($value, $expectedParams[$key]);
                }
            }

            $defaultValue = $customField->getDefaultValue();
            switch ($customField->getType()) {
                case 'date':
                    $this->assertInstanceOf(\DateTime::class, $defaultValue);
                    $this->assertSame($expectedCf['defaultValue'], $defaultValue->format('Y-m-d'));

                    break;
                case 'datetime':
                    $this->assertInstanceOf(\DateTime::class, $defaultValue);
                    $this->assertSame($expectedCf['defaultValue'], $defaultValue->format('Y-m-d H:i'));

                    break;
                default:
                    $this->assertSame($expectedCf['defaultValue'], $defaultValue);

                    break;
            }
        }
    }

    /**
     * @return string[]
     */
    private function createHeaders(): array
    {
        return [
            'HTTP_Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_XDEBUG_SESSION'   => 'XDEBUG_ECLIPSE',
            'HTTP_X-CSRF-Token'     => $this->getCsrfToken('mautic_ajax_post')->getValue(),
        ];
    }
}
