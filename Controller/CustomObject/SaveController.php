<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SaveController extends AbstractFormController
{
    public function __construct(
        CorePermissions $security,
        UserHelper $userHelper,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    ) {
        $this->setRequestStack($requestStack);

        parent::__construct($security, $userHelper, $managerRegistry);
    }

    public function saveAction(
        FlashBag $flashBag,
        FormFactoryInterface $formFactory,
        CustomObjectModel $customObjectModel,
        CustomFieldModel $customFieldModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        ParamsToStringTransformer $paramsToStringTransformer,
        OptionsToStringTransformer $optionsToStringTransformer,
        LockFlashMessageHelper $lockFlashMessageHelper,
        ?int $objectId = null
    ): Response {
        $request = $this->getCurrentRequest();

        try {
            $customObject = $objectId ? $customObjectModel->fetchEntity($objectId) : new CustomObject();
            if ($customObject->isNew()) {
                $permissionProvider->canCreate();
            } else {
                $permissionProvider->canEdit($customObject);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customObjectModel->isLocked($customObject)) {
            $lockFlashMessageHelper->addFlash(
                $customObject,
                $routeProvider->buildEditRoute($objectId),
                $this->canEdit($customObject),
                'custom.object'
            );

            return $this->redirect($routeProvider->buildViewRoute($objectId));
        }

        $action  = $routeProvider->buildSaveRoute($objectId);
        $form    = $formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $action]
        );

        $postData = $request->get('custom_object');

        // just because empty fields are deleted from post data by default
        $form->submit($postData, false);

        if ($form->isValid()) {
            $this->handleRawPost(
                $customObjectModel,
                $paramsToStringTransformer,
                $optionsToStringTransformer,
                $customObject,
                $postData
            );

            $customObjectModel->save($customObject);

            $flashBag->add(
                $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                [
                    '%name%' => $customObject->getName(),
                    '%url%'  => $routeProvider->buildEditRoute($objectId),
                ]
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                $customObjectModel->unlockEntity($customObject);
                $route = $routeProvider->buildViewRoute($customObject->getId());
            } else {
                $route = $routeProvider->buildEditRoute($customObject->getId());
            }

            return $this->redirectWithCompletePageRefresh($request, $route);
        }

        return $this->delegateView(
            [
                'returnUrl'      => $routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject'        => $customObject,
                    'availableFieldTypes' => $customFieldTypeProvider->getTypes(),
                    'customFields'        => $customFieldModel->fetchCustomFieldsForObject($customObject),
                    'deletedFields'       => [],
                    'form'                => $form->createView(),
                ],
                'contentTemplate' => '@CustomObjects/CustomObject/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $objectId ? $routeProvider->buildEditRoute($customObject->getId()) : $routeProvider->buildNewRoute(),
                ],
            ]
        );
    }

    /**
     * @param string[] $rawCustomObject
     */
    private function handleRawPost(
        CustomObjectModel $customObjectModel,
        ParamsToStringTransformer $paramsToStringTransformer,
        OptionsToStringTransformer $optionsToStringTransformer,
        CustomObject $customObject,
        array $rawCustomObject
    ): void {
        if (empty($rawCustomObject['customFields'])) {
            return;
        }

        // Let's order received $_POST data and apply to delete for existing CFs
        $customFields = [];
        foreach ($rawCustomObject['customFields'] as $customField) {
            if ($customField['deleted'] && $customField['id']) {
                // Remove deleted custom fields
                $customObjectModel->removeCustomFieldById($customObject, (int) $customField['id']);
            } else {
                // We are using order key as key to access collection of CustomFields below
                $customFields[(int) $customField['order']] = $customField;
            }
        }

        foreach ($customFields as $order => $rawCustomField) {
            // Should be resolved better in form/transformer, but here it is more clear
            $params = $rawCustomField['params'];
            $params = $paramsToStringTransformer->reverseTransform($params);

            $options = $rawCustomField['options'];
            $options = $optionsToStringTransformer->reverseTransform($options);

            /** @var CustomField $customField */
            $customField = $customObject->getCustomFieldByOrder((int) $order);

            $customField->setParams($params);
            $customField->setOptions($options);
            $customField->setDefaultValue(!empty($rawCustomField['defaultValue']) ? $rawCustomField['defaultValue'] : null);
        }
    }

    private function redirectWithCompletePageRefresh(Request $request, string $url): Response
    {
        return $request->isXmlHttpRequest() ? new JsonResponse(['redirect' => $url]) : $this->redirect($url);
    }
}
