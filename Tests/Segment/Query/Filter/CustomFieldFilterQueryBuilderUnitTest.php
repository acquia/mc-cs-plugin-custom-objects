<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;

class CustomFieldFilterQueryBuilderUnitTest extends \PHPUnit_Framework_TestCase
{
    private $randomParameterName;
    private $customFieldFilterQueryBuilder;

    protected function setUp()
    {
        parent::setUp();

        $this->randomParameterName           = $this->createMock(RandomParameterName::class);
        $this->customFieldFilterQueryBuilder = new CustomFieldFilterQueryBuilder($this->randomParameterName);
    }

    public function testGetParametersAliasesForArrays()
    {
        $this->randomParameterName->expects($this->exactly(2))
            ->method('generateRandomParameterName')
            ->willReturn('randomAliasString');
        $this->assertSame(
            ['randomAliasString', 'randomAliasString'],
            $this->customFieldFilterQueryBuilder->getParametersAliases(['one', 'two'])
        );
    }

    public function testGetParametersAliasesForStrings()
    {
        $this->randomParameterName->expects($this->once())
            ->method('generateRandomParameterName')
            ->willReturn('randomAliasString');
        $this->assertSame(
            'randomAliasString',
            $this->customFieldFilterQueryBuilder->getParametersAliases('one')
        );
    }
}
