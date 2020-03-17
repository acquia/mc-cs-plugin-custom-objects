<?php


namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;


use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\FilterOperatorSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;

class FilterOperatorSubscriberTest extends \PHPUnit\Framework\TestCase
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
        $this->customObjectModelMock    = $this->createMock(CustomObjectModel::class);
        $this->filterOperatorSubscriber = new FilterOperatorSubscriber($this->customObjectModelMock);
    }

    /**
     * Test to get all subscribed event.
     */
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = FilterOperatorSubscriber::getSubscribedEvents();
        $this->assertCount(4, $subscribedEvents, 'Expects 4 subscribed events');
    }

    /**
     * Test adding operators to a filter event.
     */
    public function testOnOperatorsGenerate(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|LeadListFiltersOperatorsEvent $eventMock */
        $eventMock = $this->createMock(LeadListFiltersOperatorsEvent);
        $eventMock->expects($this->once())
            ->method('addOperator')
            ->with('withinCustomObjects', [
                'label'       => 'custom.within.custom.objects.label',
                'expr'        => 'withinCustomObjects',
                'negate_expr' => 'notWithinCustomObjects',
            ]);
        $this->filterOperatorSubscriber->onOperatorsGenerate($eventMock);
    }

    /**
     * Test adding operators to an field event when field is generated email domain.
     */
    public function testAddWithinFieldValuesOperatorWhenGeneratedEmailDomain(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FieldOperatorsEvent $eventMock */
        $eventMock = $this->createMock(FieldOperatorsEvent::class);
        $eventMock->expects($this->once())
            ->method('getField')
            ->willReturn('generated_email_domain');
        $eventMock->expects($this->once())
            ->method('addOperator')
            ->with('withinCustomObjects');
        $this->filterOperatorSubscriber->addWithinFieldValuesOperator($eventMock);
    }

    /**
     * Test adding operators to an field event when field is not generated email domain.
     */
    public function testAddWithinFieldValuesOperatorWhenNotGeneratedEmailDomain(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FieldOperatorsEvent $eventMock */
        $eventMock = $this->createMock(FieldOperatorsEvent::class);
        $eventMock->expects($this->once())
            ->method('getField')
            ->willReturn('not_generated_email_domain');
        $eventMock->expects($this->never())
            ->method('addOperator');
        $this->filterOperatorSubscriber->addWithinFieldValuesOperator($eventMock);
    }

    /**
     * Test segment filter when operator already defined.
     */
    public function testOnSegmentFilterFormHandleWithinFieldFormTypeWhenWithinCustomObjectsIsOneOf(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FilterPropertiesTypeEvent $eventMock */
        $eventMock = $this->createMock(FilterPropertiesTypeEvent::class);
        $eventMock->expects($this->once())
            ->method('operatorIsOneOf')
            ->with('withinCustomObjects')
            ->willReturn(true);
        $eventMock->expects($this->never())
            ->method('getFilterPropertiesForm');
        $this->filterOperatorSubscriber->onSegmentFilterFormHandleWithinFieldFormType($eventMock);
    }

    /**
     * Test adding to form.
     */
    public function testOnSegmentFilterFormHandleWithinFieldFormType(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FilterPropertiesTypeEvent $eventMock */
        $eventMock = $this->createMock(FilterPropertiesTypeEvent::class);
        $eventMock->expects($this->once())
            ->method('operatorIsOneOf')
            ->with('withinCustomObjects')
            ->willReturn(false);
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock(FormInterface::class);
        $eventMock->expects($this->once())
            ->method('getFilterPropertiesForm')
            ->willReturn($formMock);
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
                'choices' => ['object_name' => ['Name' => "custom-object:1:name"]],
                'attr'    => ['class' => 'form-control'],
            ]);
        $eventMock->expects($this->once())
            ->method('stopPropagation');
        $this->filterOperatorSubscriber->onSegmentFilterFormHandleWithinFieldFormType($eventMock);
    }

    /**
     * Test builder when operator already defined.
     */
    public function testOnWithinFieldValuesBuilderWhenWithinCustomObjectsIsOneOf(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|SegmentOperatorQueryBuilderEvent $eventMock */
        $eventMock = $this->createMock(SegmentOperatorQueryBuilderEvent::class);
        $eventMock->expects($this->once())
            ->method('operatorIsOneOf')
            ->with('withinCustomObjects')
            ->willReturn(true);
        $eventMock->expects($this->never())
            ->method('getQueryBuilder');
        $this->filterOperatorSubscriber->onWithinFieldValuesBuilder($eventMock);
    }

    /**
     * Test builder.
     */
    public function testOnWithinFieldValuesBuilder(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|SegmentOperatorQueryBuilderEvent $eventMock */
        $eventMock = $this->createMock(SegmentOperatorQueryBuilderEvent::class);
        $eventMock->expects($this->once())
            ->method('operatorIsOneOf')
            ->with('withinCustomObjects')
            ->willReturn(false);
        /** @var \PHPUnit\Framework\MockObject\MockObject|ContactSegmentFilter $contactSegmentFilterMock */
        $contactSegmentFilterMock = $this->createMock(ContactSegmentFilter::class);
        $contactSegmentFilterMock->expects($this->once())
            ->method('getParameterValue')
            ->willReturn('custom-object:1:name');
        $eventMock->method('getFilter')
            ->willReturn($contactSegmentFilterMock);
        $contactSegmentFilterMock->expects($this->once())
            ->method('getField')
            ->willReturn('city');
        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('innerJoin')
            ->with('l',
                MAUTIC_TABLE_PREFIX.'custom_item',
                'ci',
                "ci.custom_object_id = 1 AND ci.name = l.city AND ci.is_published = 1");
        $eventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);
        $eventMock->expects($this->once())
            ->method('setOperatorHandled')
            ->with(true);
        $eventMock->expects($this->once())
            ->method('stopPropagation');
        $this->filterOperatorSubscriber->onWithinFieldValuesBuilder($eventMock);
    }
}