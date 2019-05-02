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

use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderInterface;

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

    /**
     * @param RequestStack                 $requestStack
     * @param SessionProviderInterface     $sessionProvider
     * @param CustomItemModel              $customItemModel
     * @param CustomObjectModel            $customObjectModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider      $routeProvider
     */
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
     * @param int $objectId
     * @param int $page
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
        $filterEntityType = $request->get('filterEntityType');
        $orderBy          = $this->sessionProvider->getOrderBy(CustomItemRepository::TABLE_ALIAS.'.id');
        $orderByDir       = $this->sessionProvider->getOrderByDir('ASC');

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
            $this->sessionProvider->setOrderBy($orderBy);
            $this->sessionProvider->setOrderByDir($orderByDir);
        }

        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);
        $tableConfig->addFilter(CustomItem::class, 'customObject', $objectId);

        switch ($filterEntityType) {
            case 'contact':
                $tableConfig->addFilterIfNotEmpty(CustomItemXrefContact::class, 'contact', $filterEntityId);

                break;
        }

        $this->sessionProvider->setPage($page);
        $this->sessionProvider->setPageLimit($limit);
        $this->sessionProvider->setFilter($search);

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
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            $response['viewParameters']['tmpl'] = $request->get('tmpl', 'index');
        } else {
            $route                                = $this->routeProvider->buildListRoute($objectId, $page);
            $response['returnUrl']                = $route;
            $response['passthroughVars']['route'] = $route;
        }

        return $this->delegateView($response);
    }
}
