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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;

class CustomFieldFilterQueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetServiceId(): void
    {
        $this->assertSame('mautic.lead.query.builder.custom_field.value', CustomFieldFilterQueryBuilder::getServiceId());
    }
}
