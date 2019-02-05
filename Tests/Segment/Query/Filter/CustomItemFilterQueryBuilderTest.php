<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Segment\Query\Filter;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomItemFilterQueryBuilderTest extends WebTestCase
{
    public function testShowPost()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/post/hello-world');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Hello World")')->count()
        );
    }
}
