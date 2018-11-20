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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

class CustomObjectCloneController extends CommonController
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
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param Router $router
     * @param FormFactory $formFactory
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(
        Router $router,
        FormFactory $formFactory,
        CustomObjectModel $customObjectModel
    )
    {
        $this->router      = $router;
        $this->formFactory = $formFactory;
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @todo implement permissions
     * 
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function cloneAction(int $objectId)
    {
        $entity  = clone $this->customObjectModel->getEntity($objectId);
        $action  = $this->router->generate('mautic_custom_object_save');
        $form    = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_object_list'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $this->router->generate('mautic_custom_object_clone', ['objectId' => $objectId]),
                ],
            ]
        );
    }
}