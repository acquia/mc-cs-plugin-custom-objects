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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureModel;
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
     * @var CustomObjectStructureModel
     */
    private $customObjectStructureModel;

    /**
     * @param Session $session
     * @param CustomObjectStructureModel $customObjectStructureModel
     */
    public function __construct(
        Session $session,
        CustomObjectStructureModel $customObjectStructureModel
    )
    {
        $this->session                    = $session;
        $this->customObjectStructureModel = $customObjectStructureModel;
    }

    /**
     * @todo implement permissions
     * @todo unlock entity?
     * 
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function cancelAction(?int $objectId)
    {
        $viewParameters = [
            'page' => $this->session->get('custom.object.structures.page', 1),
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_custom_object_structures_list', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureList:list',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                ],
            ]
        );
    }
}