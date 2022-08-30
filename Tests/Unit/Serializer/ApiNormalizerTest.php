<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Serializer\ApiNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiNormalizerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Normalizer
     */
    private $normalizerInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CustomItemModel
     */
    private $customItemModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    private $em;

    /**
     * @var ApiNormalizer
     */
    private $apiNormalizer;

    public function setUp(): void
    {
        if (interface_exists('ApiPlatform\\Core\\Api\\IriConverterInterface')) {
            $this->normalizerInterface     = $this->createMock(Normalizer::class);
            $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
            $this->iriConverter            = $this->createMock(IriConverterInterface::class);
            $this->customItemModel         = $this->createMock(CustomItemModel::class);
            $this->em                      = $this->createMock(EntityManager::class);
            $this->apiNormalizer           = new ApiNormalizer($this->normalizerInterface, $this->customFieldTypeProvider, $this->customItemModel, $this->iriConverter, $this->em);
        } else {
            $this->markTestSkipped('ApiPlatform is not available');
        }

        parent::setUp();
    }

    public function testDenormalizeNotCustomField(): void
    {
        $data         = ['sth' => 'sth'];
        $class        = CustomObject::class;
        $customObject = $this->createMock(CustomObject::class);
        $this->normalizerInterface
            ->expects($this->once())
            ->method('denormalize')
            ->with($data, $class, null, [])
            ->willReturn($customObject);
        $returnedEntity = $this->apiNormalizer->denormalize($data, $class);
        $this->assertSame($customObject, $returnedEntity);
    }

    public function testDenormalizeCustomField(): void
    {
        $options = [
            ['label' => '1'],
            ['label' => '2'],
        ];
        $dataReduced = [
            'type'         => 'multiselect',
        ];
        $data = $dataReduced + [
            'options'      => $options,
            'defaultValue' => '1',
        ];
        $class           = CustomField::class;
        $customField     = $this->createMock(CustomField::class);
        $classOption     = CustomFieldOption::class;
        $customOptionOne = $this->createMock(CustomFieldOption::class);
        $customOptionTwo = $this->createMock(CustomFieldOption::class);
        $this->normalizerInterface
            ->method('denormalize')
            ->withConsecutive(
                [$options[0], $classOption, null, []],
                [$options[1], $classOption, null, []],
                [$dataReduced, $class, null, []]
            )
            ->willReturnOnConsecutiveCalls($customOptionOne, $customOptionTwo, $customField);
        $customFieldType = $this->createMock(CustomFieldTypeInterface::class);
        $this->customFieldTypeProvider
            ->expects($this->once())
            ->method('getType')
            ->with('multiselect')
            ->willReturn($customFieldType);
        $customField
            ->expects($this->once())
            ->method('setTypeObject')
            ->with($customFieldType);
        $customField
            ->method('addOption')
            ->withConsecutive([$customOptionOne], [$customOptionTwo]);
        $customField
            ->expects($this->once())
            ->method('setDefaultValue')
            ->with('1');
        $typeObjectMock = $this->createMock(IntType::class);
        $customField
            ->expects($this->once())
            ->method('getTypeObject')
            ->willReturn($typeObjectMock);
        $returnedEntity = $this->apiNormalizer->denormalize($data, $class);
        $this->assertSame($customField, $returnedEntity);
    }
}

if (interface_exists('ApiPlatform\\Core\\Api\\IriConverterInterface')) {
    abstract class Normalizer implements NormalizerInterface, DenormalizerInterface
    {
    }
}
