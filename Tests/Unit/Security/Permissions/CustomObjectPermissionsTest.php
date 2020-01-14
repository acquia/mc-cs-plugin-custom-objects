<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Security\Permissions;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomObjectPermissionsTest extends \PHPUnit\Framework\TestCase
{
    private $customObjectModel;
    private $customObject;
    private $formBuilder;
    private $configProvider;
    private $translator;

    /**
     * @var CustomObjectPermissions
     */
    private $permissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel = $this->createMock(CustomObjectModel::class);
        $this->customObject      = $this->createMock(CustomObject::class);
        $this->configProvider    = $this->createMock(ConfigProvider::class);
        $this->formBuilder       = $this->createMock(FormBuilderInterface::class);
        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->permissions       = new CustomObjectPermissions(
            [],
            $this->customObjectModel,
            $this->configProvider,
            $this->translator
        );
    }

    public function testDefinePermissions(): void
    {
        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn(45);

        $this->customObjectModel->expects($this->once())
            ->method('getEntities')
            ->with([
                'ignore_paginator' => true,
                'filter'           => [
                    'force' => [
                        [
                            'column' => CustomObject::TABLE_ALIAS.'.isPublished',
                            'value'  => true,
                            'expr'   => 'eq',
                        ],
                    ],
                ],
            ])
            ->willReturn([45 => $this->customObject]);

        $this->permissions->definePermissions();

        $configuredPermissions = $this->permissions->getPermissions();
        $expectedPermissions   = [
            'custom_fields' => [
                'viewown'      => 2,
                'viewother'    => 4,
                'editown'      => 8,
                'editother'    => 16,
                'create'       => 32,
                'deleteown'    => 64,
                'deleteother'  => 128,
                'full'         => 1024,
                'publishown'   => 256,
                'publishother' => 512,
            ],
            'custom_objects' => [
                'viewown'      => 2,
                'viewother'    => 4,
                'editown'      => 8,
                'editother'    => 16,
                'create'       => 32,
                'deleteown'    => 64,
                'deleteother'  => 128,
                'full'         => 1024,
                'publishown'   => 256,
                'publishother' => 512,
            ],
            45 => [
                'viewown'      => 2,
                'viewother'    => 4,
                'editown'      => 8,
                'editother'    => 16,
                'create'       => 32,
                'deleteown'    => 64,
                'deleteother'  => 128,
                'full'         => 1024,
                'publishown'   => 256,
                'publishother' => 512,
            ],
        ];

        $this->assertSame($expectedPermissions, $configuredPermissions);
    }

    public function testGetName(): void
    {
        $this->assertSame('custom_objects', $this->permissions->getName());
    }

    public function testBuildForm(): void
    {
        $objectId = 45;
        $objectNamePlural = 'Products';
        $translation = 'Products - User has access to';

        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $this->customObject->expects($this->once())
            ->method('getNamePlural')
            ->willReturn($objectNamePlural);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'custom.object.permissions',
                ['%name%' => $objectNamePlural]
            )
            ->willReturn($translation);

        $this->customObjectModel->expects($this->once())
            ->method('getEntities')
            ->willReturn([$objectId => $this->customObject]);

        $this->formBuilder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['custom_objects:custom_fields'],
                ['custom_objects:custom_objects'],
                ["custom_objects:$objectId"]
            );

        $this->permissions->buildForm($this->formBuilder, [], []);
    }

    public function testIsEnabled(): void
    {
        $this->assertFalse($this->permissions->isEnabled());

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->assertTrue($this->permissions->isEnabled());
    }
}
