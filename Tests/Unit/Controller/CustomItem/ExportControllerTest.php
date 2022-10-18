<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomObjectTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportControllerTest extends CustomObjectTestCase
{
    private CustomObject $customObject;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCustomItemExportScheduled(): void
    {
        $this->createMockData();
        $url                    = '/s/custom/object/'.$this->customObject->getId().'/export';
        $crawler                = $this->client->request(Request::METHOD_POST, $url);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $customItemExportScheduler  = $this->em->getRepository(CustomItemExportScheduler::class)
                                                ->findBy(['customObjectId' => $this->customObject->getId()]);

        $this->assertNotNull($customItemExportScheduler, 'Custom Item Export Not Scheduled');
        $this->assertStringContainsString('Custom Item export scheduled.', $clientResponseContent);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createMockData(): void
    {
        $customObject = $this->createCustomObject();
        $this->em->persist($customObject);
        $this->em->flush();

        $this->customObject = $customObject;
    }
}
