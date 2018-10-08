<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureListModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CustomObjectStructureListController extends Controller
{
    private $request;
    private $session;
    private $coreParametersHelper;
    private $customObjectStructureListModel;

    public function __construct(
        RequestStack $requestStack,// Can be 'request' Request?
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomObjectStructureListModel $customObjectStructureListModel
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customObjectStructureListModel = $customObjectStructureListModel;
    }

    public function listAction(int $page)
    {
        $search = $this->request->get('search', $this->session->get('custom.objects.filter', ''));

        $this->session->set('custom.objects.filter', $search);

        // @todo check permissions

        $viewParams = [
            'page' => $page,
        ];

        //set limits
        $limit = $this->session->get('custom.objects.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter = ['string' => $search];

        $orderBy    = $this->session->get('custom.objects.orderby', 'c.title');
        $orderByDir = $this->session->get('custom.objects.orderbydir', 'DESC');

        $entities = $this->customObjectStructureListModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($entities);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $viewParams['page'] = $lastPage;
            $this->session->set('custom.objects.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_category_index', $viewParams);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticCategoryBundle:Category:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_'.$bundle.'category_index',
                        'mauticContent' => 'category',
                    ],
                ]
            );
        }

        $this->session->set('custom.objects.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $template = 'CustomObjectsBundle:CustomObjectStructure:list.html.php';
        $parameters = [
            // 'permissionBase' => $permissionBase,
            'searchValue'    => $search,
            'items'          => $entities,
            'page'           => $page,
            'limit'          => $limit,
            // 'permissions'    => $permissions,
            'tmpl'           => $tmpl,
        ];

        return $this->render($template, $parameters, new Response(''));

        return $this->delegateView(
            [
                'returnUrl'      => $this->generateUrl('mautic_category_index', $viewParams),
                'viewParameters' => [
                    'bundle'         => $bundle,
                    'permissionBase' => $permissionBase,
                    'searchValue'    => $search,
                    'items'          => $entities,
                    'page'           => $page,
                    'limit'          => $limit,
                    'permissions'    => $permissions,
                    'tmpl'           => $tmpl,
                    'categoryTypes'  => $categoryTypes,
                ],
                'contentTemplate' => 'MauticCategoryBundle:Category:list.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_'.$bundle.'category_index',
                    'mauticContent' => 'category',
                    'route'         => $this->generateUrl('mautic_category_index', $viewParams),
                ],
            ]
        );
    }
}