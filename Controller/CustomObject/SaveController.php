<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;

class SaveController extends AbstractFormController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        private FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private CustomObjectPermissionProvider $permissionProvider,
        private CustomObjectRouteProvider $routeProvider,
        private CustomObjectModel $customObjectModel,
        private CustomFieldModel $customFieldModel,
        private CustomFieldTypeProvider $customFieldTypeProvider,
        private LockFlashMessageHelper $lockFlashMessageHelper,
        private ParamsToStringTransformer $paramsToStringTransformer,
        private OptionsToStringTransformer $optionsToStringTransformer,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

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

        if ($this->customObjectModel->isLocked($customObject)) {
            $this->lockFlashMessageHelper->addFlash(
                $customObject,
                $this->routeProvider->buildEditRoute($objectId),
                $this->canEdit($customObject),
                'custom.object'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId));
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->routeProvider->buildSaveRoute($objectId);
        $form    = $this->formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $action]
        );

        $postData = $request->get('custom_object');

        // just because empty fields are deleted from post data by default
        $form->submit($postData, false);

        if ($form->isValid()) {
            $this->handleRawPost($customObject, $postData);

            $this->customObjectModel->save($customObject);

            $this->flashBag->add(
                $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                [
                    '%name%' => $customObject->getName(),
                    '%url%'  => $this->routeProvider->buildEditRoute($objectId),
                ]
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                $this->customObjectModel->unlockEntity($customObject);
                $route = $this->routeProvider->buildViewRoute($customObject->getId());
            } else {
                $route = $this->routeProvider->buildEditRoute($customObject->getId());
            }

            return $this->redirectWithCompletePageRefresh($request, $route);
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
                'contentTemplate' => '@CustomObjects/CustomObject/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $objectId ? $this->routeProvider->buildEditRoute($customObject->getId()) : $this->routeProvider->buildNewRoute(),
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $rawCustomObject
     */
    private function handleRawPost(CustomObject $customObject, array $rawCustomObject): void
    {
        if (empty($rawCustomObject['customFields'])) {
            return;
        }

        // Let's order received $_POST data and apply delete for existing CFs
        $customFields = [];
        foreach ($rawCustomObject['customFields'] as $customField) {
            if ($customField['deleted'] && $customField['id']) {
                // Remove deleted custom fields
                $this->customObjectModel->removeCustomFieldById($customObject, (int) $customField['id']);
            } else {
                // We are using order key as key to access collection of CustomFields below
                $customFields[(int) $customField['order']] = $customField;
            }
        }

        foreach ($customFields as $order => $rawCustomField) {
            // Should be resolved better in form/transformer, but here it is more clear
            $params = $rawCustomField['params'];
            $params = $this->paramsToStringTransformer->reverseTransform($params);

            $options = $rawCustomField['options'];
            $options = $this->optionsToStringTransformer->reverseTransform($options);

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
