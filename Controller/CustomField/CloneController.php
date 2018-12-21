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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CloneController extends CommonController
{
    /**
     * @var Router
     */
    private $router;

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
     * @param Router $router
     * @param FormFactory $formFactory
     * @param CustomFieldModel $customFieldModel
     * @param CustomFieldPermissionProvider $permissionProvider
     */
    public function __construct(
        Router $router,
        FormFactory $formFactory,
        CustomFieldModel $customFieldModel,
        CustomFieldPermissionProvider $permissionProvider
    )
    {
        $this->router             = $router;
        $this->formFactory        = $formFactory;
        $this->customFieldModel   = $customFieldModel;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function cloneAction(int $objectId)
    {
        try {
            $entity = clone $this->customFieldModel->fetchEntity($objectId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        try {
            $this->permissionProvider->canClone($entity);
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $entity->setLabel($entity->getLabel().' '.$this->translator->trans('mautic.core.form.clone'));

        $action = $this->router->generate('mautic_custom_field_save');
        $form   = $this->formFactory->create(CustomFieldType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_field_list'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $this->router->generate('mautic_custom_field_clone', ['objectId' => $objectId]),
                ],
            ]
        );
    }
}