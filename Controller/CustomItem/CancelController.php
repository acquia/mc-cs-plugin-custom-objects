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

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;

class CancelController extends CommonController
{
    /**
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    public function __construct(
        SessionProviderFactory $sessionProviderFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel
    ) {
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->routeProvider          = $routeProvider;
        $this->customItemModel        = $customItemModel;
    }

    /**
     * @throws NotFoundException
     */
    public function cancelAction(int $objectId, ?int $itemId = null): Response
    {
        $page = $this->sessionProviderFactory->createItemProvider($objectId)->getPage();

        if ($itemId) {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            $this->customItemModel->unlockEntity($customItem);
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($objectId, $page),
                'viewParameters'  => ['objectId' => $objectId, 'page' => $page],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                ],
            ]
        );
    }
}
