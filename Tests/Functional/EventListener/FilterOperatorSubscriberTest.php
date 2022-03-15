<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpFoundation\Request;

class FilterOperatorSubscriberTest extends MauticMysqlTestCase
{
    public function testIfNewOperatorNotInCustomObjectsAddedinSegmentFilter()
    {
        // Create a segment
        $segment = new LeadList();
        $segment->setName('Test Segment A');
        $segment->setPublicName('Test Segment A');
        $segment->setAlias('test-segment-a');

        $this->em->persist($segment);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());

        $segment_filters = $crawler->filter('#available_segment_filters')->html();

        $this->assertStringContainsString('not in custom objects', $segment_filters);
    }

    public function testIfProperContactsAreAddedinSegmentWithNotInCustomObjectsFilter()
    {
        // 1) create 2 contacts with "testcontact1@acquia.com" and "testcontact2@gmail.com" in email
        $contact1 = new Lead();
        $contact1->setFirstname('Test')->setLastname('Contact 1')->setEmail('testcontact1@acquia.com');
        $this->em->persist($contact1);

        $contact2 = new Lead();
        $contact2->setFirstname('Test')->setLastname('Contact 2')->setEmail('testcontact2@gmail.com');
        $this->em->persist($contact2);

        // 2) create custom object "Email List" with "testcontact1@acquia.com" and "testcontact2@mautic.com" as items
        $customObject = new CustomObject();
        $customObject->setNameSingular('Email List');
        $customObject->setNamePlural('Emai List');
        $customObject->setAlias('emails');
        $customObject->setType(CustomObject::TYPE_MASTER);
        $this->em->persist($customObject);

        $customItem1   = new CustomItem($customObject);
        $customItem1->setName('testcontact1@acquia.com');
        $this->em->persist($customItem1);

        $customItem2   = new CustomItem($customObject);
        $customItem2->setName('testcontact2@mautic.com');
        $this->em->persist($customItem2);
        $this->em->flush();

        // 3) create a segment with filter : email > not in custom objects > select custom object
        $filters = [[
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'email',
                'type'       => 'text',
                'operator'   => 'notInCustomObjects',
                'properties' => [
                    'filter' => 'custom-object:'.$customObject->getId().':name',
                ],
                'filter'     => 'custom-object:'.$customObject->getId().':name',
                'display'    => null,
            ]];
        $segment = new LeadList();
        $segment->setName('Test Segment A');
        $segment->setPublicName('Test Segment A');
        $segment->setAlias('test-segment-a');
        $segment->setFilters($filters);
        $this->em->persist($segment);
        $this->em->flush();

        // 4) run update segment command
        $this->runCommand('mautic:segments:update', ['-i' => $segment->getId()]);

        // 5) fetch segment added contacts
        $leads = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()], ['lead' => 'DESC']);

        // 6) Assertions
        $this->assertCount(1, $leads);
        $this->assertSame($contact2->getId(), $leads[0]->getLead()->getId());
        $this->assertSame($contact2->getFirstname(), $leads[0]->getLead()->getFirstname());
        $this->assertSame($contact2->getLastname(), $leads[0]->getLead()->getLastname());
        $this->assertSame($contact2->getEmail(), $leads[0]->getLead()->getEmail());
    }
}
