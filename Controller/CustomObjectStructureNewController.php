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
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomObjectStructureNewController extends CommonController
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
        $this->requestStack                     = $requestStack;
        $this->router                           = $router;
        $this->session                          = $session;
        $this->formFactory                      = $formFactory;
        $this->translator                       = $translator;
        $this->customObjectStructureActionModel = $customObjectStructureActionModel;
    }

    /**
     * @todo implement permissions
     * 
     * @return Response|JsonResponse
     */
    public function renderForm()
    {
        $request = $this->requestStack->getCurrentRequest();
        $entity  = new CustomObjectStructure();
        $action  = $this->router->generate('mautic_custom_object_structures_new');
        $form    = $this->formFactory->create(CustomObjectStructureType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_object_structures_list'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    // 'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureAction:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                    'route'         => $this->router->generate('mautic_custom_object_structures_list'),
                ],
            ]
        );
    }
}