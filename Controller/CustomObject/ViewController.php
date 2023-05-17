<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ViewController extends CommonController
{
    public function viewAction(
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        CustomObjectModel $customObjectModel,
        AuditLogModel $auditLogModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        int $objectId
    ): Response {
        $this->setRequestStack($requestStack);
        $request = $this->getCurrentRequest();

        try {
            $customObject = $customObjectModel->fetchEntity($objectId);
            $permissionProvider->canView($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $route         = $routeProvider->buildViewRoute($objectId);
        $dateRangeForm = $formFactory->create(
            DateRangeType::class,
            $request->get('daterange', []),
            ['action' => $route]
        );
        $stats = $customObjectModel->getItemsLineChartData(
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            $customObject
        );

        $auditLogs = $auditLogModel->getLogForObject(
            'customObject',
            $objectId,
            $customObject->getDateAdded(),
            10,
            'customObjects'
        );

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'customObject'  => $customObject,
                    'dateRangeForm' => $dateRangeForm->createView(),
                    'stats'         => $stats,
                    'logs'          => $auditLogs,
                ],
                'contentTemplate' => '@CustomObjects/CustomObject/detail.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'activeLink'    => "#mautic_custom_object_{$objectId}",
                    'route'         => $route,
                ],
            ]
        );
    }
}
