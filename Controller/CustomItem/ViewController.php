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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;

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
        private CustomItemModel $customItemModel,
        private CustomItemXrefContactModel $customItemXrefContactModel,
        private AuditLogModel $auditLogModel,
        private CustomItemPermissionProvider $permissionProvider,
        private CustomItemRouteProvider $routeProvider
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function viewAction(int $objectId, int $itemId): Response
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
        $stats = $this->customItemXrefContactModel->getLinksLineChartData(
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
                        'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ContactListController::listAction',
                        [
                            'objectId'   => $itemId,
                            'page'       => 1,
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                ],
                'contentTemplate' => '@CustomObjects/CustomItem/details.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'activeLink'    => "#mautic_custom_object_{$objectId}",
                    'route'         => $route,
                ],
            ]
        );
    }
}
