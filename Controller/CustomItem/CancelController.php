<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;

class CancelController extends CommonController
{
    /**
     * @var CustomItemSessionProvider
     */
    private $sessionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param CustomItemSessionProvider $sessionProvider
     * @param CustomItemRouteProvider   $routeProvider
     */
    public function __construct(
        CustomItemSessionProvider $sessionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->sessionProvider = $sessionProvider;
        $this->routeProvider   = $routeProvider;
    }

    /**
     * @todo unlock entity?
     *
     * @param int      $objectId
     * @param int|null $itemId
     *
     * @return Response
     */
    public function cancelAction(int $objectId, ?int $itemId = null)
    {
        $page = $this->sessionProvider->getPage();

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($objectId, $page),
                'viewParameters'  => ['objectId' => $objectId, 'page' => $page],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                ],
            ]
        );
    }
}
