<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CommonRepository<CustomItemExportScheduler>
 */
class CustomItemExportSchedulerRepository extends CommonRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomItemExportScheduler::class);
    }
}
