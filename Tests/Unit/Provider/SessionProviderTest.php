<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionProviderTest extends TestCase
{
    private $session;
    private $namespace;
    private $defaultPageLimit;

    /**
     * @var SessionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session          = $this->createMock(Session::class);
        $this->namespace        = 'some.namespace';
        $this->defaultPageLimit = 15;
        $this->provider         = new SessionProvider($this->session, $this->namespace, $this->defaultPageLimit);
    }

    public function testGetPage(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with($this->buildName('page'), 1)
            ->willReturn(4);

        $this->assertSame(4, $this->provider->getPage());
    }

    public function testGetPageLimit(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with($this->buildName('limit'), $this->defaultPageLimit)
            ->willReturn(30);

        $this->assertSame(30, $this->provider->getPageLimit());
    }

    public function testGetOrderBy(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with($this->buildName('orderby'), 'id')
            ->willReturn('name');

        $this->assertSame('name', $this->provider->getOrderBy('id'));
    }

    public function testGetOrderByDir(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with($this->buildName('orderbydir'), 'DESC')
            ->willReturn('ASC');

        $this->assertSame('ASC', $this->provider->getOrderByDir());
    }

    public function testGetFilter(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with($this->buildName('filter'), '')
            ->willReturn('ids:123');

        $this->assertSame('ids:123', $this->provider->getFilter());
    }

    public function testSetPage(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with($this->buildName('page'), 2);

        $this->provider->setPage(2);
    }

    public function testSetPageLimit(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with($this->buildName('limit'), 50);

        $this->provider->setPageLimit(50);
    }

    public function testSetOrderBy(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with($this->buildName('orderby'), 'id');

        $this->provider->setOrderBy('id');
    }

    public function testSetOrderByDir(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with($this->buildName('orderbydir'), 'DESC');

        $this->provider->setOrderByDir('DESC');
    }

    public function testSetFilter(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with($this->buildName('filter'), 'ids:123');

        $this->provider->setFilter('ids:123');
    }

    private function buildName(string $key): string
    {
        return $this->namespace.'.'.$key;
    }
}
