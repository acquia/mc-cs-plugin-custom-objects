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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\HttpFoundation\Response;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class ListController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CustomObjectSessionProvider
     */
    private $sessionProvider;

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
     * @param CustomObjectSessionProvider    $sessionProvider
     * @param CustomObjectModel              $customObjectModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        CustomObjectSessionProvider $sessionProvider,
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->sessionProvider    = $sessionProvider;
        $this->customObjectModel  = $customObjectModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
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
            return $this->accessDenied(false, $e->getMessage());
        }

        $request    = $this->requestStack->getCurrentRequest();
        $search     = InputHelper::clean($request->get('search', $this->sessionProvider->getFilter()));
        $limit      = (int) $request->get('limit', $this->sessionProvider->getPageLimit());
        $orderBy    = $this->sessionProvider->getOrderBy(CustomObject::TABLE_ALIAS.'.id');
        $orderByDir = $this->sessionProvider->getOrderByDir('ASC');
        $route      = $this->routeProvider->buildListRoute($page);

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $this->sessionProvider->setOrderBy($orderBy);
            $this->sessionProvider->setOrderByDir($orderByDir);
        }

        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);

        $this->sessionProvider->setPage($page);
        $this->sessionProvider->setPageLimit($limit);
        $this->sessionProvider->setFilter($search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'items'          => $this->customObjectModel->getTableData($tableConfig),
                    'count'          => $this->customObjectModel->getCountForTable($tableConfig),
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
