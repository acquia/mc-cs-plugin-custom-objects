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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NoRelationshipException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class LinkFormController extends AbstractFormController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

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
        FormFactory $formFactory,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $customItemRouteProvider,
        FlashBag $flashBag
    ) {
        $this->formFactory        = $formFactory;
        $this->customItemModel    = $customItemModel;
        $this->customObjectModel  = $customObjectModel;
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

            // Have this here so I can put a breakpoint for xdebug to see what is in the custom item references
//            $customItem->getCustomItemReferences()->map(function($item) {
//                return $item;
//            });


            // Here I need to EITHER create a new custom item OR get the existing one from the $customItem
            //$relationshipItem = $this->customItemModel->populateCustomFields(new CustomItem($relationshipObject));
            $relationshipItem = $this->customItemModel->fetchEntity(34);

            $form = $this->formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                $options = [
                    'action'    => $this->routeProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                    'cancelUrl' => ''
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
                    'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                    'passthroughVars' => [
                        'callback'      => 'customItemLinkFormPostSubmit',
                        'mauticContent' => 'customItem',
                        'route'         => $this->routeProvider->buildNewRoute($relationshipObject->getId()),
                    ],
                ]
            );
        } catch (ForbiddenException | NotFoundException | UnexpectedValueException | NoRelationshipException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        $responseData = [
            'closeModal' => true,
            'flashes'    => $this->renderView('MauticCoreBundle:Notification:flash_messages.html.php'),
        ];

        return new JsonResponse($responseData);
    }

    public function saveAction(int $itemId, string $entityType, int $entityId): Response
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);

            if (null === $relationshipObject = $customItem->getCustomObject()->getRelationshipObject()) {
                throw new NoRelationshipException();
            }

            $this->permissionProvider->canCreate($relationshipObject->getId());

            $relationshipItem = $this->customItemModel->populateCustomFields(new CustomItem($relationshipObject));

            // Generate a default name for relationship forms as the name is hidden from the form.
            $submittedFormValues                        = $this->request->request->all();
            $submittedFormValues['custom_item']['name'] = sprintf('relationship-between-%s-%d-and-%s-%d',
                $entityType,
                $entityId,
                $customItem->getCustomObject()->getAlias(),
                $customItem->getCustomObject()->getId()
            );

            // Overwrite the request params with the new ones containing the generated title.
            $this->request->request->add($submittedFormValues);

            $form = $this->formFactory->create(
                CustomItemType::class,
                $relationshipItem,
                $options = [
                    'action'    => $this->routeProvider->buildLinkFormSaveRoute($customItem->getId(), $entityType, $entityId),
                    'objectId'  => $relationshipObject->getId(),
                    'contactId' => $entityId,
                ]
            );

            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $this->customItemModel->save($relationshipItem);

                $responseData = [
                    'closeModal' => true,
                    'flashes'    => $this->renderView('MauticCoreBundle:Notification:flash_messages.html.php'),
                ];

                return new JsonResponse($responseData);
            }

        } catch (ForbiddenException | NoRelationshipException $e) {
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
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'closeModal'    => false,
                    'mauticContent' => 'customItem',
                    'route'         => $this->routeProvider->buildLinkFormRoute($itemId, $entityType, $entityId),
                ],
            ]
        );
    }
}
