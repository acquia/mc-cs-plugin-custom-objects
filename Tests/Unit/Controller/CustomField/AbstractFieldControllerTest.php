<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractFieldControllerTest extends ControllerTestCase
{
    protected function createRequestStackMock(
        $objectId = null,
        $fieldId = null,
        $fieldType = null,
        $panelId = null,
        $panelCount = null,
        array $mapExtras = []
    ): RequestStack {
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
        $query->method('all')
            ->willReturn($map);
        $request->query = $query;

        $post  = $this->createMock(ParameterBag::class);
        $post->method('get')
            ->willReturn($fieldId);
        $post->method('all')
            ->willReturn($map);
        $request->request = $post;

        $request->expects($this->any())
            ->method('duplicate')
            ->willReturn($request);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        return $requestStack;
    }
}
