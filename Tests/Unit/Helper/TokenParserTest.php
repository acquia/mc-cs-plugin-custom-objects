<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\DTO\Token;
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;

class TokenParserTest extends \PHPUnit\Framework\TestCase
{
    public function testFindTokens(): void
    {
        $tokenParser = new TokenParser();
        $content     = <<<TEXT

a bunch of content goes {custom-object=order:due-date | where=due-date > today | order= -due-date|limit=10 | default=No order is due}here <br />
with lots of HTML tags and JS scripts

{custom-object=product : sku | where=segment-filter |order=latest|limit=1 | default=Nothing to see here}
{custom-object= product : sku}


Invalid tokens:
{custom-object= product}
{custom-object = product : sku}
(custom-object = product : sku)
{custom-object = unicorn}
{unicorn-object = unicorn}

TEXT;

        $tokens = $tokenParser->findTokens($content);

        $this->assertCount(3, $tokens);

        /** @var Token $token1 */
        $token1 = $tokens->current();

        $this->assertSame('{custom-object=order:due-date | where=due-date > today | order= -due-date|limit=10 | default=No order is due}', $token1->getToken());
        $this->assertSame('due-date', $token1->getCustomFieldAlias());
        $this->assertSame('order', $token1->getCustomObjectAlias());
        $this->assertSame('No order is due', $token1->getDefaultValue());
        $this->assertSame(10, $token1->getLimit());
        $this->assertSame('-due-date', $token1->getOrder());
        $this->assertSame('due-date > today', $token1->getWhere());

        $tokens->next();

        /** @var Token $token2 */
        $token2 = $tokens->current();

        $this->assertSame('{custom-object=product : sku | where=segment-filter |order=latest|limit=1 | default=Nothing to see here}', $token2->getToken());
        $this->assertSame('sku', $token2->getCustomFieldAlias());
        $this->assertSame('product', $token2->getCustomObjectAlias());
        $this->assertSame('Nothing to see here', $token2->getDefaultValue());
        $this->assertSame(1, $token2->getLimit());
        $this->assertSame('latest', $token2->getOrder());
        $this->assertSame('segment-filter', $token2->getWhere());

        $tokens->next();

        /** @var Token $token3 */
        $token3 = $tokens->current();

        $this->assertSame('{custom-object= product : sku}', $token3->getToken());
        $this->assertSame('sku', $token3->getCustomFieldAlias());
        $this->assertSame('product', $token3->getCustomObjectAlias());
        $this->assertSame('', $token3->getDefaultValue());
        $this->assertSame(1, $token3->getLimit());
        $this->assertSame('latest', $token3->getOrder());
        $this->assertSame('', $token3->getWhere());
    }
}
