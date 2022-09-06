<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemExportSchedulerRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidatorClassMetadata;

class CustomItemExportScheduler
{
    private int $id;

    private User $user; // Created by

    private DateTimeImmutable $scheduledDateTime;

    private int $customObjectId;

    /** @var array<mixed> */
    private array $changes = [];

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_item_export_scheduler');
        $builder->setCustomRepositoryClass(CustomItemExportSchedulerRepository::class);
        $builder->addId();
        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', true, false, 'CASCADE')
            ->build();
        $builder->createField('scheduledDateTime', Types::DATETIME_IMMUTABLE)
            ->columnName('scheduled_datetime')
            ->build();
        $builder->createField('customObjectId', Types::BIGINT)
           ->columnName('custom_object_id')
           ->build();
    }

    public static function loadValidatorMetadata(ValidatorClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'scheduledDate',
            new Assert\NotBlank(
                ['message' => 'mautic.lead.import.dir.notblank']
            )
        );

        $metadata->addPropertyConstraint(
            'customObjectId',
            new Assert\NotBlank(
                ['message' => 'custom.object.export.notblank']
            )
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->addChange('user', $user->getId());

        return $this;
    }

    public function getScheduledDateTime(): ?DateTimeImmutable
    {
        return $this->scheduledDateTime;
    }

    public function setScheduledDateTime(DateTimeImmutable $scheduledDateTime): self
    {
        $this->scheduledDateTime = $scheduledDateTime;
        $this->addChange('scheduledDateTime', $scheduledDateTime);

        return $this;
    }

    public function getCustomObject(): int
    {
        return $this->customObjectId;
    }

    public function setCustomObjectId(int $customObjectId): self
    {
        $this->customObjectId = $customObjectId;
        $this->addChange('customObject', $customObjectId);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @param mixed $value
     */
    private function addChange(string $property, $value): void
    {
        $this->changes[$property] = $value;
    }
}
