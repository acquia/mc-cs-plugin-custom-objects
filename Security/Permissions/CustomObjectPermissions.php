<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomObjectPermissions extends AbstractPermissions
{
    public const NAME = 'custom_objects';

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Cached custom objects.
     *
     * @var array
     */
    private $customObjects = [];

    /**
     * @param array $params
     * @param CustomObjectModel $customObjectModel
     * @param ConfigProvider $configProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        array $params,
        CustomObjectModel $customObjectModel,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    )
    {
        parent::__construct($params);

        $this->customObjectModel = $customObjectModel;
        $this->configProvider    = $configProvider;
        $this->translator        = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function definePermissions(): void
    {
        $this->addExtendedPermissions('custom_fields');
        $this->addExtendedPermissions(self::NAME);

        $customObjects = $this->getCustomObjects();
        foreach ($customObjects as $customObject) {
            $this->addExtendedPermissions($customObject->getId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addExtendedFormFields(self::NAME, 'custom_fields', $builder, $data);
        $this->addExtendedFormFields(self::NAME, self::NAME, $builder, $data);

        $customObjects = $this->getCustomObjects();
        foreach ($customObjects as $customObject) {
            $this->addExtendedFormFields(self::NAME, $customObject->getId(), $builder, $data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return $this->configProvider->pluginIsEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabel($bundle, $level): string
    {
        if (is_numeric($level)) {
            $customObject = $this->getCustomObjects()[$level];

            return $this->translator->trans(
                'custom.object.permissions',
                ['%name%' => $customObject->getNamePlural()]
            );
        }

        return parent::getLabel($bundle, $level);
    }

    /**
     * Fetches published custom objects once and returns the cached array if fetched already.
     *
     * @return CustomObject[]
     */
    private function getCustomObjects(): array
    {
        if (!$this->customObjects) {
            $this->customObjects = $this->customObjectModel->getEntities([
                'ignore_paginator' => true,
                'filter'           => [
                    'force' => [
                        [
                            'column' => 'e.isPublished',
                            'value'  => true,
                            'expr'   => 'eq',
                        ],
                    ],
                ],
            ]);
        }

        return $this->customObjects;
    }
}