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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionProviderTest extends \PHPUnit\Framework\TestCase
{
    private $session;
    private $params;

    /**
     * @var SessionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session  = $this->createMock(Session::class);
        $this->params   = $this->createMock(CoreParametersHelper::class);
        $this->provider = new SessionProvider($this->session, $this->params);
    }

    public function testGetPage(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with(SessionProvider::KEY_PAGE, 1)
            ->willReturn(4);

        $this->assertSame(4, $this->provider->getPage());
    }

    public function testGetPageLimit(): void
    {
        $this->params->expects($this->once())
            ->method('get')
            ->with('default_pagelimit')
            ->willReturn(5);

        $this->session->expects($this->once())
            ->method('get')
            ->with(SessionProvider::KEY_LIMIT, 5)
            ->willReturn(30);

        $this->assertSame(30, $this->provider->getPageLimit());
    }

    public function testGetOrderBy(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with(SessionProvider::KEY_ORDER_BY, 'id')
            ->willReturn('name');

        $this->assertSame('name', $this->provider->getOrderBy('id'));
    }

    public function testGetOrderByDir(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with(SessionProvider::KEY_ORDER_BY_DIR, 'DESC')
            ->willReturn('ASC');

        $this->assertSame('ASC', $this->provider->getOrderByDir());
    }

    public function testGetFilter(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with(SessionProvider::KEY_FILTER, '')
            ->willReturn('ids:123');

        $this->assertSame('ids:123', $this->provider->getFilter());
    }

    public function testSetPage(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with(SessionProvider::KEY_PAGE, 2);

        $this->provider->setPage(2);
    }

    public function testSetPageLimit(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with(SessionProvider::KEY_LIMIT, 50);

        $this->provider->setPageLimit(50);
    }

    public function testSetOrderBy(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with(SessionProvider::KEY_ORDER_BY, 'id');

        $this->provider->setOrderBy('id');
    }

    public function testSetOrderByDir(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with(SessionProvider::KEY_ORDER_BY_DIR, 'DESC');

        $this->provider->setOrderByDir('DESC');
    }

    public function testSetFilter(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with(SessionProvider::KEY_FILTER, 'ids:123');

        $this->provider->setFilter('ids:123');
    }
}
