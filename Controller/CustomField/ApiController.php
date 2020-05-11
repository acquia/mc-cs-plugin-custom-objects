<?php


namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;


class ApiController
{
    /**
     * List of custom fields.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Fields",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=CustomField::class, groups={"fetch"}))
     *     )
     * )
     * @param CustomField $customField
     */
    public function fetchUserRewardsAction(CustomField $customField)
    {
        // ...
    }
}