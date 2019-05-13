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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class CustomObjectRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $entityManager;
    private $classMetadata;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->classMetadata          = $this->createMock(ClassMetadata::class);
        $this->customObjectRepository = new CustomObjectRepository(
            $this->entityManager,
            $this->classMetadata
        );
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame(CustomObject::TABLE_ALIAS, $this->customObjectRepository->getTableAlias());
    }
}
