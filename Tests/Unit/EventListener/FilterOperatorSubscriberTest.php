<?php


namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;


use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\FilterOperatorSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

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
        //$formMock = $this->createMock(FilterPropertiesForm::class);
        //$eventMock->expects($this->once())
        //    ->method('getFilterPropertiesForm')
        //    ->willReturn($formMock);
        $this->filterOperatorSubscriber->onSegmentFilterFormHandleWithinFieldFormType($eventMock);
    }
}