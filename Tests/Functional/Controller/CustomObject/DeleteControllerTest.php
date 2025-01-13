<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Controller\CustomObject;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Request;

class DeleteControllerTest extends MauticMysqlTestCase
{
    public function testDeleteChildObject()
    {
        // Create a Parent Custom Object
        $parentCustomObject = new CustomObject();
        $parentCustomObject->setNameSingular('Product');
        $parentCustomObject->setNamePlural('Products');
        $parentCustomObject->setAlias('products');
        $parentCustomObject->setType(CustomObject::TYPE_MASTER);
        $this->em->persist($parentCustomObject);

        // Create a Child Custom Object
        $childObject = new CustomObject();
        $childObject->setNameSingular('Electronics');
        $childObject->setNamePlural('Electronics');
        $childObject->setAlias('electronics');
        $childObject->setType(CustomObject::TYPE_RELATIONSHIP);

        // Set Parent-Child relationship and save
        $childObject->setMasterObject($parentCustomObject);
        $parentCustomObject->setRelationshipObject($childObject);
        $this->em->persist($childObject);
        $this->em->flush();

        // Open Custom Object listing page verify that both parent and child objects are listed in Custom Objects table.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/custom/object');
        $this->assertStringContainsString('Electronics', $crawler->filterXPath('//*[@id="custom-objects-table"]/tbody/tr[1]/td[2]/div/a')->text());
        $this->assertStringContainsString('Products', $crawler->filterXPath('//*[@id="custom-objects-table"]/tbody/tr[2]/td[2]/div/a')->text());

        // Delete the Child Custom Object
        $this->client->request(Request::METHOD_POST, sprintf('/s/custom/object/delete/%s', $childObject->getId()));

        // Now, go back to the listing page and verify the Parent CO is there, but not Child CO in Custom Objects table.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/custom/object');
        $this->assertStringContainsString('Products', $crawler->filterXPath('//*[@id="custom-objects-table"]/tbody/tr[1]/td[2]/div/a')->text());
        $this->assertNull($crawler->filterXPath('//*[@id="custom-objects-table"]/tbody/tr[2]')->getNode(0));
    }
}
