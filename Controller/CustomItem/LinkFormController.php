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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class LinkFormController extends AbstractFormController
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

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
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    public function __construct(
        FormFactoryInterface $formFactory,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $customItemRouteProvider,
        FlashBag $flashBag
    ) {
        $this->formFactory        = $formFactory;
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $customItemRouteProvider;
        $this->flashBag           = $flashBag;
    }

    public function formAction(int $itemId, string $entityType, int $entityId): Response
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);

            if (null === $relationshipObject = $customItem->getCustomObject()->getRelationshipObject()) {
                throw new NoRelationshipException();
            }

            $this->permissionProvider->canEdit($customItem);

            $relationshipItem = $this->getRelationshipItem($relationshipObject, $customItem, $entityType, $entityId);
            $relationshipItem->generateNameForChildObject($entityType, $entityId, $customItem);

            $form             = $this->formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                [
                    'action'    => $this->routeProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                    'cancelUrl' => '',
                ]
            );

            return $this->delegateView(
                [
                    'returnUrl'      => $this->routeProvider->buildContactViewRoute($entityId),
                    'viewParameters' => [
                        'entity'       => $relationshipItem,
                        'customObject' => $relationshipObject,
                        'form'         => $form->createView(),
                    ],
                    'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                    'passthroughVars' => [
                        'callback'      => 'customItemLinkFormLoad',
                        'mauticContent' => 'customItem',
                        'route'         => $this->routeProvider->buildNewRoute($relationshipObject->getId()),
                    ],
                ]
            );
        } catch (ForbiddenException|NotFoundException|UnexpectedValueException|NoRelationshipException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        $responseData = [
            'closeModal' => true,
            'flashes'    => $this->renderView('MauticCoreBundle:Notification:flash_messages.html.twig'),
        ];

        return new JsonResponse($responseData);
    }

    public function saveAction(int $itemId, string $entityType, int $entityId): Response
    {
        $relationshipItem   = null;
        $relationshipObject = null;
        $form               = null;

        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);

            if (null === $relationshipObject = $customItem->getCustomObject()->getRelationshipObject()) {
                throw new NoRelationshipException();
            }

            $this->permissionProvider->canCreate($relationshipObject->getId());

            $relationshipItem = $this->getRelationshipItem($relationshipObject, $customItem, $entityType, $entityId);
            $relationshipItem->generateNameForChildObject($entityType, $entityId, $customItem);

            $form             = $this->formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                [
                    'action'    => $this->routeProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                ]
            );

            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $callback         = $relationshipItem->getId() ? null : 'customItemLinkFormPostSubmit';
                $relationshipItem = $this->customItemModel->save($relationshipItem);

                $responseData = [
                    'closeModal' => true,
                    'callback'   => $callback,
                    'flashes'    => $this->renderView('MauticCoreBundle:Notification:flash_messages.html.twig'),
                ];

                return new JsonResponse($responseData);
            }
        } catch (ForbiddenException|NoRelationshipException|NotFoundException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildContactViewRoute($entityId),
                'viewParameters' => [
                    'entity'       => $relationshipItem,
                    'customObject' => $relationshipObject,
                    'form'         => $form->createView(),
                    'tmpl'         => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                'passthroughVars' => [
                    'closeModal'    => false,
                    'callback'      => 'customItemLinkFormLoad',
                    'mauticContent' => 'customItem',
                    'route'         => $this->routeProvider->buildLinkFormRoute($itemId, $entityType, $entityId),
                ],
            ]
        );
    }

    protected function getRelationshipItem(CustomObject $relationshipObject, CustomItem $customItem, string $entityType, int $entityId): CustomItem
    {
        /** @var CustomItemXrefCustomItem|null $relationshipItemXref */
        $relationshipItemXref = $customItem->getCustomItemLowerReferences()
            ->filter(function (CustomItemXrefCustomItem $item) use ($entityType, $entityId) {
                $higher = $item->getCustomItemHigher();

                return $higher->getRelationsByType($entityType)
                        ->filter(function ($relation) use ($entityId) {
                            return (int) $relation->getLinkedEntity()->getId() === (int) $entityId;
                        })->count() > 0;
            })->first();

        return $this->customItemModel->populateCustomFields(
            $relationshipItemXref ? $relationshipItemXref->getCustomItemHigher() : new CustomItem($relationshipObject)
        );
    }
}
