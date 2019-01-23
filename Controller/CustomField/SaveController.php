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
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $routeProvider;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     * @param CustomFieldModel $customFieldModel
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomFieldModel $customFieldModel,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customFieldModel   = $customFieldModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int|null $fieldId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(?int $fieldId = null)
    {
        try {
            $field = $fieldId ? $this->customFieldModel->fetchEntity($fieldId): new CustomField();
            if ($field->isNew()) {
                $this->permissionProvider->canCreate();
            } else {
                $this->permissionProvider->canEdit($field);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->routeProvider->buildSaveRoute($fieldId);
        $form    = $this->formFactory->create(CustomFieldType::class, $field, ['action' => $action]);
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $this->customFieldModel->save($field);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $fieldId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $field->getName(),
                        '%url%'  => $this->routeProvider->buildEditRoute($fieldId),
                    ], 
                    'flashes'
                )
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                // Close modal
                return $this->delegateView(
                    [
                        'passthroughVars' => [
                            'success'       => 1,
                            'closeModal'    => 1,
                        ],
                    ]
                );
            } else {
                return $this->redirectToEdit($request, $field);
            }
        }

        $route = $fieldId ? $this->routeProvider->buildEditRoute($fieldId) : $this->routeProvider->buildNewRoute();

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'field' => $field,
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

        return $this->forward('custom_field.edit_controller:renderFormAction', $params);
    }
}