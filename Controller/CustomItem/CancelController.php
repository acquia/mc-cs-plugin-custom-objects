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
;

use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
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
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param Session $session
     * @param CustomItemModel $customItemModel
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        Session $session,
        CustomItemModel $customItemModel,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->session           = $session;
        $this->customItemModel = $customItemModel;
        $this->routeProvider     = $routeProvider;
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
        $page = $this->session->get('custom.item.page', 1);

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($page),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                ],
            ]
        );
    }
}