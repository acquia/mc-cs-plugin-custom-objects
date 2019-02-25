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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class CancelController extends CommonController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @param Session $session
     * @param CustomObjectRouteProvider $routeProvider
     */
    public function __construct(
        Session $session,
        CustomObjectRouteProvider $routeProvider
    )
    {
        $this->session       = $session;
        $this->routeProvider = $routeProvider;
    }

    /**
     * @todo unlock entity?
     * 
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function cancelAction(?int $objectId)
    {
        $page = $this->session->get('custom.object.page', 1);

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($page),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                ],
            ]
        );
    }
}