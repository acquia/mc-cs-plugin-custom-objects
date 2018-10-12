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

    public function __construct(
        RequestStack $requestStack,
        Router $router,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectStructureModel $customObjectStructureModel
    )
    {
        $this->requestStack                     = $requestStack;
        $this->router                           = $router;
        $this->session                          = $session;
        $this->formFactory                      = $formFactory;
        $this->translator                       = $translator;
        $this->customObjectStructureModel = $customObjectStructureModel;
    }

    /**
     * @todo implement permissions
     * 
     * @return Response|JsonResponse
     */
    public function save()
    {
        $entity  = new CustomObjectStructure();
        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->router->generate('mautic_custom_object_structures_save');
        $form    = $this->formFactory->create(CustomObjectStructureType::class, $entity, ['action' => $action]);

        $form->handleRequest($request);

        // $validator = $this->get('validator');
        // $errors = $validator->validate($entity);
        
        if ($form->isValid()) {
            $this->customObjectStructureModel->save($entity);

            // $this->session->getFlashBag()->add(
            //     'notice',
            //     $this->translator->trans(
            //         'mautic.core.notice.created',
            //         [
            //             '%name%' => $entity->getName(),
            //             '%url%'  => $this->router->generate(
            //                 'custom_objects_edit',
            //                 [
            //                     'objectId' => $entity->getId(),
            //                 ]
            //             ),
            //         ], 
            //         'flashes'
            //     )
            // );

            // if (!$form->get('buttons')->get('save')->isClicked()) {
            //     return $this->redirectToDetail($request, $entity);
            // } else {
            //     return $this->editAction($entity->getId(), true);
            // }
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

    // private function redirectToList(Request $request)
    // {
    //     $viewParameters = [
    //         'page' => $this->session->get('custom.object.structures.page', 1),
    //         // 'tmpl' => $request->isXmlHttpRequest() ? $request->get('tmpl', 'list') : 'list',
    //     ];
    //     return $this->postActionRedirect(
    //         [
    //             'returnUrl'       => $this->generateUrl('mautic_custom_object_structures_list', $viewParameters),
    //             'viewParameters'  => $viewParameters,
    //             'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureList:list.html.php',
    //             'passthroughVars' => [
    //                 'mauticContent' => 'customObjectStructure',
    //             ],
    //         ]
    //     );

    //     return $this->forward('custom_object_structures.list_controller:listAction', $params);
    // }

    // private function redirectToDetail(Request $request, CustomObjectStructure $entity)
    // {
    //     $params = [
    //         'objectAction' => 'view',
    //         'objectId'     => $entity->getId(),
    //         'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
    //     ];

    //     return $this->render('CustomObjectsBundle:CustomObjectStructureDetail:view.html.php', $params, new Response(''));
    // }
}