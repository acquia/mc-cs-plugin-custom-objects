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

    public function listAction(int $objectId): JsonResponse
    {
        try {
            $this->permissionProvider->canViewAtAll($objectId);
        } catch (ForbiddenException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);

            return $this->renderJson();
        }

        $request          = $this->requestStack->getCurrentRequest();
        $search           = InputHelper::clean($request->get('filter'));
        $filterEntityId   = (int) $request->get('filterEntityId');
        $filterEntityType = InputHelper::clean($request->get('filterEntityType'));
        $tableConfig      = new TableConfig(15, 1, CustomItem::TABLE_ALIAS.'.name', 'ASC');
        $tableConfig->addParameter('search', $search);
        $tableConfig->addParameter('customObjectId', $objectId);
        $tableConfig->addParameter('filterEntityType', $filterEntityType);
        $tableConfig->addParameter('filterEntityId', $filterEntityId);

        return $this->renderJson(['items' => $this->customItemModel->getLookupData($tableConfig)]);
    }
}
