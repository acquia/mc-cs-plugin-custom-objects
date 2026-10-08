<?php

namespace MauticPlugin\CustomObjectsBundle\DataPersister;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;

final class CustomItemDataPersister
{
    private CustomItemModel $customItemModel;

    public function __construct(CustomItemModel $customItemModel)
    {
        $this->customItemModel = $customItemModel;
    }

    /**
     * @param mixed $data
     */
    public function supports($data): bool
    {
        return $data instanceof CustomItem;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function persist($data)
    {
        \assert($data instanceof CustomItem);

        $this->customItemModel->save($data);

        return $data;
    }

    /**
     * @param mixed $data
     */
    public function remove($data): void
    {
        \assert($data instanceof CustomItem);

        $this->customItemModel->delete($data);
    }
}
