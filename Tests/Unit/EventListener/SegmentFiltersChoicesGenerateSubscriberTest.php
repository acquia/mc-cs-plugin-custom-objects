<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\EventListener\SegmentFiltersChoicesGenerateSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentFiltersChoicesGenerateSubscriberTest extends TestCase
{
    /**
     * @var CustomObjectRepository|MockObject
     */
    private $customObjectRepository;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var ConfigProvider|MockObject
     */
    private $configProvider;

    /**
     * @var CustomFieldTypeProvider|MockObject
     */
    private $fieldTypeProvider;

    /**
     * @var SegmentFiltersChoicesGenerateSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);
        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->configProvider         = $this->createMock(ConfigProvider::class);
        $this->fieldTypeProvider      = $this->createMock(CustomFieldTypeProvider::class);

        $this->subscriber = new SegmentFiltersChoicesGenerateSubscriber(
            $this->customObjectRepository,
            $this->translator,
            $this->configProvider,
            $this->fieldTypeProvider
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => 'onGenerateSegmentFilters'],
            SegmentFiltersChoicesGenerateSubscriber::getSubscribedEvents()
        );
    }

    public function testOnGenerateSegmentFiltersPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->fieldTypeProvider->expects($this->never())
            ->method('getKeyTypeMapping');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);
        $this->subscriber->onGenerateSegmentFilters($event);
    }

    public function testOnGenerateSegmentFiltersPluginEnabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->fieldTypeProvider->expects($this->once())
            ->method('getKeyTypeMapping');

        $this->customObjectRepository->expects($this->once())
            ->method('matching')
            ->willReturn(new ArrayCollection());

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);
        $this->subscriber->onGenerateSegmentFilters($event);
    }
}
