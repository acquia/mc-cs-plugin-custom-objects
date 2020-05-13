<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomObjectTypeTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $customFieldTypeProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $customObjectRepository;

    /**
     * @var CustomObjectType
     */
    private $type;

    public function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);

        $this->type = new CustomObjectType(
            $this->entityManager,
            $this->customFieldTypeProvider,
            $this->customObjectRepository
        );
    }

    public function testBuildForm()
    {

    }

    public function testConfigureOptions()
    {
        $options = [
            'data_class'         => CustomObject::class,
            'allow_extra_fields' => true,
            'csrf_protection'    => false,
        ];

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($options);

        $this->type->configureOptions($resolver);
    }
}
