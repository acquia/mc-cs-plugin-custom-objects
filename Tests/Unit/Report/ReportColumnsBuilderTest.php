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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Report;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Report\ReportColumnsBuilder;
use PHPUnit\Framework\TestCase;

class ReportColumnsBuilderTest extends TestCase
{
    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var ReportColumnsBuilder
     */
    private $reportColumnsBuilder;

    protected function setUp()
    {
        parent::setUp();

        $this->customObject = $this->createMock(CustomObject::class);
        $this->reportColumnsBuilder = new ReportColumnsBuilder($this->customObject);
    }

    private function getCustomFieldsCollection(): array
    {
        $label1 = uniqid();
        $customField1 = new CustomField();
        $customField1->setId(1);
        $customField1->setLabel($label1);
        $customField1->setType('string');

        $label2 = uniqid();
        $customField2 = new CustomField();
        $customField2->setId(2);
        $customField2->setLabel($label2);
        $customField2->setType('int');

        $collection = new ArrayCollection([
            $customField1,
            $customField2,
        ]);

        return [$collection, $label1, $label2];
    }

    public function testThatGetColumnsMethodReturnsCorrectColumns(): void
    {
        [$collection, $label1, $label2] = $this->getCustomFieldsCollection();

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn($collection);

        $columns = $this->reportColumnsBuilder->getColumns();

        $this->assertSame($columns, [
            'c4ca4238.value' => [
                'label' => $label1,
                'type' => 'string',
            ],
            'c81e728d.value' => [
                'label' => $label2,
                'type' => 'int',
            ],
        ]);
    }
}
