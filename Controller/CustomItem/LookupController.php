<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class LookupController extends JsonController
{
    public function listAction(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        FlashBag $flashBag,
        int $objectId
    ): JsonResponse {
        $request = $requestStack->getCurrentRequest();

        try {
            $permissionProvider->canViewAtAll($objectId);
        } catch (ForbiddenException $e) {
            $flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);

            return $this->renderJson();
        }

        $search           = InputHelper::clean($request->get('filter'));
        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $tableConfig      = new TableConfig(15, 1, CustomItem::TABLE_ALIAS.'.name', 'ASC');
        $tableConfig->addParameter('search', $search);
        $tableConfig->addParameter('customObjectId', $objectId);
        $tableConfig->addParameter('filterEntityType', $filterEntityType);
        $tableConfig->addParameter('filterEntityId', $filterEntityId);

        return $this->renderJson(['items' => $customItemModel->getLookupData($tableConfig)]);
    }
}
