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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;
use Symfony\Component\HttpFoundation\Response;

class CancelController extends CommonController
{
    /**
     * @var CustomObjectSessionProvider
     */
    private $sessionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    public function __construct(
        CustomObjectSessionProvider $sessionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    ) {
        $this->sessionProvider   = $sessionProvider;
        $this->routeProvider     = $routeProvider;
        $this->customObjectModel = $customObjectModel;
    }

    public function cancelAction(?int $objectId): Response
    {
        $page = $this->sessionProvider->getPage();

        if ($objectId) {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->customObjectModel->unlockEntity($customObject);
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($page),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                ],
            ]
        );
    }
}
