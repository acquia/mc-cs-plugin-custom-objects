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

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObjectStructure;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectStructureType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureModel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomObjectStructureSaveController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomObjectStructureModel
     */
    private $customObjectStructureModel;

    /**
     * @param RequestStack $requestStack
     * @param Router $router
     * @param Session $session
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     * @param CustomObjectStructureModel $customObjectStructureModel
     */
    public function __construct(
        RequestStack $requestStack,
        Router $router,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectStructureModel $customObjectStructureModel
    )
    {
        $this->requestStack               = $requestStack;
        $this->router                     = $router;
        $this->session                    = $session;
        $this->formFactory                = $formFactory;
        $this->translator                 = $translator;
        $this->customObjectStructureModel = $customObjectStructureModel;
    }

    /**
     * @todo implement permissions
     * 
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(?int $objectId = null)
    {
        try {
            $entity = $objectId ? $this->customObjectStructureModel->getEntity($objectId): new CustomObjectStructure();
        } catch (NotFoundException $e) {
            $this->notFound($e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->router->generate('mautic_custom_object_structures_save', ['objectId' => $objectId]);
        $form    = $this->formFactory->create(CustomObjectStructureType::class, $entity, ['action' => $action]);
        $form->handleRequest($request);

        // $validator = $this->get('validator');
        // $errors = $validator->validate($entity);
        
        if ($form->isValid()) {
            $this->customObjectStructureModel->save($entity);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $entity->getName(),
                        '%url%'  => $this->router->generate(
                            'mautic_custom_object_structures_edit',
                            [
                                'objectId' => $entity->getId(),
                            ]
                        ),
                    ], 
                    'flashes'
                )
            );
            if ($form->get('buttons')->get('save')->isClicked()) {
                return $this->redirectToDetail($entity);
            } else {
                return $this->redirectToEdit($entity);
            }
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_object_structures_new'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureAction:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                    'route'         => $this->router->generate('mautic_custom_object_structures_new'),
                ],
            ]
        );
    }

    /**
     * @param Request               $request
     * @param CustomObjectStructure $entity
     * 
     * @return Response
     */
    private function redirectToEdit(Request $request, CustomObjectStructure $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('custom_object.structures.edit_controller:renderFormAction', $params);
    }

    /**
     * @param Request               $request
     * @param CustomObjectStructure $entity
     * 
     * @return Response
     */
    private function redirectToDetail(Request $request, CustomObjectStructure $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('CustomObjectsBundle:CustomObjectStructureView:view', $params);
    }
}