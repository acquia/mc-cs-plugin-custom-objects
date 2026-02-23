<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;

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
        RequestStack $requestStack,
        CorePermissions $security,
        private CustomObjectPermissionProvider $permissionProvider,
        private CustomObjectRouteProvider $routeProvider,
        private CustomObjectModel $customObjectModel,
        private SessionProviderFactory $sessionProviderFactory,
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function listAction(int $page = 1): Response
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $request         = $this->getCurrentRequest();
        $sessionProvider = $this->sessionProviderFactory->createObjectProvider();
        $search          = InputHelper::clean($request->get('search', $sessionProvider->getFilter()));
        $limit           = (int) $request->get('limit', $sessionProvider->getPageLimit());
        $orderBy         = $sessionProvider->getOrderBy(CustomObject::TABLE_ALIAS . '.id');
        $orderByDir      = $sessionProvider->getOrderByDir('ASC');
        $route           = $this->routeProvider->buildListRoute($page);

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $sessionProvider->setOrderBy($orderBy);
            $sessionProvider->setOrderByDir($orderByDir);
        }

        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);
        $tableConfig->addParameter('search', $search);

        $sessionProvider->setPage($page);
        $sessionProvider->setPageLimit($limit);
        $sessionProvider->setFilter($search);

        $permissions = $this->security->isGranted(
            [
                'custom_objects:custom_objects:view',
                'custom_objects:custom_objects:viewown',
                'custom_objects:custom_objects:viewother',
                'custom_objects:custom_objects:create',
                'custom_objects:custom_objects:edit',
                'custom_objects:custom_objects:editown',
                'custom_objects:custom_objects:editother',
                'custom_objects:custom_objects:delete',
                'custom_objects:custom_objects:deleteown',
                'custom_objects:custom_objects:deleteother',
            ],
            'RETURN_ARRAY'
        );

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'     => $search,
                    'items'           => $this->customObjectModel->getTableData($tableConfig),
                    'count'           => $this->customObjectModel->getCountForTable($tableConfig),
                    'page'            => $page,
                    'limit'           => $limit,
                    'tmpl'            => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'sessionVar'      => $sessionProvider->getNamespace(),
                    'tableAlias'      => CustomObject::TABLE_ALIAS,
                    'viewRoute'       => CustomObjectRouteProvider::ROUTE_VIEW,
                    'permissionBase'  => 'custom_objects:custom_objects',
                    'permissions'     => $permissions,
                    'indexRoute'      => CustomObjectRouteProvider::ROUTE_LIST,
                    'translationBase' => 'mautic.custom.object',
                ],
                'contentTemplate' => '@CustomObjects/CustomObject/list.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }
}
