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
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ListController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

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
        SessionProviderFactory $sessionProviderFactory,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->requestStack           = $requestStack;
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->customItemModel        = $customItemModel;
        $this->customObjectModel      = $customObjectModel;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
    }

    public function listAction(int $objectId, int $page = 1): Response
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
        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $sessionProvider  = $this->sessionProviderFactory->createItemProvider($objectId, $filterEntityType, $filterEntityId);
        $search           = InputHelper::clean($request->get('search', $sessionProvider->getFilter()));
        $limit            = (int) $request->get('limit', $sessionProvider->getPageLimit());
        $orderBy          = $sessionProvider->getOrderBy(CustomItem::TABLE_ALIAS.'.id');
        $orderByDir       = $sessionProvider->getOrderByDir('ASC');

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $sessionProvider->setOrderBy($orderBy);
            $sessionProvider->setOrderByDir($orderByDir);
        }

        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);
        $tableConfig->addParameter('customObjectId', $objectId);
        $tableConfig->addParameter('filterEntityType', $filterEntityType);
        $tableConfig->addParameter('filterEntityId', $filterEntityId);
        $tableConfig->addParameter('search', $search);

        $sessionProvider->setPage($page);
        $sessionProvider->setPageLimit($limit);
        $sessionProvider->setFilter($search);

        $route    = $this->routeProvider->buildListRoute($objectId, $page);
        $items    = $this->customItemModel->getTableData($tableConfig);
        $response = [
            'viewParameters' => [
                'searchValue'      => $search,
                'customObject'     => $customObject,
                'filterEntityId'   => $filterEntityId,
                'filterEntityType' => $filterEntityType,
                'items'            => $items,
                'itemCount'        => $this->customItemModel->getCountForTable($tableConfig),
                'page'             => $page,
                'limit'            => $limit,
                'tmpl'             => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'currentRoute'     => $route,
            ],
            'contentTemplate' => 'CustomObjectsBundle:CustomItem:list.html.php',
            'passthroughVars' => [
                'mauticContent' => 'customItem',
                'route'         => $filterEntityType ? null : $route,
            ],
        ];

        if ($filterEntityId) {
            $response['viewParameters']['fieldData'] = $this->customItemModel->getFieldListData($customObject, $items, $filterEntityType);
        }

        if (!$request->isXmlHttpRequest()) {
            $response['returnUrl'] = $route;
        }

        return $this->delegateView($response);
    }
}
