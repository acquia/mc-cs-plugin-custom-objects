<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\CommonRepository;

class CustomCommonRepository extends CommonRepository
{
    public function __construct(ManagerRegistry $registry, string $entityFQCN = null)
    {
        $entityFQCN = $entityFQCN
            ?? preg_replace('/(.*)\\\\Repository(.*)Repository?/', '$1\Entity$2', static::class);
        parent::__construct($registry, $entityFQCN);
    }
}
