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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @param RequestStack                 $requestStack
     * @param CustomItemModel              $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     */
    public function __construct(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
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
            return new AccessDeniedException($e->getMessage(), $e);
        }

        $request     = $this->requestStack->getCurrentRequest();
        $nameFilter  = InputHelper::clean($request->get('filter'));
        $contactId   = (int) InputHelper::clean($request->get('contactId'));
        $tableConfig = new TableConfig(10, 1, CustomItemRepository::TABLE_ALIAS.'.name', 'ASC');
        $tableConfig->addFilter(CustomItem::class, 'customObject', $objectId);
        $tableConfig->addFilterIfNotEmpty(CustomItem::class, 'name', "%{$nameFilter}%", 'like');

        if ($contactId) {
            $notContact = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', $contactId, 'neq');
            $isNull     = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', null, 'isNull');
            $orX        = $tableConfig->createFilter(CustomItemXrefContact::class, 'contact', [$notContact, $isNull], 'orX');
            $tableConfig->addFilterDTO($orX);
        }

        return $this->renderJson(['items' => $this->customItemModel->getLookupData($tableConfig)]);
    }
}
