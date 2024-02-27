<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NoRelationshipException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class LinkFormController extends AbstractFormController
{
    public function formAction(
        FormFactoryInterface $formFactory,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $customItemRouteProvider,
        FlashBag $flashBag,
        int $itemId,
        string $entityType,
        int $entityId
    ): Response {
        try {
            $customItem = $customItemModel->fetchEntity($itemId);

            if (null === $relationshipObject = $customItem->getCustomObject()->getRelationshipObject()) {
                throw new NoRelationshipException();
            }

            $permissionProvider->canEdit($customItem);

            $relationshipItem = $this->getRelationshipItem($customItemModel, $relationshipObject, $customItem, $entityType, $entityId);
            $relationshipItem->generateNameForChildObject($entityType, $entityId, $customItem);

            $form             = $formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                [
                    'action'    => $customItemRouteProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                    'cancelUrl' => '',
                ]
            );

            return $this->delegateView(
                [
                    'returnUrl'      => $customItemRouteProvider->buildContactViewRoute($entityId),
                    'viewParameters' => [
                        'entity'       => $relationshipItem,
                        'customObject' => $relationshipObject,
                        'form'         => $form->createView(),
                    ],
                    'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                    'passthroughVars' => [
                        'callback'      => 'customItemLinkFormLoad',
                        'mauticContent' => 'customItem',
                        'route'         => $customItemRouteProvider->buildNewRoute($relationshipObject->getId()),
                    ],
                ]
            );
        } catch (ForbiddenException|NotFoundException|UnexpectedValueException|NoRelationshipException $e) {
            $flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        $responseData = [
            'closeModal' => true,
            'flashes'    => $this->renderView('@MauticCore/Notification/flash_messages.html.twig'),
        ];

        return new JsonResponse($responseData);
    }

    public function saveAction(
        Request $request,
        FormFactoryInterface $formFactory,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $customItemRouteProvider,
        FlashBag $flashBag,
        int $itemId,
        string $entityType,
        int $entityId
    ): Response {
        $relationshipItem   = null;
        $relationshipObject = null;
        $form               = null;

        try {
            $customItem = $customItemModel->fetchEntity($itemId);

            if (null === $relationshipObject = $customItem->getCustomObject()->getRelationshipObject()) {
                throw new NoRelationshipException();
            }

            $permissionProvider->canCreate($relationshipObject->getId());

            $relationshipItem = $this->getRelationshipItem($customItemModel, $relationshipObject, $customItem, $entityType, $entityId);
            $relationshipItem->generateNameForChildObject($entityType, $entityId, $customItem);

            $form             = $formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                [
                    'action'    => $customItemRouteProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                ]
            );

            $form->handleRequest($request);

            if ($form->isValid()) {
                $callback         = $relationshipItem->getId() ? null : 'customItemLinkFormPostSubmit';
                $relationshipItem = $customItemModel->save($relationshipItem);

                $responseData = [
                    'closeModal' => true,
                    'callback'   => $callback,
                    'flashes'    => $this->renderView('@MauticCore/Notification/flash_messages.html.twig'),
                ];

                return new JsonResponse($responseData);
            }
        } catch (ForbiddenException|NoRelationshipException|NotFoundException $e) {
            $flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->delegateView(
            [
                'returnUrl'      => $customItemRouteProvider->buildContactViewRoute($entityId),
                'viewParameters' => [
                    'entity'       => $relationshipItem,
                    'customObject' => $relationshipObject,
                    'form'         => $form->createView(),
                    'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                'passthroughVars' => [
                    'closeModal'    => false,
                    'callback'      => 'customItemLinkFormLoad',
                    'mauticContent' => 'customItem',
                    'route'         => $customItemRouteProvider->buildLinkFormRoute($itemId, $entityType, $entityId),
                ],
            ]
        );
    }

    protected function getRelationshipItem(
        CustomItemModel $customItemModel,
        CustomObject $relationshipObject,
        CustomItem $customItem,
        string $entityType,
        int $entityId
    ): CustomItem {
        /** @var CustomItemXrefCustomItem|null $relationshipItemXref */
        $relationshipItemXref = $customItem->getCustomItemLowerReferences()
            ->filter(function (CustomItemXrefCustomItem $item) use ($entityType, $entityId) {
                $higher = $item->getCustomItemHigher();

                return $higher->getRelationsByType($entityType)
                        ->filter(function ($relation) use ($entityId) {
                            return (int) $relation->getLinkedEntity()->getId() === (int) $entityId;
                        })->count() > 0;
            })->first();

        return $customItemModel->populateCustomFields(
            $relationshipItemXref ? $relationshipItemXref->getCustomItemHigher() : new CustomItem($relationshipObject)
        );
    }
}
