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
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
class ViewController extends CommonController
{
    public function __construct(
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private FormFactoryInterface $formFactory,
        private CustomObjectModel $customObjectModel,
        private AuditLogModel $auditLogModel,
        private CustomObjectPermissionProvider $permissionProvider,
        private CustomObjectRouteProvider $routeProvider
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function viewAction(int $objectId): Response
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canView($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $route         = $this->routeProvider->buildViewRoute($objectId);
        $dateRangeForm = $this->formFactory->create(
            DateRangeType::class,
            $this->requestStack->getCurrentRequest()->get('daterange', []),
            ['action' => $route]
        );
        $stats = $this->customObjectModel->getItemsLineChartData(
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            $customObject
        );

        $auditLogs = $this->auditLogModel->getLogForObject(
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
                'contentTemplate' => '@CustomObjects/CustomObject/details.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'activeLink'    => "#mautic_custom_object_{$objectId}",
                    'route'         => $route,
                ],
            ]
        );
    }
}
