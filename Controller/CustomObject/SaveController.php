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

use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\StringToParamsTransformer;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\Form\FormFactoryInterface;

class SaveController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var FormFactoryInterface
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
     * @var StringToParamsTransformer
     */
    private $stringToParamsTransformer;

    /**
     * @param RequestStack                   $requestStack
     * @param FlashBag                       $flashBag
     * @param FormFactoryInterface           $formFactory
     * @param CustomObjectModel              $customObjectModel
     * @param CustomFieldModel               $customFieldModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     * @param CustomFieldTypeProvider        $customFieldTypeProvider
     * @param StringToParamsTransformer      $stringToParamsTransformer
     */
    public function __construct(
        RequestStack $requestStack,
        FlashBag $flashBag,
        FormFactoryInterface $formFactory,
        CustomObjectModel $customObjectModel,
        CustomFieldModel $customFieldModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        StringToParamsTransformer $stringToParamsTransformer
    ) {
        $this->requestStack            = $requestStack;
        $this->flashBag                = $flashBag;
        $this->formFactory             = $formFactory;
        $this->customObjectModel       = $customObjectModel;
        $this->customFieldModel        = $customFieldModel;
        $this->permissionProvider      = $permissionProvider;
        $this->routeProvider           = $routeProvider;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
        $this->stringToParamsTransformer = $stringToParamsTransformer;
    }

    /**
     * @param int|null $objectId
     *
     * @return Response
     */
    public function saveAction(?int $objectId = null): Response
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
            return $this->accessDenied(false, $e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->routeProvider->buildSaveRoute($objectId);
        $form    = $this->formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $action]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $rawCustomObject = $request->get('custom_object');

            if (!empty($rawCustomObject['customFields'])) {
                foreach ($rawCustomObject['customFields'] as $key => $customField) {
                    if ($customField['deleted'] && $customField['id']) {
                        $this->customObjectModel->removeCustomFieldById($customObject, (int) $customField['id']);
                    } else {
                        // Should be resolved better in form/transformer, but here it is more clear
                        $params = $customField['params'];
                        $params = $this->stringToParamsTransformer->transform($params);
                        $customObject->getCustomFields()->get($key)->setParams($params);
                    }
                }
            }

            $this->customObjectModel->save($customObject);

            $this->flashBag->add(
                $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                [
                    '%name%' => $customObject->getName(),
                    '%url%'  => $this->routeProvider->buildEditRoute($objectId),
                ]
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                return $this->forwardToDetail($request, $customObject);
            }

            return $this->redirect($this->routeProvider->buildEditRoute($customObject->getId()));
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject'        => $customObject,
                    'availableFieldTypes' => $this->customFieldTypeProvider->getTypes(),
                    'customFields'        => $this->customFieldModel->fetchCustomFieldsForObject($customObject),
                    'deletedFields'       => [],
                    'form'                => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $objectId ? $this->routeProvider->buildEditRoute($customObject->getId()) : $this->routeProvider->buildNewRoute(),
                ],
            ]
        );
    }

    /**
     * @param Request      $request
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
}
