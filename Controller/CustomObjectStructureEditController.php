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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObjectStructure;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectStructureType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureModel;

class CustomObjectStructureEditController extends CommonController
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
     * @var CustomObjectStructureModel
     */
    private $customObjectStructureModel;

    /**
     * @param Router $router
     * @param FormFactory $formFactory
     * @param CustomObjectStructureModel $customObjectStructureModel
     */
    public function __construct(
        Router $router,
        FormFactory $formFactory,
        CustomObjectStructureModel $customObjectStructureModel
    )
    {
        $this->router      = $router;
        $this->formFactory = $formFactory;
        $this->customObjectStructureModel = $customObjectStructureModel;
    }

    /**
     * @todo implement permissions
     * 
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function renderFormAction(int $objectId)
    {
        $entity  = $this->customObjectStructureModel->getEntity($objectId);
        $action  = $this->router->generate('mautic_custom_object_structures_save');
        $form    = $this->formFactory->create(CustomObjectStructureType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_object_structures_list'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObjectStructureAction:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObjectStructure',
                    'route'         => $this->router->generate('mautic_custom_object_structures_edit', ['objectId' => $objectId]),
                ],
            ]
        );
    }
}