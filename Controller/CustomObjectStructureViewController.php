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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureModel;
use Predis\Protocol\Text\RequestSerializer;
use Mautic\CoreBundle\Controller\CommonController;

class CustomObjectStructureViewController extends CommonController
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
     * @var CustomObjectStructureModel
     */
    private $customObjectStructureModel;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param CustomObjectStructureModel $customObjectStructureModel
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        CustomObjectStructureModel $customObjectStructureModel
    )
    {
        $this->requestStack               = $requestStack;
        $this->session                    = $session;
        $this->coreParametersHelper       = $coreParametersHelper;
        $this->customObjectStructureModel = $customObjectStructureModel;
    }

    /**
     * @todo check permissions
     * 
     * @param int $objectId
     * 
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function viewAction(int $objectId)
    {
        $entity = $this->customObjectStructureModel->getEntity($objectId);
        $route  = $this->generateUrl('mautic_custom_object_structures_view', ['objectId' => $objectId]);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'item' => $entity,
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureList:detail.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                    'route'         => $route,
                ],
            ]
        );
    }
}