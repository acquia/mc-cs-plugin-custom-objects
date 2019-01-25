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
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
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
     * @param CustomItemPermissionProvider $permissionProvider
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
     * @todo make the search filter work.
     * 
     * @param integer $objectId
     * @param integer $page
     * 
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(int $objectId, int $page = 1)
    {
        try {
            $this->permissionProvider->canViewAtAll();
            $customObject = $this->customObjectModel->fetchEntity($objectId);
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
        $orderBy      = $this->session->get('mautic.custom.item.orderby', CustomItemRepository::TABLE_ALIAS.'.id');
        $orderByDir   = $this->session->get('mautic.custom.item.orderbydir', 'DESC');
        $route        = $this->routeProvider->buildListRoute($objectId, $page);
        $contactId    = (int) $request->get('contactId');
        
        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = $this->session->get("mautic.custom.item.orderbydir", 'ASC');
            $orderByDir = ($orderByDir == 'ASC') ? 'DESC' : 'ASC';
            $this->session->set("mautic.custom.item.orderby", $orderBy);
            $this->session->set("mautic.custom.item.orderbydir", $orderByDir);
        }
        
        $tableConfig = new TableConfig($limit, $page, $orderBy, $orderByDir);
        $tableConfig->addFilter(CustomItem::class, 'customObject', $objectId);
        $tableConfig->addFilterIfNotEmpty(CustomItemXrefContact::class, 'contact', $contactId);

        $this->session->set('mautic.custom.item.page', $page);
        $this->session->set('mautic.custom.item.limit', $limit);
        $this->session->set('mautic.custom.item.filter', $search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'customObject'   => $customObject,
                    'items'          => $this->customItemModel->getTableData($tableConfig),
                    'itemCount'      => $this->customItemModel->getCountForTable($tableConfig),
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
