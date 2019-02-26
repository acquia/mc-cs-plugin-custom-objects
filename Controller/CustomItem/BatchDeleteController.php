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
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;

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
     * @var CustomItemSessionProvider
     */
    private $sessionProvider;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param RequestStack                 $requestStack
     * @param CustomItemModel              $customItemModel
     * @param CustomItemSessionProvider    $sessionProvider
     * @param TranslatorInterface          $translator
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider      $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        CustomItemSessionProvider $sessionProvider,
        TranslatorInterface $translator,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->customItemModel    = $customItemModel;
        $this->sessionProvider    = $sessionProvider;
        $this->translator         = $translator;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int $objectId
     *
     * @return Response
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
                $customItem = $this->customItemModel->fetchEntity((int) $itemId);
                $this->permissionProvider->canDelete($customItem);
                $this->customItemModel->delete($customItem);
                $deleted[] = $itemId;
            } catch (NotFoundException $e) {
                $notFound[] = $itemId;
            } catch (ForbiddenException $e) {
                $denied[] = $itemId;
            }
        }

        if ($deleted) {
            $this->sessionProvider->addFlash(
                $this->translator->trans(
                    'mautic.core.notice.batch_deleted',
                    ['%count%' => count($deleted)],
                    'flashes'
                )
            );
        }

        if ($notFound) {
            $this->sessionProvider->addFlash(
                $this->translator->trans(
                    'custom.item.error.items.not.found',
                    ['%ids%' => implode(',', $notFound)],
                    'flashes'
                ),
                'error'
            );
        }

        if ($denied) {
            $this->sessionProvider->addFlash(
                $this->translator->trans(
                    'custom.item.error.items.denied',
                    ['%ids%' => implode(',', $denied)],
                    'flashes'
                ),
                'error'
            );
        }

        $page = $this->sessionProvider->getPage();

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
