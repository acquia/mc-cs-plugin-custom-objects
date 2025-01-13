<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiselectTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;

    /**
     * @var MultiselectType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldType  = new MultiselectType(
            $this->translator,
            $this->createMock(FilterOperatorProviderInterface::class),
            new CsvHelper()
        );
    }

    public function testUsePlaceholder(): void
    {
        $this->assertTrue($this->fieldType->usePlaceholder());
    }
}
