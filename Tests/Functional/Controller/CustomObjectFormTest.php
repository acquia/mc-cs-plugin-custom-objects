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

    public function testCreate(): void
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
                        'alias'        => '2',
                        'required'     => '',
                        'params'       => '[]',
                        'options'      => '[
                                {
                                    "label": "1",
                                    "value": "1",
                                    "order": 1
                                },
                                {
                                    "label": "2",
                                    "value": "2",
                                    "order": 2
                                }
                            ]',
                        'defaultValue' => [
                            0 => '2',
                        ],
                    ],
                ],
                'category'    => '',
                'isPublished' => 1,
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

        /**
         * @var int $key
         * @var CustomField $customField
         */
        foreach ($customFields as $key => $customField) {
            $this->assertSame($customObject, $customField->getCustomObject());

            $expectedCf = $expected['customFields'][$key];
            $this->assertSame((bool) $expectedCf['isPublished'], $customField->isPublished());
            $this->assertSame($expectedCf['type'], $customField->getType());
            $this->assertSame((int) $expectedCf['order'], $customField->getOrder());
            $this->assertSame($expectedCf['label'], $customField->getLabel());
            $this->assertSame($expectedCf['alias'], $customField->getAlias());
            $this->assertSame((bool) $expectedCf['required'], $customField->isRequired());

            foreach($expectedCf['$options'] as $key => $option) {

                $optionInDb = $customField->
                $this->assertSame($options['label'])
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
