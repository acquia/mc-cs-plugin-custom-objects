<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Tests\ProjectVersionTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class DynamicContentSubscriberTest extends MauticMysqlTestCase
{
    use ProjectVersionTrait;

    protected function setUp(): void
    {
        if (!$this->isCloudProject()) {
            $this->markTestSkipped('DWCs are not supported in emails in Mautic 4.4');
        }

        parent::setUp();
    }

    /**
     * @return iterable<string,array{bool,mixed[]}>
     */
    public function filtersDataProvider(): iterable
    {
        yield 'Equal Animal name matches' => [true, [
            [
                'glue'     => 'and',
                'field'    => '_TO_BE_REPLACED_',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'Cat',
                'display'  => null,
                'operator' => '=',
            ],
        ]];
        yield 'Equal Animal name does not match' => [false, [
            [
                'glue'     => 'and',
                'field'    => '_TO_BE_REPLACED_',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'Giraffe',
                'display'  => null,
                'operator' => '=',
            ],
        ]];
        yield 'Not equal Animal name matches' => [true, [
            [
                'glue'     => 'and',
                'field'    => '_TO_BE_REPLACED_',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'Giraffe',
                'display'  => null,
                'operator' => '!=',
            ],
        ]];
        yield 'Not equal Animal name match' => [true, [
            [
                'glue'     => 'and',
                'field'    => '_TO_BE_REPLACED_',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'Cat',
                'display'  => null,
                'operator' => '!=',
            ],
        ]];
    }

    /**
     * @param mixed[] $filters
     * @dataProvider filtersDataProvider
     */
    public function testCustomObjectFiltersAreFollowedWhenEmailIsSent(bool $shouldMatch, array $filters): void
    {
        $contact      = $this->createContact();
        $customObject = $this->createCustomObject();
        $this->linkCustomItemWithContact('Cat', $customObject, $contact);
        $this->linkCustomItemWithContact('Dog', $customObject, $contact);
        $this->em->flush();

        $filters            = $this->replaceCustomObjectFieldInFilters($filters, $customObject);
        $dynamicContentText = $this->createDynamicContent($filters, TypeList::TEXT);
        $dynamicContentHtml = $this->createDynamicContent($filters, TypeList::HTML);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $content = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isOk(), $content);
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertSame(1, $formCrawler->count());
        $form = $formCrawler->form();
        $form->setValues([
            'lead_quickemail[subject]' => sprintf('Some subject {dwc=%s}{/dwc}', $dynamicContentText->getSlotName()),
            'lead_quickemail[body]'    => sprintf('<html><body><p>{dwc=%s}{/dwc}</p></body></html>', $dynamicContentHtml->getSlotName()),
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $email = $this->messageLogger->getMessages()[0]->toString();

        if ($shouldMatch) {
            Assert::assertStringContainsString($dynamicContentText->getContent(), $email);
            Assert::assertStringContainsString($dynamicContentHtml->getContent(), $email);
        } else {
            Assert::assertStringNotContainsString($dynamicContentText->getContent(), $email);
            Assert::assertStringNotContainsString($dynamicContentHtml->getContent(), $email);
        }
    }

    /**
     * @param mixed[] $filters
     * @dataProvider filtersDataProvider
     */
    public function testCustomObjectFiltersAreFollowedInPagePreview(bool $shouldMatch, array $filters): void
    {
        $contact      = $this->createContact();
        $customObject = $this->createCustomObject();
        $this->linkCustomItemWithContact('Cat', $customObject, $contact);
        $this->linkCustomItemWithContact('Dog', $customObject, $contact);
        $this->em->flush();

        $filters        = $this->replaceCustomObjectFieldInFilters($filters, $customObject);
        $dynamicContent = $this->createDynamicContent($filters, TypeList::HTML);
        $page           = $this->createPage(sprintf('<html><body><p>{dwc=%s}{/dwc}</p></body></html>', $dynamicContent->getSlotName()));
        $this->em->flush();

        $this->loginUser('admin');
        $this->client->request(Request::METHOD_GET, sprintf('/page/preview/%d?contactId=%d', $page->getId(), $contact->getId()));
        $content = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isOk(), $content);

        if ($shouldMatch) {
            Assert::assertStringContainsString($dynamicContent->getContent(), $content);
        } else {
            Assert::assertStringNotContainsString($dynamicContent->getContent(), $content);
        }
    }

    /**
     * @param mixed[] $filters
     */
    private function createDynamicContent(array $filters, string $type): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Name');
        $dynamicContent->setContent(sprintf('DWC content of type %s', $type));
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName(sprintf('slot-name-%s', $type));
        $dynamicContent->setFilters($filters);
        $dynamicContent->setType($type);
        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('charlie@kid.tld');
        $this->em->persist($contact);

        return $contact;
    }

    private function createCustomObject(): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular('Animal');
        $customObject->setNamePlural('Animals');
        $customObject->setAlias('animal');
        $this->em->persist($customObject);

        return $customObject;
    }

    private function linkCustomItemWithContact(string $name, CustomObject $customObject, Lead $contact): void
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($name);
        $this->em->persist($customItem);
        $this->em->persist(new CustomItemXrefContact($customItem, $contact));
    }

    /**
     * @param mixed[] $filters
     *
     * @return mixed[]
     */
    private function replaceCustomObjectFieldInFilters(array $filters, CustomObject $customObject): array
    {
        foreach ($filters as &$filter) {
            $filter['field'] = sprintf('cmo_%d', $customObject->getId());
        }

        return $filters;
    }

    private function createPage(string $html): Page
    {
        $page = new Page();
        $page->setTitle('Page');
        $page->setAlias('page');
        $page->setCustomHtml($html);
        $this->em->persist($page);

        return $page;
    }
}
