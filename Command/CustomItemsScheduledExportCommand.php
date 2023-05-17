<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomItemsScheduledExportCommand extends Command
{
    public const COMMAND_NAME = 'mautic:custom_items:scheduled_export';

    private CustomItemExportSchedulerModel $customItemExportSchedulerModel;
    private EventDispatcherInterface $eventDispatcher;
    private FormatterHelper $formatterHelper;

    public function __construct(
        CustomItemExportSchedulerModel $customItemExportSchedulerModel,
        EventDispatcherInterface $eventDispatcher,
        FormatterHelper $formatterHelper
    ) {
        $this->customItemExportSchedulerModel = $customItemExportSchedulerModel;
        $this->eventDispatcher                = $eventDispatcher;
        $this->formatterHelper                = $formatterHelper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Export custom items which are scheduled in `custom_item_export_scheduler` table.')
            ->addOption(
                '--ids',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma separated custom_item_export_scheduler ids.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ids                        = $this->formatterHelper->simpleCsvToArray($input->getOption('ids'), 'int');
        $customItemExportSchedulers = $this->customItemExportSchedulerModel->getRepository()->findBy(['id' => $ids]);
        $count                      = 0;

        foreach ($customItemExportSchedulers as $customItemExportScheduler) {
            $customItemExportSchedulerEvent = new CustomItemExportSchedulerEvent($customItemExportScheduler);
            $this->eventDispatcher->dispatch($customItemExportSchedulerEvent, CustomItemEvents::CUSTOM_ITEM_PREPARE_EXPORT_FILE);
            $this->eventDispatcher->dispatch($customItemExportSchedulerEvent, CustomItemEvents::CUSTOM_ITEM_MAIL_EXPORT_FILE);
            $this->eventDispatcher->dispatch($customItemExportSchedulerEvent, CustomItemEvents::POST_EXPORT_MAIL_SENT);
            ++$count;
        }

        $output->writeln('CustomItem export email(s) sent: '.$count);

        return ExitCode::SUCCESS;
    }
}
