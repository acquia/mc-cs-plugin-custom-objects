<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObjectStructure;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectStructureRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomObjectStructureModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomObjectStructureRepository
     */
    private $customObjectStructureRepository;

    /**
     * @param EntityManager $entityManager
     * @param CustomObjectStructureRepository $customObjectStructureRepository
     */
    public function __construct(
        EntityManager $entityManager,
        CustomObjectStructureRepository $customObjectStructureRepository
    )
    {
        $this->entityManager                   = $entityManager;
        $this->customObjectStructureRepository = $customObjectStructureRepository;
    }

    /**
     * @param CustomObjectStructure $entity
     * 
     * @return CustomObjectStructure
     */
    public function save(CustomObjectStructure $entity): CustomObjectStructure
    {
        $entity = $this->sanitizeAlias($entity);
        $entity = $this->ensureUniqueAlias($entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param integer $id
     * 
     * @return CustomObjectStructure
     * 
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomObjectStructure
    {
        $entity = parent::getEntity($id);

        if (null === $entity) {
            throw new NotFoundException("Custom Object Structure with ID = {$id} was not found");
        }

        return $entity;
    }

    /**
     * @return CommonRepository
     */
    public function getRepository(): CommonRepository
    {
        return $this->customObjectStructureRepository;
    }

    /**
     * @param CustomObjectStructure $entity
     * 
     * @return CustomObjectStructure
     */
    private function sanitizeAlias(CustomObjectStructure $entity): CustomObjectStructure
    {
        $dirtyAlias = $entity->getAlias();

        if (empty($dirtyAlias)) {
            $dirtyAlias = $entity->getName();
        }

        $cleanAlias = $this->cleanAlias($dirtyAlias, '', false, '-');

        $entity->setAlias($cleanAlias);

        return $entity;
    }

    /**
     * Make sure alias is not already taken.
     *
     * @param CustomObjectStructure $entity
     * 
     * @return CustomObjectStructure
     */
    private function ensureUniqueAlias(CustomObjectStructure $entity): CustomObjectStructure
    {
        $testAlias = $entity->getAlias();
        $isUnique  = $this->customObjectStructureRepository->isAliasUnique($testAlias, $entity->getId());
        $counter   = 1;

        while ($isUnique) {
            $testAlias = $testAlias.$counter;
            $isUnique  = $repo->isAliasUnique($testAlias, $entity->getId());
            ++$counter;
        }

        if ($testAlias !== $entity->getAlias()) {
            $entity->setAlias($testAlias);
        }

        return $entity;
    }
}
