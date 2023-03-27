<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Helper\RandomHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateSampleDataCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RandomHelper
     */
    private $randomHelper;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        EntityManager $entityManager,
        RandomHelper $randomHelper
    ) {
        parent::__construct();

        $this->entityManager     = $entityManager;
        $this->randomHelper      = $randomHelper;
        $this->connection        = $entityManager->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:customobjects:generatesampledata')
            ->setDescription('Creates specified amount of custom items with random links to contacts, random names and random custom field values.')
            ->addOption(
                '--limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'How many custom items to create. Defaults to 10'
            )
            ->addOption(
                '--force',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Without confirmation. Use --force=1'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io       = new SymfonyStyle($input, $output);
        $enquirer = $this->getHelper('question');
        $limit    = (int) $input->getOption('limit');
        $force    = (bool) (int) $input->getOption('force');

        if (!$limit) {
            $limit = 1000;
        }

        if (!$force) {
            $confirmation = new ConfirmationQuestion("Do you really want to delete current data and generate {$limit} sample of contacts? [Y/n] ", false);

            if (!$enquirer->ask($input, $output, $confirmation)) {
                return 0;
            }
        }

        $startTime = microtime(true);
        $progress  = new ProgressBar($output, $limit);
        $progress->setFormat(' %current%/%max% [%bar%] | %percent:3s%% | Elapsed: %elapsed:6s% | Estimated: %estimated:-6s% | Memory Usage: %memory:6s%');
        $progress->start();

        $this->cleanupDB();
        [$coProductId, $cfPriceId, $coOrderId] = $this->createCustomObjectsWithItems();

        for ($i = 1; $i <= $limit; ++$i) {
            $this->generateContact($coProductId, $cfPriceId, $coOrderId, $limit);
            $this->entityManager->clear();

            $progress->advance();
        }

        $progress->finish();
        $runTime = gmdate('H:i:s', (int) (microtime(true) - $startTime));

        $io->success("Execution time: {$runTime}");

        return 0;
    }

    /**
     * @return int[]
     *
     * @throws DBALException
     */
    private function createCustomObjectsWithItems(): array
    {
        $coProduct = [
            'is_published'  => true,
            'alias'         => 'product',
            'name_singular' => 'Product',
            'name_plural'   => 'Products',
            'type'          => 0,
        ];

        $coProductId = $this->insertInto('custom_object', $coProduct);

        $cfPrice = [
            'is_published'     => true,
            'custom_object_id' => $coProductId,
            'alias'            => 'price',
            'Label'            => 'Price',
            'type'             => 'int',
            'required'         => 0,
        ];

        $cfPriceId = $this->insertInto('custom_field', $cfPrice);

        $coOrder = [
            'is_published'  => true,
            'alias'         => 'order',
            'name_singular' => 'Order',
            'name_plural'   => 'Orders',
            'type'          => 0,
        ];

        $coOrderId = $this->insertInto('custom_object', $coOrder);

        return [$coProductId, $cfPriceId, $coOrderId];
    }

    private function cleanupDB(): void
    {
        $query = 'delete from '.MAUTIC_TABLE_PREFIX.'leads where 1';
        $this->connection->query($query);

        $query = 'delete from '.MAUTIC_TABLE_PREFIX.'custom_object where 1';
        $this->connection->query($query);
    }

    private function generateContact(int $coProductId, int $cfPriceId, int $coOrderId, int $priceLimit): void
    {
        $contact = [
            'firstname'    => $this->randomHelper->getWord(),
            'lastname'     => $this->randomHelper->getWord(),
            'email'        => $this->randomHelper->getEmail(),
            'is_published' => true,
            'points'       => 0,
        ];

        $contactId = $this->insertInto('leads', $contact);

        $this->generateProductRelations($contactId, $coProductId, $cfPriceId, $coOrderId, $priceLimit);
    }

    private function generateProductRelations(int $contactId, int $coProductId, int $cfPriceId, int $coOrderId, int $priceLimit): void
    {
        $ciProduct = [
            'custom_object_id' => $coProductId,
            'name'             => $this->randomHelper->getWord(),
            'is_published'     => true,
        ];

        $ciProductId = $this->insertInto('custom_item', $ciProduct);

        $ciValueInt = [
            'custom_field_id' => $cfPriceId,
            'custom_item_id'  => $ciProductId,
            'value'           => rand(1, $priceLimit),
        ];

        $this->insertInto('custom_field_value_int', $ciValueInt);

        $ciOrder = [
            'custom_object_id' => $coOrderId,
            'name'             => $this->randomHelper->getWord(),
            'is_published'     => true,
        ];

        $ciOrderId = $this->insertInto('custom_item', $ciOrder);

        $dateAdded = date('Y-m-d H:i:s');

        $cixContact = [
            'custom_item_id' => $ciProductId,
            'contact_id'     => $contactId,
            'date_added'     => $dateAdded,
        ];

        $this->insertInto('custom_item_xref_contact', $cixContact);

        $cixci = [
            'custom_item_id_lower'  => $ciProductId,
            'custom_item_id_higher' => $ciOrderId,
            'date_added'            => $dateAdded,
        ];

        $this->insertInto('custom_item_xref_custom_item', $cixci);
    }

    /**
     * @return int Last inserted row ID
     *
     * @throws DBALException
     */
    private function insertInto(string $table, array $row): int
    {
        $table       = MAUTIC_TABLE_PREFIX.$table;
        $columnNames = implode(',', array_keys($row));
        $values      = implode(
            ',',
            array_map(
                function ($value) {
                    switch (gettype($value)) {
                        case 'string':
                            return "'$value'";
                        case 'integer':
                            return (string) $value;
                        case 'boolean':
                            return (bool) $value;
                        default:
                            $type = gettype($value);
                            throw new \InvalidArgumentException("Unsupported type '$type' for insert query");
                    }
                },
                array_values($row)
            )
        );

        $query = "
            INSERT INTO `$table` ($columnNames)
            VALUES ($values)
        ";

        $this->connection->query($query);

        return (int) $this->connection->lastInsertId();
    }
}
