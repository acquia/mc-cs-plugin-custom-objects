<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Command;

use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class DeleteCustomObjectCommand extends ContainerAwareCommand
{

    public const COMMAND_NAME = 'mautic:customobjects:deletecustomobject';

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(CustomObjectRepository $customObjectRepository, EntityManager $entityManager)
    {
        parent::__construct();

        $this->customObjectRepository = $customObjectRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Deletes a custom object and all of its related children (if any).')
            ->addArgument(
                'custom-object-id',
                InputOption::VALUE_REQUIRED,
                'ID of the custom object to delete'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $customObjectId = (int)$input->getArgument('custom-object-id');

        /** @var CustomObject $customObject */
        $customObject = $this->customObjectRepository->find($customObjectId);

        if (!$customObject instanceof CustomObject) {
            $errorMessage = sprintf('Custom object ID #%s doesn\'t exist. It\'s impossible to delete it', $customObjectId);
            $io->error($errorMessage);
            return 1;
        }

        try {
            $this->entityManager->beginTransaction();
            $this->deleteCustomObject($customObject);
            $this->entityManager->commit();
        }
        catch (\Exception $e) {
            $this->entityManager->rollback();
            $errorMessage = sprintf('The following error occurred when trying to delete custom object ID #%s (and the changes were rolled back): %s', $customObjectId, $e->getMessage());
            $io->error($errorMessage);
            return 1;
        }

        return 0;
    }

    private function deleteCustomObject(CustomObject $customObject): void
    {
        throw new \Exception('1');
    }
}
