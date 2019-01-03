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

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class DeleteController extends CommonController
{
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param CustomItemModel $customItemModel
     * @param Session $session
     * @param TranslatorInterface $translator
     * @param CustomItemPermissionProvider $permissionProvider
     */
    public function __construct(
        CustomItemModel $customItemModel,
        Session $session,
        TranslatorInterface $translator,
        CustomItemPermissionProvider $permissionProvider
    )
    {
        $this->customItemModel    = $customItemModel;
        $this->session            = $session;
        $this->translator         = $translator;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int $objectId
     * @param int $itemId
     * 
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId, int $itemId)
    {
        try {
            $entity = $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canDelete($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $this->customItemModel->deleteEntity($entity);

        $this->session->getFlashBag()->add(
            'notice',
            $this->translator->trans(
                'mautic.core.notice.deleted',
                [
                    '%name%' => $entity->getName(),
                    '%id%'   => $entity->getId(),
                ], 
                'flashes'
            )
        );

        return $this->forward(
            'CustomObjectsBundle:CustomItem\List:list',
            [
                'objectId' => $objectId,
                'page'     => $this->session->get('custom.item.page', 1),
            ]
        );
    }
}