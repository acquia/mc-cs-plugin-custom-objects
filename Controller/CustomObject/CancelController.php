<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelController extends CommonController
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
        private SessionProviderFactory $sessionProviderFactory,
        private CustomObjectRouteProvider $routeProvider,
        private CustomObjectModel $customObjectModel
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function cancelAction(?int $objectId): Response
    {
        $page = $this->sessionProviderFactory->createObjectProvider()->getPage();

        if ($objectId) {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->customObjectModel->unlockEntity($customObject);
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($page),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController::listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                ],
            ]
        );
    }
}
