<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use FOS\RestBundle\Util\Codes;

class ApiSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreatingContactWithCustomItems(): void
    {
        $customObject = $this->createCustomObjectWithAllFields($this->container, 'Contact API with CO test');

        $contact = [
            'email' => 'contact1@api.test',
            'customObjects' => [
                $customObject->getId() => [ // @todo replace ID with alias.
                    [
                        'name' => 'Custom Item Created Via Contact API',
                    ]
                ]
            ],
        ];

        $this->client->request('POST', 'api/contacts/new', $contact);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        dump($responseData);
        $this->assertSame(Codes::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame(1, $responseData['contact']['id']);
        $this->assertSame('contact1@api.test', $responseData['contact']['fields']['all']['email']);
    }
}
