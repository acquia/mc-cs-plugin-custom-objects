<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
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

        $query = $this->createMock(ParameterBag::class);
        $query->method('get')
            ->willReturn($fieldId);
        $request->query = $query;

        return $request;
    }
}
