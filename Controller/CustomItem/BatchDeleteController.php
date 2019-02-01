<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
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
use Symfony\Component\HttpFoundation\RequestStack;

class BatchDeleteController extends CommonController
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
     * @var Session
     */
    private $session;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param RequestStack $requestStack
     * @param CustomItemModel $customItemModel
     * @param Session $session
     * @param TranslatorInterface $translator
     * @param CustomItemPermissionProvider $permissionProvider
     */
    public function __construct(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        Session $session,
        TranslatorInterface $translator,
        CustomItemPermissionProvider $permissionProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->customItemModel    = $customItemModel;
        $this->session            = $session;
        $this->translator         = $translator;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId)
    {
        $request  = $this->requestStack->getCurrentRequest();
        $itemIds  = json_decode($request->get('ids', '[]'), true);
        $notFound = [];
        $denied   = [];
        $deleted  = [];

        foreach ($itemIds as $itemId) {
            try {
                $entity = $this->customItemModel->fetchEntity((int) $itemId);
                $this->permissionProvider->canDelete($entity);
                $this->customItemModel->deleteEntity($entity);
                $deleted[] = $itemId;
            } catch (NotFoundException $e) {
                $notFound[] = $itemId;
            } catch (ForbiddenException $e) {
                $denied[] = $itemId;
            }
        }

        if ($deleted) {
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    'mautic.core.notice.batch_deleted',
                    ['%count%' => count($deleted)], 
                    'flashes'
                )
            );
        }

        if ($notFound) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    'custom.item.error.items.not.found',
                    ['%ids%' => implode(',', $notFound)], 
                    'flashes'
                )
            );
        }

        if ($denied) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    'custom.item.error.items.denied',
                    ['%ids%' => implode(',', $denied)], 
                    'flashes'
                )
            );
        }

        return $this->forward(
            'CustomObjectsBundle:CustomItem\List:list',
            [
                'objectId' => $objectId,
                'page'     => $this->session->get('custom.item.page', 1),
            ]
        );
    }
}