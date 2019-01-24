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
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

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
     * @var CustomFieldRouteProvider
     */
    private $routeProvider;

    /**
     * @param Session $session
     * @param CustomFieldModel $customFieldModel
     * @param CustomFieldRouteProvider $routeProvider
     */
    public function __construct(
        Session $session,
        CustomFieldModel $customFieldModel,
        CustomFieldRouteProvider $routeProvider
    )
    {
        $this->session          = $session;
        $this->customFieldModel = $customFieldModel;
        $this->routeProvider    = $routeProvider;
    }

    /**
     * @todo unlock entity?
     * 
     * @param int|null $fieldId
     * 
     * @return Response|JsonResponse
     */
    public function cancelAction(?int $fieldId)
    {
        return $this->postActionRedirect(
            [
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'closeModal' => 1,
                ],
            ]
        );
    }
}