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
     * @var CustomItemXrefContactModel
     */
    private $customItemXrefContactModel;

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
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        CustomItemModel $customItemModel,
        CustomItemXrefContactModel $customItemXrefContactModel,
        AuditLogModel $auditLogModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->requestStack               = $requestStack;
        $this->formFactory                = $formFactory;
        $this->customItemModel            = $customItemModel;
        $this->customItemXrefContactModel = $customItemXrefContactModel;
        $this->auditLogModel              = $auditLogModel;
        $this->permissionProvider         = $permissionProvider;
        $this->routeProvider              = $routeProvider;

        parent::setRequestStack($requestStack);
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
