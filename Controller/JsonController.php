<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonController extends Controller
{
    /**
     * Adds flashes stored in session (by addFlash() method) to the JsonResponse.
     *
     * @param mixed[] $responseData
     */
    protected function renderJson(array $responseData = []): JsonResponse
    {
        $responseData['flashes'] = $this->renderView('@MauticCore/Notification/flash_messages.html.twig');

        return new JsonResponse($responseData);
    }
}
