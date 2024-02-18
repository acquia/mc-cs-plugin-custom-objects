<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewController extends CommonController
{
    public function viewAction(
        Request $request,
        FormFactoryInterface $formFactory,
        CustomItemModel $customItemModel,
        CustomItemXrefContactModel $customItemXrefContactModel,
        AuditLogModel $auditLogModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        int $objectId,
        int $itemId
    ): Response {
        try {
            $customItem = $customItemModel->fetchEntity($itemId);
            $permissionProvider->canView($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $route         = $routeProvider->buildViewRoute($objectId, $itemId);
        $dateRangeForm = $formFactory->create(
            DateRangeType::class,
            $request->get('daterange', []),
            ['action' => $route]
        );
        $stats = $customItemXrefContactModel->getLinksLineChartData(
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            $customItem
        );

        $auditLogs = $auditLogModel->getLogForObject('customItem', $itemId, $customItem->getDateAdded(), 10, 'customObjects');

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'item'          => $customItem,
                    'dateRangeForm' => $dateRangeForm->createView(),
                    'stats'         => $stats,
                    'logs'          => $auditLogs,
                    'contacts'      => $this->forward(
                        'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ContactListController::listAction',
                        [
                            'objectId'   => $itemId,
                            'page'       => 1,
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                ],
                'contentTemplate' => '@CustomObjects/CustomItem/detail.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'activeLink'    => "#mautic_custom_object_{$objectId}",
                    'route'         => $route,
                ],
            ]
        );
    }
}
