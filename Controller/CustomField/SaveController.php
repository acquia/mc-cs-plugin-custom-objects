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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

class SaveController extends CommonController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $fieldRouteProvider;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param Session                       $session
     * @param FormFactory                   $formFactory
     * @param TranslatorInterface           $translator
     * @param CustomFieldModel              $customFieldModel
     * @param CustomFieldFactory            $customFieldFactory
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider      $routeProvider
     * @param CustomObjectModel             $customObjectModel
     */
    public function __construct(
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    ){
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customFieldModel   = $customFieldModel;
        $this->customFieldFactory = $customFieldFactory;
        $this->permissionProvider = $permissionProvider;
        $this->fieldRouteProvider      = $routeProvider;
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @param Request $request
     *
     * @return Response|JsonResponse
     * @throws ForbiddenException
     */
    public function saveAction(Request $request)
    {
        $objectId = (int) $request->get('objectId');
        $fieldId = (int) $request->get('fieldId');
        $fieldType = $request->get('fieldType');

        if ($objectId) {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
        } else {
            $customObject = new CustomObject();
        }

        try {
            if ($fieldId) {
                $customField = $this->customFieldModel->fetchEntity($fieldId);
                $this->permissionProvider->canEdit($customField);
            } else {
                $this->permissionProvider->canCreate();
                $customField = $this->customFieldFactory->create($fieldType, $customObject);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $action = $this->fieldRouteProvider->buildSaveRoute($fieldId, $customObject->getId(), $fieldType);
        $form   = $this->formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $this->customFieldModel->save($customField);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $fieldId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $customField->getName(),
                        '%url%'  => '', // No url provided as it does not make sense
                    ], 
                    'flashes'
                )
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                // Close modal
                return $this->delegateView(
                    [
                        'passthroughVars' => [
                            'closeModal'    => 1,
                        ],
                    ]
                );
            } else {
                return $this->redirectToEdit($request, $customField);
            }
        }

        $route = $fieldId ? $this->fieldRouteProvider->buildFormRoute($fieldId) : '';

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'customField' => $customField,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }

    /**
     * @param Request     $request
     * @param CustomField $entity
     * 
     * @return Response
     */
    private function redirectToEdit(Request $request, CustomField $entity): Response
    {
        $request->setMethod('GET');
        $params = ['fieldId' => $entity->getId()];

        return $this->forward('custom_field.form_controller:renderFormAction', $params);
    }
}