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

use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureActionModel;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomObjectStructureCancelController extends CommonController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomObjectStructureActionModel
     */
    private $customObjectStructureActionModel;

    public function __construct(
        Session $session,
        CustomObjectStructureActionModel $customObjectStructureActionModel
    )
    {
        $this->session                          = $session;
        $this->customObjectStructureActionModel = $customObjectStructureActionModel;
    }

    /**
     * @todo implement permissions
     * @todo unlock entity?
     * 
     * @return Response|JsonResponse
     */
    public function redirectToList()
    {
        $viewParameters = [
            'page' => $this->session->get('custom.object.structures.page', 1),
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_custom_object_structures_list', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'custom_object_structures.list_controller:listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                ],
            ]
        );
    }
}