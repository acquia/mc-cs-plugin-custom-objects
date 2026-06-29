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
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
class ListController extends CommonController
{
    public function __construct(
        ManagerRegistry $doctrine,
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
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
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
        $orderBy         = $sessionProvider->getOrderBy(CustomObject::TABLE_ALIAS.'.id');
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
                    'sessionVar'     => $sessionProvider->getNamespace(),
                    'tableAlias'     => CustomObject::TABLE_ALIAS,
                    'viewRoute'      => CustomObjectRouteProvider::ROUTE_VIEW
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
