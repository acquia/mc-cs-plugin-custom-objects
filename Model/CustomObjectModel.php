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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomObjectModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @param EntityManager $entityManager
     * @param CustomObjectRepository $customObjectRepository
     */
    public function __construct(
        EntityManager $entityManager,
        CustomObjectRepository $customObjectRepository
    )
    {
        $this->entityManager                   = $entityManager;
        $this->customObjectRepository = $customObjectRepository;
    }

    /**
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    public function save(CustomObject $entity): CustomObject
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
     * @return CustomObject
     * 
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomObject
    {
        $entity = parent::getEntity($id);

        if (null === $entity) {
            throw new NotFoundException("Custom Object  with ID = {$id} was not found");
        }

        return $entity;
    }

    /**
     * @return CommonRepository
     */
    public function getRepository(): CommonRepository
    {
        return $this->customObjectRepository;
    }

    /**
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    private function sanitizeAlias(CustomObject $entity): CustomObject
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
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    private function ensureUniqueAlias(CustomObject $entity): CustomObject
    {
        $testAlias = $entity->getAlias();
        $isUnique  = $this->customObjectRepository->isAliasUnique($testAlias, $entity->getId());
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
