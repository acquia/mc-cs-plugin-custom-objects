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
use Symfony\Component\Form\FormFactory;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Model\AuditLogModel;

class ViewController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @param RequestStack $requestStack
     * @param FormFactory $formFactory
     * @param CustomItemModel $customItemModel
     * @param AuditLogModel $auditLogModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        FormFactory $formFactory,
        CustomItemModel $customItemModel,
        AuditLogModel $auditLogModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->formFactory        = $formFactory;
        $this->customItemModel    = $customItemModel;
        $this->auditLogModel      = $auditLogModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int $objectId
     * @param int $itemId
     *
     * @return Response
     */
    public function viewAction(int $objectId, int $itemId)
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canView($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $route         = $this->routeProvider->buildViewRoute($objectId, $itemId);
        $dateRangeForm = $this->formFactory->create(
            DateRangeType::class,
            $this->requestStack->getCurrentRequest()->get('daterange', []),
            ['action' => $route]
        );
        $stats = $this->customItemModel->getLinksLineChartData(
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            $customItem
        );

        $auditLogs = $this->auditLogModel->getLogForObject('customItem', $itemId, $customItem->getDateAdded(), 10, 'customObjects');

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'item'          => $customItem,
                    'dateRangeForm' => $dateRangeForm->createView(),
                    'stats'         => $stats,
                    'logs'          => $auditLogs,
                    'contacts'      => $this->forward(
                        'CustomObjectsBundle:CustomItem\ContactList:list',
                        [
                            'objectId'   => $itemId,
                            'page'       => 1,
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:detail.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'activeLink'    => "#mautic_custom_object_{$objectId}",
                    'route'         => $route,
                ],
            ]
        );
    }
}