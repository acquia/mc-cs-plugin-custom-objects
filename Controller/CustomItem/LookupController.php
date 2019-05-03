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

use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;

class LookupController extends JsonController
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
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @param RequestStack                 $requestStack
     * @param CustomItemModel              $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param FlashBag                     $flashBag
     */
    public function __construct(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        FlashBag $flashBag
    ) {
        $this->requestStack       = $requestStack;
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->flashBag           = $flashBag;
    }

    /**
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function listAction(int $objectId): JsonResponse
    {
        try {
            $this->permissionProvider->canViewAtAll($objectId);
        } catch (ForbiddenException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);

            return $this->renderJson([]);
        }

        $request          = $this->requestStack->getCurrentRequest();
        $nameFilter       = InputHelper::clean($request->get('filter'));
        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $tableConfig      = new TableConfig(10, 1, CustomItemRepository::TABLE_ALIAS.'.name', 'ASC');
        $tableConfig->addFilter(CustomItem::class, 'customObject', $objectId);
        $tableConfig->addFilterIfNotEmpty(CustomItem::class, 'name', "%{$nameFilter}%", 'like');

        if ($filterEntityId && 'contact' === $filterEntityType) {
            $notContact = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', $filterEntityId, 'neq');
            $isNull     = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', null, 'isNull');
            $orX        = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', [$notContact, $isNull], 'orX');
            $tableConfig->addFilterDTO($orX);
        }

        if ($filterEntityId && 'customItem' === $filterEntityType) {
            $tableConfig->addFilter(CustomItemXrefCustomItem::class, 'parentCustomItem', $filterEntityId, 'neq');
            $tableConfig->addFilter(CustomItemXrefCustomItem::class, 'customItem', $filterEntityId, 'neq');
        }

        return $this->renderJson(['items' => $this->customItemModel->getLookupData($tableConfig)]);
    }
}
