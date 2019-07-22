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
        $panelCount = null
    ): Request {
        $request = $this->createMock(Request::class);
        $request->expects($this->at(0))
            ->method('get')
            ->with('objectId')
            ->willReturn($objectId);
        $request->expects($this->at(1))
            ->method('get')
            ->with('fieldId')
            ->willReturn($fieldId);
        $request->expects($this->at(2))
            ->method('get')
            ->with('fieldType')
            ->willReturn($fieldType);
        $request->expects($this->at(3))
            ->method('get')
            ->with('panelId')
            ->willReturn($panelId);
        $request->expects($this->at(4))
            ->method('get')
            ->with('panelCount')
            ->willReturn($panelCount);

        return $request;
    }
}
