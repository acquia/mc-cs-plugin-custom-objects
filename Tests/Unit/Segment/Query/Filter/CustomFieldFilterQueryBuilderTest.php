<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;

class CustomFieldFilterQueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetServiceId(): void
    {
        $this->assertSame('mautic.lead.query.builder.custom_field.value', CustomFieldFilterQueryBuilder::getServiceId());
    }
}
