<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var CustomObject[]
     */
    private $customObjects = [];

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        CustomObjectModel $customObjectModel,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    ) {
        parent::__construct($coreParametersHelper->all());

        $this->customObjectModel = $customObjectModel;
        $this->configProvider    = $configProvider;
        $this->translator        = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function definePermissions(): void
    {
        $this->addExtendedPermissions(['custom_fields', self::NAME]);

        $customObjects = $this->getCustomObjects();
        foreach ($customObjects as $customObject) {
            $this->addExtendedPermissions([$customObject->getId()]);
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
                            'column' => CustomObject::TABLE_ALIAS.'.isPublished',
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
