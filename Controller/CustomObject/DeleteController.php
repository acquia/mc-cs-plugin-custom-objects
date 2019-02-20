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

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class DeleteController extends CommonController
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param CustomObjectModel $customObjectModel
     * @param Session $session
     * @param TranslatorInterface $translator
     * @param CustomObjectPermissionProvider $permissionProvider
     */
    public function __construct(
        CustomObjectModel $customObjectModel,
        Session $session,
        TranslatorInterface $translator,
        CustomObjectPermissionProvider $permissionProvider
    )
    {
        $this->customObjectModel = $customObjectModel;
        $this->session           = $session;
        $this->translator        = $translator;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canDelete($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $this->customObjectModel->delete($customObject);

        $this->session->getFlashBag()->add(
            'notice',
            $this->translator->trans(
                'mautic.core.notice.deleted',
                [
                    '%name%' => $customObject->getName(),
                    '%id%'   => $customObject->getId(),
                ], 
                'flashes'
            )
        );

        return $this->forward(
            'CustomObjectsBundle:CustomObject\List:list',
            ['page' => $this->session->get('custom.object.page', 1)]
        );
    }
}