<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;

class ListController extends CommonController
{
    public function __construct(
        ManagerRegistry $doctrine,
        MauticFactory $mauticFactory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private SessionProviderFactory $sessionProviderFactory,
        private CustomItemModel $customItemModel,
        private CustomObjectModel $customObjectModel,
        private CustomItemPermissionProvider $permissionProvider,
        private CustomItemRouteProvider $routeProvider
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
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
        $lookup           = (bool) $request->get('lookup');
        $sessionProvider  = $this->sessionProviderFactory->createItemProvider($objectId, $filterEntityType, $filterEntityId, $lookup);
        $search           = InputHelper::clean($request->get('search', $sessionProvider->getFilter()));
        $limit            = (int) $request->get('limit', $sessionProvider->getPageLimit());
        $orderBy          = $sessionProvider->getOrderBy(CustomItem::TABLE_ALIAS . '.id');
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

        $route     = $this->routeProvider->buildListRoute($objectId, $page, $filterEntityType ?: null, $filterEntityId ?: null, ['lookup' => $lookup ?: null]);
        $items     = $this->customItemModel->getTableData($tableConfig);
        $namespace = $sessionProvider->getNamespace();
        $response  = [
            'viewParameters' => [
                'searchValue'      => $search,
                'customObject'     => $customObject,
                'filterEntityId'   => $filterEntityId,
                'filterEntityType' => $filterEntityType,
                'lookup'           => $lookup,
                'items'            => $items,
                'itemCount'        => $this->customItemModel->getCountForTable($tableConfig),
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
            $response['viewParameters']['fieldData'] = $this->customItemModel->getFieldListData($customObject, $items, $filterEntityType);
        }

        if (!$request->isXmlHttpRequest()) {
            $response['returnUrl'] = $route;
        }

        return $this->delegateView($response);
    }
}
