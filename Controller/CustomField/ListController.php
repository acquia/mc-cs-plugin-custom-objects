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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Predis\Protocol\Text\RequestSerializer;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;

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
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param CustomFieldModel $customFieldModel
     * @param CorePermissions $corePermissions
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomFieldModel $customFieldModel,
        CustomFieldPermissionProvider $permissionProvider
    )
    {
        $this->requestStack         = $requestStack;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customFieldModel     = $customFieldModel;
        $this->permissionProvider   = $permissionProvider;
    }

    /**
     * @param integer $page
     * 
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(int $page)
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request      = $this->requestStack->getCurrentRequest();
        $search       = InputHelper::clean($request->get('search', $this->session->get('mautic.custom.field.filter', '')));
        $defaultlimit = (int) $this->coreParametersHelper->getParameter('default_pagelimit');
        $sessionLimit = (int) $this->session->get('mautic.custom.field.limit', $defaultlimit);
        $limit        = (int) $request->get('limit', $sessionLimit);
        $viewParams   = ['page' => $page];
        $start        = ($page === 1) ? 0 : (($page - 1) * $limit);
        $start        = $start < 0 ? 0 : $start;
        $filter       = ['string' => $search];
        $orderBy      = $this->session->get('mautic.custom.field.orderby', 'e.id');
        $orderByDir   = $this->session->get('mautic.custom.field.orderbydir', 'DESC');
        $route        = $this->generateUrl('mautic_custom_field_list', $viewParams);

        if ($request->query->has('orderby')) {
            $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
            $orderByDir = $this->session->get("mautic.custom.field.orderbydir", 'ASC');
            $orderByDir = ($orderByDir == 'ASC') ? 'DESC' : 'ASC';
            $this->session->set("mautic.custom.field.orderby", $orderBy);
            $this->session->set("mautic.custom.field.orderbydir", $orderByDir);
        }
        
        $entities = $this->customFieldModel->fetchEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );
    
        $this->session->set('mautic.custom.field.page', $page);
        $this->session->set('mautic.custom.field.limit', $limit);
        $this->session->set('mautic.custom.field.filter', $search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $entities,
                    'page'        => $page,
                    'limit'       => $limit,
                    'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:list.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }
}