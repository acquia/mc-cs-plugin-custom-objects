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

use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;

class MultiselectTypeTest extends \PHPUnit_Framework_TestCase
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
        $this->fieldType  = new MultiselectType($this->translator, new CsvHelper());
    }

    public function testUsePlaceholder(): void
    {
        $this->assertTrue($this->fieldType->usePlaceholder());
    }
}
