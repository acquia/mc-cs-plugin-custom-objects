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

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;

class CancelController extends CommonController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param Session $session
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        Session $session,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->session         = $session;
        $this->routeProvider   = $routeProvider;
    }

    /**
     * @todo unlock entity?
     *
     * @param int      $objectId
     * @param int|null $itemId
     *
     * @return Response|JsonResponse
     */
    public function cancelAction(int $objectId, int $itemId = null)
    {
        $page = $this->session->get('custom.item.page', 1);

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