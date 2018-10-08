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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureActionModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomObjectStructureActionController extends Controller
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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomObjectStructureActionModel
     */
    private $customObjectStructureActionModel;

    public function __construct(
        RequestStack $requestStack,
        Router $router,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectStructureActionModel $customObjectStructureActionModel
    )
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->session = $session;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->customObjectStructureActionModel = $customObjectStructureActionModel;
    }

    /**
     * Calls correct action method in this controller.
     *
     * @param string $objectAction
     * @param integer $objectId
     * 
     * @return ???
     * 
     * @throws \UnexpectedValueException
     */
    public function executeAction(string $objectAction, int $objectId)
    {
        $methodName = $objectAction.'Action';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($objectId);
        } else {
            throw new \UnexpectedValueException("Action $objectAction is not implemented.");
        }
    }

    /**
     * @todo implement permissions
     */
    private function newAction()
    {
        $request = $this->requestStack->getCurrentRequest();
        $entity  = new CustomObjectStructure();
        $action  = $this->router->generate('mautic_custom_objects_action', ['objectAction' => 'new']);
        $form    = $this->formFactory->create(CustomObjectStructureType::class, $entity, ['action' => $action]);

        if ($request->getMethod() == 'POST') {
            $cancelled = $request->request->get($form->getName().'[buttons][cancel]', false, true) !== false;

            if ($cancelled) {
                return $this->redirectToList($request);
            }

            $form->handleRequest($request);
            
            $valid       = $form->isValid();
            $saveClicked = $form->get('buttons')->get('save')->isClicked();
            
            if ($valid) {
                $model->saveEntity($entity);

                $this->session->getFlashBag()->add(
                    'notice',
                    $this->translator->trans(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%url%'       => $this->router->generate(
                                'custom_objects_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ], 
                        'flashes'
                    )
                );

                if (!$saveClicked) {
                    return $this->redirectToDetail($request, $entity);
                } else {
                    return $this->editAction($entity->getId(), true);
                }
            }
        }

        $template = 'CustomObjectsBundle:CustomObjectStructure:form.html.php';
        $parameters = [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];

        return $this->render($template, $parameters, new Response(''));
    }

    private function redirectToList(Request $request)
    {
        $params = [
            'page' => $this->session->get('custom.object.structures.page', 1),
            'tmpl' => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
        ];

        return $this->forward('custom_object_structures.list_controller:listAction', $params);
    }

    private function redirectToDetail(Request $request, CustomObjectStructure $entity)
    {
        $params = [
            'objectAction' => 'view',
            'objectId'     => $entity->getId(),
            'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
        ];

        return $this->render('CustomObjectsBundle:CustomObjectStructure:view.html.php', $params, new Response(''));
    }
}