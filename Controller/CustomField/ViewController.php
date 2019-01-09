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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

class ViewController extends CommonController
{
    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $routeProvider;

    /**
     * @param CoreParametersHelper          $coreParametersHelper
     * @param CustomFieldModel              $customFieldModel
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider      $routeProvider
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        CustomFieldModel $customFieldModel,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customFieldModel     = $customFieldModel;
        $this->permissionProvider   = $permissionProvider;
        $this->routeProvider        = $routeProvider;
    }

    /**
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function viewAction(int $objectId)
    {
        try {
            $entity = $this->customFieldModel->fetchEntity($objectId);
            $this->permissionProvider->canView($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $route = $this->routeProvider->buildViewRoute($objectId);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => ['item' => $entity],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:detail.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }
}