<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Exception;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;

class InvalidValueExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $exception   = new InvalidValueException('Invalid field value');
        $customField = $this->createMock(CustomField::class);

        $this->assertNull($exception->getCustomField());

        $exception->setCustomField($customField);

        $this->assertSame($customField, $exception->getCustomField());
    }
}
