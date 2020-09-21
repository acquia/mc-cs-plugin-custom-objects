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
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\InUseException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteController extends CommonController
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomObjectSessionProvider
     */
    private $sessionProvider;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomObjectSessionProvider $sessionProvider,
        FlashBag $flashBag,
        CustomObjectPermissionProvider $permissionProvider
    ) {
        $this->customObjectModel  = $customObjectModel;
        $this->sessionProvider    = $sessionProvider;
        $this->flashBag           = $flashBag;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canDelete($customObject);
            $this->customObjectModel->checkCustomObjectIsAssociated($objectId);
            $this->customObjectModel->delete($customObject);
            
            $this->flashBag->add(
                'mautic.core.notice.deleted',
                [
                    '%name%' => $customObject->getName(),
                    '%id%'   => $customObject->getId(),
                ]
            );
            
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        } catch (InUseException $e) {
            $this->flashBag->add(
                'custom.item.error.link.segment',
                [
                    '%itemName%' => $customObject->getName(),
                    '%segmentList%'   => implode(', ', $e->getSegmentList()),
                ],
                FlashBag::LEVEL_ERROR
            );
        }

        

        return $this->forward(
            'CustomObjectsBundle:CustomObject\List:list',
            ['page' => $this->sessionProvider->getPage()]
        );
    }
}
