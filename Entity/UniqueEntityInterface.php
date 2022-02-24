<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

interface UniqueEntityInterface
{
    /**
     * @return mixed
     */
    public function getId();
}
