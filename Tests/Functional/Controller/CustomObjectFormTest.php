<?php

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
use Symfony\Component\HttpFoundation\Response;

class CustomObjectFormTest extends MauticMysqlTestCase
{
    public function testCreate(): void
    {
        $_POST = $payload = $this->createPostCheckboxGroup();
        $token = $this->getCsrfToken('mautic_ajax_post')->getValue();

        $this->client->request(
            'POST',
            's/custom/object/save',
            $payload,
            [],
            [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'HTTP_XDEBUG_SESSION' => 'XDEBUG_ECLIPSE',
                'HTTP_X-CSRF-Token' => $token,
            ]
        );

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertTrue(!empty($response['flashes']));
    }

    private function createPostCheckboxGroup(): array
    {
        return [
                'custom_object' => [
                    'nameSingular' => 'all',
                    'namePlural' => 'all',
                    'alias' => 'all',
                    'description' => '',
                    'category' => '',
                    'isPublished' => 1,
                    'buttons' => ['apply' => ''],
                    'customFields' => [
                        'defaultValue' => [
                            0 => '2',
                        ],
                        'id' => '',
                        'customObject' => '2',
                        'isPublished' => '1',
                        'type' => 'checkbox_group',
                        'order' => '0',
                        'label' => 'CheckboxGroup',
                        'alias' => '2',
                        'required' => '',
                        'params' => '[]',
                        'options' => '[
                            {
                                "customField": 11,
                                "label": "1",
                                "value": "1",
                                "order": 1
                            },
                            {
                                "customField": 11,
                                "label": "2",
                                "value": "2",
                                "order": 2
                            }
                        ]',
                ],
            ],
        ];
    }
}