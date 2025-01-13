<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use PHPUnit\Framework\Assert;

class NegativeOperatorFilterQueryBuilderTest extends MauticMysqlTestCase
{
    private CustomItemModel $customItemModel;
    private CustomObject $customObject;
    private CustomField $customField;

    protected function setUp(): void
    {
        $mergeFilterEnabled = ' with data set "Merge filter enabled"' === $this->getDataSetAsString(false);

        if ($mergeFilterEnabled && !method_exists(ContactSegmentFilterCrate::class, 'getMergedProperty')) {
            $this->markTestSkipped();
        }

        $this->configParams['custom_object_merge_filter']                         = $mergeFilterEnabled;
        $this->configParams['custom_object_item_value_to_contact_relation_limit'] = $mergeFilterEnabled ? 0 : 3;

        parent::setUp();

        $this->customItemModel = self::$container->get('mautic.custom.model.item');
        $this->createCustomObjectWithCustomField();
        $this->em->flush();
    }

    /**
     * @return iterable<string, bool[]>
     */
    public function dataUseMergeFilter(): iterable
    {
        yield 'Merge filter enabled' => [true];
        yield 'Merge filter disabled' => [false];
    }

    /**
     * @dataProvider dataUseMergeFilter
     */
    public function testNotEqualsOperator(bool $mergeFilterEnabled): void
    {
        $this->createContactAndAttachCustomItem('abc', 'ABC');
        $this->createContactAndAttachCustomItem('xyz', 'XYZ');
        $this->createContactAndAttachCustomItem('empty', '');
        $this->createContact('unlinked');

        $segment = $this->createAndBuildSegment($this->createFilter('!=', 'abc'));

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment]);
        Assert::assertCount($mergeFilterEnabled ? 2 : 3, $members);

        $firstnames = $this->extractFirstnamesFromSegmentMembers($members);
        Assert::assertNotContains('abc', $firstnames);
        Assert::assertContains('xyz', $firstnames);
        Assert::assertContains('empty', $firstnames);

        if ($mergeFilterEnabled) {
            Assert::assertNotContains('unlinked', $firstnames);
        } else {
            Assert::assertContains('unlinked', $firstnames);
        }
    }

    /**
     * @dataProvider dataUseMergeFilter
     */
    public function testEmptyOperator(bool $mergeFilterEnabled): void
    {
        $this->createContactAndAttachCustomItem('not empty', 'Not empty');
        $this->createContactAndAttachCustomItem('empty', '');
        $this->createContact('unlinked 1');
        $this->createContact('unlinked 2');

        $segment = $this->createAndBuildSegment($this->createFilter('empty', null));

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment]);
        Assert::assertCount($mergeFilterEnabled ? 1 : 3, $members);

        $firstnames = $this->extractFirstnamesFromSegmentMembers($members);
        Assert::assertNotContains('not empty', $firstnames);
        Assert::assertContains('empty', $firstnames);

        if ($mergeFilterEnabled) {
            Assert::assertNotContains('unlinked 1', $firstnames);
            Assert::assertNotContains('unlinked 2', $firstnames);
        } else {
            Assert::assertContains('unlinked 1', $firstnames);
            Assert::assertContains('unlinked 2', $firstnames);
        }
    }

    private function createCustomItem(CustomObject $customObject, string $value, string $name): CustomItem
    {
        $customItem       = new CustomItem($customObject);
        $customFieldValue = new CustomFieldValueText($this->customField, $customItem, $value);
        $customItem->addCustomFieldValue($customFieldValue);
        $customItem->setName($name);
        $this->em->persist($customItem);
        $this->em->persist($customFieldValue);

        return $customItem;
    }

    private function createCustomObjectWithCustomField(): void
    {
        $this->customField = new CustomField();
        $this->customField->setLabel('Field');
        $this->customField->setAlias('field');
        $this->customField->setType('text');
        $this->em->persist($this->customField);

        $this->customObject = new CustomObject();
        $this->customObject->setAlias('Object');
        $this->customObject->setNameSingular('Object');
        $this->customObject->setNamePlural('Objects');
        $this->customObject->addCustomField($this->customField);
        $this->customField->setCustomObject($this->customObject);
        $this->em->persist($this->customObject);
    }

    /**
     * @param array<mixed> $filters
     */
    private function createAndBuildSegment(array $filters): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $segment->setFilters($filters);
        $this->em->persist($segment);
        $this->em->flush($segment);
        $this->em->clear();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['-i' => $segment->getId()]);
        Assert::assertSame(0, $commandTester->getStatusCode(), 'Update lead lists command was not successful');

        return $segment;
    }

    private function createContact(string $firstname): Lead
    {
        $contact = new Lead();
        $contact->setFirstname($firstname);
        $this->em->persist($contact);

        return $contact;
    }

    private function createContactAndAttachCustomItem(string $contactFirstname, string $customItemText): void
    {
        $contact = $this->createContact($contactFirstname);
        $this->em->flush();

        $customItem = $this->createCustomItem($this->customObject, $customItemText, $customItemText);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());
    }

    /**
     * @return array<mixed>
     */
    private function createFilter(string $operator, ?string $value): array
    {
        $filters = [
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->customField->getId(),
                'object'     => 'custom_object',
                'type'       => 'text',
                'operator'   => $operator,
                'properties' => [
                    'filter' => $value,
                ],
            ],
        ];

        return $filters;
    }

    /**
     * @param array<ListLead> $members
     *
     * @return array<string>
     */
    private function extractFirstnamesFromSegmentMembers(array $members): array
    {
        return array_map(fn (ListLead $segment) => $segment->getLead()->getFirstname(), $members);
    }
}
