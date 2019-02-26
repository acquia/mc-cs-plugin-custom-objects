<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonController extends Controller
{
    /**
     * Adds flashes stored in session (by addFlash() method) to the JsonResponse.
     *
     * @param mixed[] $responseData
     *
     * @return JsonResponse
     */
    protected function renderJson(array $responseData = []): JsonResponse
    {
        $responseData['flashes'] = $this->renderView('MauticCoreBundle:Notification:flash_messages.html.php');

        return new JsonResponse($responseData);
    }
}
