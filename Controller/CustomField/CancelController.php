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

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CancelController extends CommonController
{
    /**
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