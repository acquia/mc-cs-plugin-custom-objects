<?php


namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
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
     * @var \PHPUnit\Framework\MockObject\MockObject|DenormalizerInterface|NormalizerInterface
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
     * @var ApiNormalizer
     */
    private $apiNormalizer;

    public function setUp(): void
    {
        $this->normalizerInterface     = $this->createMock([NormalizerInterface::class, DenormalizerInterface::class]);
        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->iriConverter            = $this->createMock(IriConverterInterface::class);
        $this->customItemModel         = $this->createMock(CustomItemModel::class);
        $this->apiNormalizer           = new ApiNormalizer($this->normalizerInterface, $this->customFieldTypeProvider, $this->customItemModel, $this->iriConverter);
        parent::setUp();
    }

    public function testDenormalizeNotCustomField(): void
    {
        $data  = ['sth' => 'sth'];
        $class = CustomObject::class;
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
            ['label' => '2']
        ];
        $dataReduced = [
            'type'         => 'multiselect'
        ];
        $data = $dataReduced + [
            'options'      => $options,
            'defaultValue' => '1',
        ];
        $class           = CustomField::class;
        $customField    = $this->createMock(CustomField::class);
        $classOption     = CustomFieldOption::class;
        $customOptionOne = $this->createMock(CustomFieldOption::class);
        $customOptionTwo = $this->createMock(CustomFieldOption::class);
        $this->normalizerInterface
            ->expects($this->at(0))
            ->method('denormalize')
            ->with($options[0], $classOption, null, [])
            ->willReturn($customOptionOne);
        $this->normalizerInterface
            ->expects($this->at(1))
            ->method('denormalize')
            ->with($options[1], $classOption, null, [])
            ->willReturn($customOptionTwo);
        $this->normalizerInterface
            ->expects($this->at(2))
            ->method('denormalize')
            ->with($dataReduced, $class, null, [])
            ->willReturn($customField);
        $customFieldType = $this->createMock(CustomFieldTypeInterface::class);
        $this->customFieldTypeProvider
            ->expects($this->once())
            ->method('getType')
            ->with('multiselect')
            ->willReturn($customFieldType);
        $customField
            ->expects($this->at(0))
            ->method('setTypeObject')
            ->with($customFieldType);
        $customField
            ->expects($this->at(1))
            ->method('setOptions')
            ->with(new ArrayCollection([$customOptionOne, $customOptionTwo]));
        $customField
            ->expects($this->at(2))
            ->method('setDefaultValue')
            ->with('1');
        $returnedEntity = $this->apiNormalizer->denormalize($data, $class);
        $this->assertSame($customField, $returnedEntity);
    }
}
