<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;

use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class DynamicContentSubscriberTest extends MauticMysqlTestCase
{
    private CustomObject $object;
    private CustomField $field;

    protected function setUp(): void
    {
        parent::setUp();

        // --- Create Custom Object ---
        $this->object = new CustomObject();
        $this->object->setNameSingular("TestObject");
        $this->object->setNamePlural("TestObjects");
        $this->object->setAlias("testobject");
        $this->object->setIsPublished(true);
        $this->em->persist($this->object);

        // --- Create Custom Field ---
        $this->field = new CustomField();
        $this->field->setLabel("Country");
        $this->field->setAlias("country");
        $this->field->setCustomObject($this->object);
        $this->field->setType("select");         // important
        $this->field->setIsPublished(true);

        // Create Doctrine Collection of option entities
        $options = new \Doctrine\Common\Collections\ArrayCollection();

        // Albania => AL
        $opt1 = new \MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption();
        $opt1->setLabel("Albania");
        $opt1->setValue("AL");
        $opt1->setCustomField($this->field);
        $options->add($opt1);

        // France => FR
        $opt2 = new \MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption();
        $opt2->setLabel("France");
        $opt2->setValue("FR");
        $opt2->setCustomField($this->field);
        $options->add($opt2);

        // Assign options
        $this->field->setOptions($options);

        // Persist all
        $this->em->persist($this->field);
        $this->em->persist($opt1);
        $this->em->persist($opt2);
        $this->em->flush();
    }

    public function testCustomObjectLabelIsConvertedToValue(): void
    {
        // --- Build filter using the custom field we created ---
        $filters = [[
            'object'       => 'custom_object',
            'field'        => $this->field->getId(),
            'filter_type'  => 'text',
            'filter_value' => 'Albania',
            'properties'   => [
                'options' => [
                    "Albania" => "AL",
                    "France"  => "FR",
                ],
            ],
        ]];

        // --- Mock dependencies ---
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('pluginIsEnabled')->willReturn(true);

        $queryFilterFactory = static::getContainer()->get(QueryFilterFactory::class);
        $matcher            = static::getContainer()->get(ContactFilterMatcher::class);

        $subscriber = new DynamicContentSubscriber(
            $queryFilterFactory,
            $configProvider,
            $matcher
        );

        // Dummy contact
        $contact = new Lead();

        // Build event
        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        // Execute subscriber
        $subscriber->evaluateFilters($event);

        // --- Expect the filter to be normalized ---
        // Use reflection to call normalizeCustomObjectFilters() directly
        $method = new \ReflectionMethod(DynamicContentSubscriber::class, 'normalizeCustomObjectFilters');
        $method->setAccessible(true);

        $normalized = $method->invoke($subscriber, $filters);

        $this->assertSame(
            "AL",
            $normalized[0]['filter_value'],
            'Label "Albania" was not normalized to "AL"'
        );
    }
}
