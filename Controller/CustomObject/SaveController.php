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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class SaveController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param RequestStack                   $requestStack
     * @param Session                        $session
     * @param FormFactory                    $formFactory
     * @param TranslatorInterface            $translator
     * @param CustomObjectModel              $customObjectModel
     * @param CustomFieldModel               $customFieldModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     * @param CustomFieldTypeProvider        $customFieldTypeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectModel $customObjectModel,
        CustomFieldModel $customFieldModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customObjectModel  = $customObjectModel;
        $this->customFieldModel = $customFieldModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(?int $objectId = null)
    {
        try {
            $customObject = $objectId ? $this->customObjectModel->fetchEntity($objectId) : new CustomObject();
            if ($customObject->isNew()) {
                $this->permissionProvider->canCreate();
            } else {
                $this->permissionProvider->canEdit($customObject);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action = $this->routeProvider->buildSaveRoute($objectId);
        $form = $this->formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $action]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->customObjectModel->save($customObject);

            $rawCustomObject = $request->get('custom_object');

            if (!empty($rawCustomObject['customFields'])) {

                foreach ($rawCustomObject['customFields'] as $customField) {
                    if ($customField['deleted'] && $customField['id']) {
                        $this->customObjectModel->removeCustomFieldById($customObject, (int) $customField['id']);
                    }
                }
            }

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $customObject->getName(),
                        '%url%' => $this->routeProvider->buildFormRoute($objectId),
                    ],
                    'flashes'
                )
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                return $this->forwardToDetail($request, $customObject);
            }
        }

        return $this->forwardToEdit($request, $customObject);
    }

    /**
     * @param Request               $request
     * @param CustomObject $entity
     * 
     * @return Response
     */
    private function forwardToDetail(Request $request, CustomObject $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('CustomObjectsBundle:CustomObject\View:view', $params);
    }

    /**
     * @param Request               $request
     * @param CustomObject $entity
     *
     * @return Response
     */
    private function forwardToEdit(Request $request, CustomObject $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('custom_object.form_controller:renderFormAction', $params);
    }
}