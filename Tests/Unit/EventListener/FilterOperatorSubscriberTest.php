<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\FilterOperatorSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FilterOperatorSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CustomObjectModel
     */
    private $customObjectModelMock;

    /**
     * @var FilterOperatorSubscriber
     */
    private $filterOperatorSubscriber;

    protected function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');
        $this->customObjectModelMock    = $this->createMock(CustomObjectModel::class);
        $this->filterOperatorSubscriber = new FilterOperatorSubscriber($this->customObjectModelMock);
    }

    /**
     * Test to get all subscribed event.
     */
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = FilterOperatorSubscriber::getSubscribedEvents();
        $this->assertCount(5, $subscribedEvents, 'Expects 5 subscribed events');
    }

    /**
     * Test adding an operator to a filter event.
     */
    public function testOnOperatorsGenerate(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface translatorInterfaceMock */
        $translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
        $event                   = new LeadListFiltersOperatorsEvent([], $translatorInterfaceMock);
        $this->assertCount(0, $event->getOperators(), 'Expects 0 operators');
        $this->filterOperatorSubscriber->onOperatorsGenerate($event);
        $this->assertCount(1, $event->getOperators(), 'Expects 1 operator');
    }

    /**
     * Test adding operators to an field event when field is generated email domain.
     */
    public function testAddWithinFieldValuesOperatorWhenGeneratedEmailDomain(): void
    {
        $event = new FieldOperatorsEvent(
            'choice',
            'generated_email_domain',
            ['withinCustomObjects' => [
                'label' => 'custom_label', ],
            ],
            []
        );
        $this->assertEmpty($event->getOperators());
        $this->filterOperatorSubscriber->addWithinFieldValuesOperator($event);
        $this->assertContains('withinCustomObjects', $event->getOperators());
    }

    /**
     * Test adding operators to an field event when field is not generated email domain. Nothing added.
     */
    public function testAddWithinFieldValuesOperatorWhenNotGeneratedEmailDomain(): void
    {
        $event = new FieldOperatorsEvent(
            'choice',
            'not_generated_email_domain',
            ['withinCustomObjects' => [
                'label' => 'custom_label', ],
            ],
            []
        );
        $this->assertEmpty($event->getOperators());
        $this->filterOperatorSubscriber->addWithinFieldValuesOperator($event);
        $this->assertEmpty($event->getOperators());
    }

    /**
     * Test segment filter when different operator. Method add on form is never called.
     */
    public function testOnSegmentFilterFormHandleWithinFieldFormTypeWhenWithinCustomObjectsIsOneOf(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock(FormInterface::class);
        $event    = new FormAdjustmentEvent(
            $formMock,
            'town',
            'Object',
            'NotWithinCustomObjects',
            []
        );
        $this->filterOperatorSubscriber->onSegmentFilterFormHandleWithinFieldFormType($event);
        $formMock->expects($this->never())
            ->method('add')
            ->withAnyParameters();
    }

    /**
     * Test adding to form.
     */
    public function testOnSegmentFilterFormHandleWithinFieldFormTypeU(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock(FormInterface::class);
        $event    = new FormAdjustmentEvent($formMock, 'town', 'Object', 'withinCustomObjects', []);
        /** @var \PHPUnit\Framework\MockObject\MockObject|CustomObject $customObjectMock */
        $customObjectMock = $this->createMock(CustomObject::class);
        $this->customObjectModelMock->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObjectMock]);
        $customObjectMock->expects($this->once())
            ->method('getNameSingular')
            ->willReturn('object_name');
        $customObjectMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $formMock->expects($this->once())
            ->method('getData')
            ->willReturn(['filter' => '']);
        $formMock->expects($this->once())
            ->method('add')
            ->with('filter', ChoiceType::class, [
                'label'   => false,
                'data'    => '',
                'choices' => ['object_name' => ['Name' => 'custom-object:1:name']],
                'attr'    => ['class' => 'form-control'],
            ]);
        $this->filterOperatorSubscriber->onSegmentFilterFormHandleWithinFieldFormType($event);
    }

    /**
     * Test builder when different operator. It breaks and never calls getParameterValue.
     */
    public function testOnWithinFieldValuesBuilderWhenWithinCustomObjectsIsOneOf(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $queryBuilderMock->method('getTableAlias')->willReturn('t');

        /** @var \PHPUnit\Framework\MockObject\MockObject|ContactSegmentFilter $contactSegmentFilterMock */
        $contactSegmentFilterMock = $this->createMock(ContactSegmentFilter::class);
        $contactSegmentFilterMock->expects($this->once())
            ->method('getOperator')
            ->willReturn('notWithinCustomObjects');
        $event = new SegmentOperatorQueryBuilderEvent($queryBuilderMock, $contactSegmentFilterMock, []);
        $this->filterOperatorSubscriber->onWithinFieldValuesBuilder($event);
        $contactSegmentFilterMock->expects($this->never())
            ->method('getParameterValue');
    }

    /**
     * Test builder.
     */
    public function testOnWithinFieldValuesBuilder(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ContactSegmentFilter $contactSegmentFilterMock */
        $contactSegmentFilterMock = $this->createMock(ContactSegmentFilter::class);
        $contactSegmentFilterMock->expects($this->once())
            ->method('getParameterValue')
            ->willReturn('custom-object:1:name');
        $contactSegmentFilterMock->expects($this->once())
            ->method('getField')
            ->willReturn('city');
        $contactSegmentFilterMock
            ->method('getOperator')
            ->willReturn('withinCustomObjects');
        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getTableAlias')->willReturn('l');
        $queryBuilderMock->expects($this->once())
            ->method('innerJoin')
            ->with(
                'l',
                MAUTIC_TABLE_PREFIX.'custom_item',
                'ci',
                'ci.custom_object_id = 1 AND ci.name = l.city AND ci.is_published = 1'
            );
        $event = new SegmentOperatorQueryBuilderEvent($queryBuilderMock, $contactSegmentFilterMock, []);
        $this->filterOperatorSubscriber->onWithinFieldValuesBuilder($event);
    }
}
