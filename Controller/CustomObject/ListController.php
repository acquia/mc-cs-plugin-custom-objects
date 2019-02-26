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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Helper\PaginationHelper;
use Symfony\Component\HttpFoundation\Response;

class ListController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @param RequestStack                   $requestStack
     * @param Session                        $session
     * @param CoreParametersHelper           $coreParametersHelper
     * @param CustomObjectModel              $customObjectModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider
    ) {
        $this->requestStack         = $requestStack;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customObjectModel    = $customObjectModel;
        $this->permissionProvider   = $permissionProvider;
        $this->routeProvider        = $routeProvider;
    }

    /**
     * @param int $page
     *
     * @return Response
     */
    public function listAction(int $page = 1): Response
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request      = $this->requestStack->getCurrentRequest();
        $search       = InputHelper::clean($request->get('search', $this->session->get('mautic.custom.object.filter', '')));
        $defaultlimit = (int) $this->coreParametersHelper->getParameter('default_pagelimit');
        $sessionLimit = (int) $this->session->get('mautic.custom.object.limit', $defaultlimit);
        $limit        = (int) $request->get('limit', $sessionLimit);
        $orderBy      = $this->session->get('mautic.custom.object.orderby', 'e.id');
        $orderByDir   = $this->session->get('mautic.custom.object.orderbydir', 'DESC');
        $route        = $this->routeProvider->buildListRoute($page);

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = $this->session->get('mautic.custom.object.orderbydir', 'ASC');
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $this->session->set('mautic.custom.object.orderby', $orderBy);
            $this->session->set('mautic.custom.object.orderbydir', $orderByDir);
        }

        $entities = $this->customObjectModel->fetchEntities(
            [
                'start'      => PaginationHelper::countOffset($page, $limit),
                'limit'      => $limit,
                'filter'     => ['string' => $search],
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $this->session->set('mautic.custom.object.page', $page);
        $this->session->set('mautic.custom.object.limit', $limit);
        $this->session->set('mautic.custom.object.filter', $search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'items'          => $entities,
                    'page'           => $page,
                    'limit'          => $limit,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:list.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }
}
