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

    public function __construct(
        RequestStack $requestStack,
        SessionProviderFactory $sessionProviderFactory,
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider
    ) {
        $this->requestStack              = $requestStack;
        $this->sessionProviderFactory    = $sessionProviderFactory;
        $this->customObjectModel         = $customObjectModel;
        $this->permissionProvider        = $permissionProvider;
        $this->routeProvider             = $routeProvider;

        parent::setRequestStack($requestStack);
    }

    public function listAction(int $page = 1): Response
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $request         = $this->requestStack->getCurrentRequest();
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
