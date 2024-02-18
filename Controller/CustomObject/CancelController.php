<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;

class CancelController extends CommonController
{
    /**
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    public function __construct(
        SessionProviderFactory $sessionProviderFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    ) {
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->routeProvider          = $routeProvider;
        $this->customObjectModel      = $customObjectModel;
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
                'contentTemplate' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController:listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                ],
            ]
        );
    }
}
