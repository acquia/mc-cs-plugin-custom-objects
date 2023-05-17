<?php

declare(strict_types=1);

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
    public function listAction(
        RequestStack $requestStack,
        SessionProviderFactory $sessionProviderFactory,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        int $objectId,
        int $page = 1
    ): Response {
        $this->setRequestStack($requestStack);
        $request = $this->getCurrentRequest();

        try {
            $permissionProvider->canViewAtAll($objectId);
            $customObject = $customObjectModel->fetchEntity($objectId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $lookup           = (bool) $request->get('lookup');
        $sessionProvider  = $sessionProviderFactory->createItemProvider($objectId, $filterEntityType, $filterEntityId, $lookup);
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
        $tableConfig->addParameter('lookup', $lookup);

        $sessionProvider->setPage($page);
        $sessionProvider->setPageLimit($limit);
        $sessionProvider->setFilter($search);

        $route     = $routeProvider->buildListRoute($objectId, $page, $filterEntityType ?: null, $filterEntityId ?: null, ['lookup' => $lookup ?: null]);
        $items     = $customItemModel->getTableData($tableConfig);
        $namespace = $sessionProvider->getNamespace();
        $response  = [
            'viewParameters' => [
                'searchValue'      => $search,
                'customObject'     => $customObject,
                'filterEntityId'   => $filterEntityId,
                'filterEntityType' => $filterEntityType,
                'lookup'           => $lookup,
                'items'            => $items,
                'itemCount'        => $customItemModel->getCountForTable($tableConfig),
                'page'             => $page,
                'limit'            => $limit,
                'tmpl'             => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'currentRoute'     => $route,
                'sessionVar'       => $namespace,
                'namespace'        => $namespace,
            ],
            'contentTemplate' => '@CustomObjects/CustomItem/list.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'customItem',
                'route'         => $filterEntityType ? null : $route,
            ],
        ];

        if ($filterEntityId) {
            $response['viewParameters']['fieldData'] = $customItemModel->getFieldListData($customObject, $items, $filterEntityType);
        }

        if (!$request->isXmlHttpRequest()) {
            $response['returnUrl'] = $route;
        }

        return $this->delegateView($response);
    }
}
