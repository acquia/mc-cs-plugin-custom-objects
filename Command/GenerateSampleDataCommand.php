<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Helper\RandomHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
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
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RandomHelper
     */
    private $randomHelper;

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        LeadModel $contactModel,
        EntityManager $entityManager,
        RandomHelper $randomHelper
    ) {
        parent::__construct();

        $this->customObjectModel = $customObjectModel;
        $this->customItemModel   = $customItemModel;
        $this->contactModel      = $contactModel;
        $this->entityManager     = $entityManager;
        $this->randomHelper      = $randomHelper;
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
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
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
            $confirmation = new ConfirmationQuestion("Do you really want to generate {$limit} sample {$customObject->getNamePlural()}? [Y/n] ", false);

            if (!$enquirer->ask($input, $output, $confirmation)) {
                return 0;
            }
        }

        $startTime = microtime(true);
        $progress  = new ProgressBar($output, $limit);
        $progress->setFormat(' %current%/%max% [%bar%] | %percent:3s%% | Elapsed: %elapsed:6s% | Estimated: %estimated:-6s% | Memory Usage: %memory:6s%');
        $progress->start();

        [$coProductId, $cfPrice, $coOrderId] = $this->createCustomObjectsWithItems();

        for ($i = 1; $i <= $limit; ++$i) {

            $contactId = $this->generateContact();

            $progress->advance();
        }

        $progress->finish();
        $runTime = gmdate('H:i:s', (int) (microtime(true) - $startTime));

        $io->success("Execution time: {$runTime}");

        return 0;
    }

    /**
     * @return int[]
     * @throws DBALException
     */
    private function createCustomObjectsWithItems(): array
    {
        $coProduct = [
            'is_published' => true,
            'alias' => 'product',
            'name_singular' => 'Product',
            'name_plural' => 'Products',
            'type' => 0,
        ];

        $coProductId = $this->insertInto('custom_object', $coProduct);

        $cfPrice = [
            'is_published' => true,
            'custom_object_id' => $coProductId,
            'alias' => 'price',
            'Label' => 'Price',
            'type' => 'int',
            'required' => 0,
        ];

        $cfPriceId = $this->insertInto('custom_field', $cfPrice);

        $coOrder = [
            'is_published' => true,
            'alias' => 'order',
            'name_singular' => 'Order',
            'name_plural' => 'Orders',
            'type' => 0,
        ];

        $coOrderId = $this->insertInto('custom_object', $coOrder);

        return [$coProductId, $cfPriceId, $coOrderId];
    }

    private function generateContact(): void
    {
        $contact = [
            'firstname'    => $this->randomHelper->getWord(),
            'lastname'     => $this->randomHelper->getWord(),
            'email'        => $this->randomHelper->getEmail(),
            'is_published' => true,
            'points'       => 0,
        ];

        $this->insertInto('leads', $contact);
    }

    /**
     * @param  string $table
     * @param  array  $row
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
                            break;
                        case 'integer':
                            return (string) $value;
                            break;
                        case 'boolean':
                            return (bool) $value;
                            break;
                        default:
                            $type = gettype($value);
                            throw new \InvalidArgumentException("Unsupported type '$type' for insert query");
                    }
                },
                array_values($row)
            )
        );

        $query = "
            INSERT INTO `$table`($columnNames)
            VALUES ($values)
        ";

        $connection = $this->entityManager->getConnection();
        $query = $this->entityManager->getConnection()->query($query);

        return (int) $connection->lastInsertId();
    }

    private function generateCustomItem(CustomObject $customObject): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($this->randomHelper->getSentence(random_int(2, 6)));

        return $customItem;
    }

    private function generateCustomFieldValues(CustomItem $customItem, CustomObject $customObject): CustomItem
    {
        foreach ($customObject->getCustomFields() as $field) {
            if ('text' === $field->getType()) {
                $customItem->addCustomFieldValue(new CustomFieldValueText($field, $customItem, $this->randomHelper->getSentence(random_int(0, 100))));
            }

            if ('int' === $field->getType()) {
                $customItem->addCustomFieldValue(new CustomFieldValueInt($field, $customItem, random_int(0, 1000)));
            }
        }

        return $customItem;
    }

    /**
     * Generates up to 10 custom item - contact references and adds them to the CustomItem entity.
     */
    private function generateContactReferences(CustomItem $customItem): CustomItem
    {
        for ($i = 1; $i <= random_int(0, 10); ++$i) {
            $contact   = new Lead();
            $reference = new CustomItemXrefContact($customItem, $contact);
            $contact->setFirstname(ucfirst($this->randomHelper->getWord()));
            $contact->setLastname(ucfirst($this->randomHelper->getWord()));

            $this->contactModel->saveEntity($contact);

            $customItem->addContactReference($reference);
        }

        return $customItem;
    }

    private function clearMemory(CustomItem $customItem): void
    {
        foreach ($customItem->getCustomFieldValues() as $value) {
            $this->entityManager->detach($value);
        }

        foreach ($customItem->getContactReferences() as $reference) {
            $this->entityManager->detach($reference->getContact());
            $this->entityManager->detach($reference);
        }

        $this->entityManager->detach($customItem);
    }
}
