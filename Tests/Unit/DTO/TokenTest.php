<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\DTO;

use MauticPlugin\CustomObjectsBundle\DTO\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function testAll(): void
    {
        $tokenString = '{}';

        $token = new Token($tokenString);
        $this->assertSame($tokenString, $token->getToken());

        // Test default values
        $this->assertSame(1, $token->getLimit());
        $this->assertSame('', $token->getWhere());
        $this->assertSame('latest', $token->getOrder());
        $this->assertSame('', $token->getDefaultValue());
        $this->assertSame('', $token->getFormat());
        $this->assertSame('', $token->getCustomFieldAlias());
        $this->assertSame('', $token->getCustomObjectAlias());

        // Test getters & setters
        $token->setLimit(2);
        $this->assertSame(2, $token->getLimit());

        $token->setWhere('somewhere');
        $this->assertSame('somewhere', $token->getWhere());

        $token->setOrder('someorder');
        $this->assertSame('someorder', $token->getOrder());

        $token->setDefaultValue('someDefaultValue');
        $this->assertSame('someDefaultValue', $token->getDefaultValue());

        $token->setFormat('someFormat');
        $this->assertSame('someFormat', $token->getFormat());

        $token->setCustomFieldAlias('someCustomFieldAlias');
        $this->assertSame('someCustomFieldAlias', $token->getCustomFieldAlias());

        $token->setCustomObjectAlias('someCuastomFieldObjectAlias');
        $this->assertSame('someCuastomFieldObjectAlias', $token->getCustomObjectAlias());
    }
}
