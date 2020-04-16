<?php


namespace MauticPlugin\CustomObjectsBundle\Controller\Api;


use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\Serializer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class CustomObjectApiController  extends CommonApiController
{
    const MODEL_ID = 'custom.object';

    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel(self::MODEL_ID);
        $this->entityNameOne    = 'custom_object';
        $this->entityNameMulti  = 'custom_objects';
        $this->serializerGroups = ['customFields'];

        parent::initialize($event);
    }

    public function newEntityAction(): Response
    {
        $content = $this->request->getContent();
        $parameters = json_decode($content, true);
        $entity = $this->getNewEntity($parameters);

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        return $this->processForm($entity, $parameters, 'POST');
    }

}