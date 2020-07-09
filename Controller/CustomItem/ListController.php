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

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ListController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SessionProviderInterface
     */
    private $sessionProvider;

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

    public function __construct(
        RequestStack $requestStack,
        SessionProviderInterface $sessionProvider,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->sessionProvider    = $sessionProvider;
        $this->customItemModel    = $customItemModel;
        $this->customObjectModel  = $customObjectModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @todo make the search filter work.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(int $objectId, int $page = 1)
    {
        try {
            $this->permissionProvider->canViewAtAll($objectId);
            $customObject = $this->customObjectModel->fetchEntity($objectId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $request          = $this->requestStack->getCurrentRequest();
        $search           = InputHelper::clean($request->get('search', $this->sessionProvider->getFilter()));
        $limit            = (int) $request->get('limit', $this->sessionProvider->getPageLimit());
        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $orderBy          = $this->sessionProvider->getOrderBy(CustomItem::TABLE_ALIAS.'.id');
        $orderByDir       = $this->sessionProvider->getOrderByDir('ASC');

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $this->sessionProvider->setOrderBy($orderBy);
            $this->sessionProvider->setOrderByDir($orderByDir);
        }

        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);
        $tableConfig->addParameter('customObjectId', $objectId);
        $tableConfig->addParameter('filterEntityType', $filterEntityType);
        $tableConfig->addParameter('filterEntityId', $filterEntityId);
        $tableConfig->addParameter('search', $search);

        $this->sessionProvider->setPage($page);
        $this->sessionProvider->setPageLimit($limit);
        $this->sessionProvider->setFilter($search);

        $route    = $this->routeProvider->buildListRoute($objectId, $page);
        $response = [
            'viewParameters' => [
                'searchValue'      => $search,
                'customObject'     => $customObject,
                'filterEntityId'   => $filterEntityId,
                'filterEntityType' => $filterEntityType,
                'items'            => $this->customItemModel->getTableData($tableConfig),
                'itemCount'        => $this->customItemModel->getCountForTable($tableConfig),
                'page'             => $page,
                'limit'            => $limit,
                'tmpl'             => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
            ],
            'contentTemplate' => 'CustomObjectsBundle:CustomItem:list.html.php',
            'passthroughVars' => [
                'mauticContent' => 'customItem',
                'route'         => $route,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            $response['viewParameters']['tmpl'] = $request->get('tmpl', 'index');
        } else {
            $response['returnUrl'] = $route;
        }

        return $this->delegateView($response);
    }
}
