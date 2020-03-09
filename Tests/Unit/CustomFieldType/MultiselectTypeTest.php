<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;

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
