<?php


namespace MauticPlugin\CustomObjectsBundle\Controller\Api;


use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomFieldApiController  extends CommonController
{
    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    public function __construct(
        CustomFieldModel $customFieldModel
    ) {
        $this->customFieldModel = $customFieldModel;
    }
    public function pluginGetFieldsAction(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $offset = (int) $request->query->get('limit', 0);
        $args = ['limit' => $limit, 'offset' => $offset];
        $customFields = $this->customFieldModel->fetchEntities($args);
        return $this->json($customFields);


    }

}