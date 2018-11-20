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

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Predis\Protocol\Text\RequestSerializer;
use Mautic\CoreBundle\Controller\CommonController;

class CustomObjectListController extends CommonController
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
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomObjectModel $customObjectModel
    )
    {
        $this->requestStack               = $requestStack;
        $this->session                    = $session;
        $this->coreParametersHelper       = $coreParametersHelper;
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @param integer $page
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(int $page)
    {
        $request    = $this->requestStack->getCurrentRequest();
        $search     = $request->get('search', $this->session->get('custom.object.filter', ''));
        $viewParams = ['page' => $page];

        $this->session->set('custom.object.filter', $search);

        // @todo check permissions


        //set limits
        $limit = $this->session->get('custom.object.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter     = ['string' => $search];
        $orderBy    = $this->session->get('custom.object.orderby', 'e.id');
        $orderByDir = $this->session->get('custom.object.orderbydir', 'DESC');

        $entities = $this->customObjectModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($entities);
        $route = $this->generateUrl('mautic_custom_object_list', $viewParams);

        // if ($count && $count < ($start + 1)) {
        //     //the number of entities are now less then the current page so redirect to the last page
        //     if ($count === 1) {
        //         $lastPage = 1;
        //     } else {
        //         $lastPage = (ceil($count / $limit)) ?: 1;
        //     }
        //     $viewParams['page'] = $lastPage;
        //     $this->session->set('custom.object.page', $lastPage);
        //     $route = $this->generateUrl('mautic_custom_object_list', $viewParams);

        //     return $this->postActionRedirect(
        //         [
        //             'returnUrl'       => $route,
        //             'viewParameters'  => ['page' => $lastPage],
        //             'contentTemplate' => 'CustomObjectsBundle:CustomObject:list.html.php',
        //             'passthroughVars' => [
        //                 'mauticContent' => 'customObject',
        //                 'route'         => $route,
        //             ],
        //         ]
        //     );
        // }

        $this->session->set('custom.object.page', $page);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'items'          => $entities,
                    'page'           => $page,
                    'limit'          => $limit,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:list.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }
}