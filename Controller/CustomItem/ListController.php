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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Predis\Protocol\Text\RequestSerializer;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Helper\PaginationHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

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
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param CustomItemModel $customItemModel
     * @param CustomObjectModel $customObjectModel
     * @param CorePermissions $corePermissions
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->requestStack         = $requestStack;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customItemModel      = $customItemModel;
        $this->customObjectModel    = $customObjectModel;
        $this->permissionProvider   = $permissionProvider;
        $this->routeProvider        = $routeProvider;
    }

    /**
     * @param integer $objectId
     * @param integer $page
     * 
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(int $objectId, int $page = 1)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canViewAtAll();
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request      = $this->requestStack->getCurrentRequest();
        $search       = InputHelper::clean($request->get('search', $this->session->get('mautic.custom.item.filter', '')));
        $defaultlimit = (int) $this->coreParametersHelper->getParameter('default_pagelimit');
        $sessionLimit = (int) $this->session->get('mautic.custom.item.limit', $defaultlimit);
        $limit        = (int) $request->get('limit', $sessionLimit);
        $orderBy      = $this->session->get('mautic.custom.item.orderby', 'e.id');
        $orderByDir   = $this->session->get('mautic.custom.item.orderbydir', 'DESC');
        $route        = $this->routeProvider->buildListRoute($objectId, $page);

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = $this->session->get("mautic.custom.item.orderbydir", 'ASC');
            $orderByDir = ($orderByDir == 'ASC') ? 'DESC' : 'ASC';
            $this->session->set("mautic.custom.item.orderby", $orderBy);
            $this->session->set("mautic.custom.item.orderbydir", $orderByDir);
        }
        
        $entities = $this->customItemModel->fetchEntities(
            [
                'start'      => PaginationHelper::countOffset($page, $limit),
                'limit'      => $limit,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
                'filter'     => [
                    'string' => $search,
                    'force'  => [
                        [
                            'column' => 'e.customObject',
                            'value'  => $objectId,
                            'expr'   => 'eq',
                        ],
                    ],
                ],
            ]
        );
    
        $this->session->set('mautic.custom.item.page', $page);
        $this->session->set('mautic.custom.item.limit', $limit);
        $this->session->set('mautic.custom.item.filter', $search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'customObject'   => $customObject,
                    'items'          => $entities,
                    'page'           => $page,
                    'limit'          => $limit,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:list.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}