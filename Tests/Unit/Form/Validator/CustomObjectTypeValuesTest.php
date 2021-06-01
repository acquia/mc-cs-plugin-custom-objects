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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Validator;

use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\CustomObjectTypeValues;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\CustomObjectTypeValuesValidator;

class CustomObjectTypeValuesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomObjectTypeValues
     */
    private $constraint;

    protected function setUp(): void
    {
        $this->constraint = new CustomObjectTypeValues();
    }

    public function testValidatedBy()
    {
        $this->assertSame(CustomObjectTypeValuesValidator::class, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertSame(CustomObjectTypeValues::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
