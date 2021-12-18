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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractFieldControllerTest extends ControllerTestCase
{
    protected function createRequestMock(
        $objectId = null,
        $fieldId = null,
        $fieldType = null,
        $panelId = null,
        $panelCount = null,
        array $mapExtras = []
    ): Request {
        $request = $this->createMock(Request::class);

        $map = [
            ['objectId', null, $objectId],
            ['fieldId', null, $fieldId],
            ['fieldType', null, $fieldType],
            ['panelId', null, $panelId],
            ['panelCount', null, $panelCount],
        ];

        foreach ($mapExtras as $mapExtra) {
            $map[] = $mapExtra;
        }

        $request
            ->method('get')
            ->willReturnMap($map);

        return $request;
    }
}
