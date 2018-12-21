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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CancelController extends CommonController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @param Session $session
     * @param CustomFieldModel $customFieldModel
     */
    public function __construct(
        Session $session,
        CustomFieldModel $customFieldModel
    )
    {
        $this->session           = $session;
        $this->customFieldModel = $customFieldModel;
    }

    /**
     * @todo unlock entity?
     * 
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function cancelAction(?int $objectId)
    {
        $viewParameters = [
            'page' => $this->session->get('custom.field.page', 1),
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_custom_field_list', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'CustomObjectsBundle:CustomField\List:list',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                ],
            ]
        );
    }
}