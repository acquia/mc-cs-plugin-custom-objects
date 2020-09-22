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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DeleteCustomObjectCommand extends ContainerAwareCommand
{
    public function __construct() {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:customobjects:deletecustomobject')
            ->setDescription('Deletes a custom object and all of its related children.')
            ->addOption(
                '--custom-object-id',
                '-i',
                InputOption::VALUE_REQUIRED,
                'ID of the Custom Object to delete'
            );
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}
