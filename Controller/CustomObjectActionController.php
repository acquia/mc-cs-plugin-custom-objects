<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectActionModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Form\FormFactory;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use Symfony\Component\Translation\TranslatorInterface;

class CustomObjectActionController extends Controller
{
    public function __construct(
        RequestStack $requestStack,
        Router $router,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectActionModel $customObjectActionModel
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->session = $session;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->customObjectActionModel = $customObjectActionModel;
    }

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
        $entity = new CustomObject();
        $action = $this->router->generate('mautic_custom_objects_action', ['objectAction' => 'new']);
        $form   = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);

        if ($this->request->getMethod() == 'POST') {
            $cancelled = $this->request->request->get($form->getName().'[buttons][cancel]', false, true) !== false;

            if ($cancelled) {
                return $this->redirectToList();
            }

            $form->handleRequest($this->request);
            
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
                    return $this->redirectToDetail($entity);
                } else {
                    return $this->editAction($entity->getId(), true);
                }
            }
        }

        $template = 'CustomObjectsBundle:CustomObjects:form.html.php';
        $parameters = [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];

        return $this->render($template, $parameters, new Response(''));
    }

    private function redirectToList()
    {
        $params = [
            'page' => $this->session->get('custom.objects.page', 1),
            'tmpl' => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
        ];

        return $this->forward('custom_objects.list_controller:listAction', $params);
    }

    private function redirectToDetail(CustomObject $entity)
    {
        $params = [
            'objectAction' => 'view',
            'objectId'     => $entity->getId(),
            'tmpl'         => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
        ];

        return $this->render('CustomObjectsBundle:CustomObjects:view.html.php', $params, new Response(''));
    }
}