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

use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;

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

    /**
     * @param CustomObjectSessionProvider $sessionProvider
     * @param CustomObjectRouteProvider   $routeProvider
     * @param CustomObjectModel           $customObjectModel
     */
    public function __construct(
        CustomObjectSessionProvider $sessionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    ) {
        $this->sessionProvider   = $sessionProvider;
        $this->routeProvider     = $routeProvider;
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @param int|null $objectId
     *
     * @return Response
     */
    public function cancelAction(?int $objectId): Response
    {
        $page = $this->sessionProvider->getPage();

        $customObject = $this->customObjectModel->getEntity($objectId);

        if ($customObject) {
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
