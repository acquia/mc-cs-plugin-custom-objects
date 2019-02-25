<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Helper\RandomHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Console\Style\SymfonyStyle;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use Symfony\Component\Console\Helper\ProgressBar;
use Doctrine\ORM\EntityManager;

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

    /**
     * @param CustomObjectModel $customObjectModel
     * @param CustomItemModel $customItemModel
     * @param LeadModel $contactModel
     * @param EntityManager $entityManager
     * @param RandomHelper $randomHelper
     */
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
    protected function configure()
    {
        $this->setName('mautic:customobjects:generatesampledata')
            ->setDescription('Creates specified amount of custom items with random links to contacts, random names and random custom field values.')
            ->addOption(
                '--object-id',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Set Custom Object ID to know what custom items to generated.'
            )
            ->addOption(
                '--limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'How many custom items to create. Defaults to 10'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io       = new SymfonyStyle($input, $output);
        $enquirer = $this->getHelper('question');
        $objectId = (int) $input->getOption('object-id');
        $limit    = (int) $input->getOption('limit');
        
        if (!$limit) {
            $limit = 1000;
        }
        
        if (!$objectId) {
            $io->error("Provide a Custom Object ID for which you want to generate the custom items. Use --object-id=X");
            
            return 1;
        }

        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
        } catch (NotFoundException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $confirmation = new ConfirmationQuestion("Do you really want to generate {$limit} sample {$customObject->getNamePlural()}? [Y/n] ", false);

        if (!$enquirer->ask($input, $output, $confirmation)) {
            return 0;
        }

        $startTime = microtime(true);
        $progress  = new ProgressBar($output, $limit);
        $progress->setFormat(' %current%/%max% [%bar%] | %percent:3s%% | Elapsed: %elapsed:6s% | Estimated: %estimated:-6s% | Memory Usage: %memory:6s%');
        $progress->start();

        for ($i = 1; $i <= $limit; $i++) {
            $customItem = $this->generateCustomItem($customObject);
            $customItem = $this->generateCustomFieldValues($customItem, $customObject);
            $customItem = $this->generateContactReferences($customItem);
            $this->customItemModel->save($customItem);
            $this->clearMemory($customItem);
            $progress->advance();
        }


        $progress->finish();
        $runTime = gmdate('H:i:s', microtime(true) - $startTime);

        $io->success("Execution time: {$runTime}");

        return 0;
    }

    /**
     * @param CustomObject $customObject
     * 
     * @return CustomItem
     */
    private function generateCustomItem(CustomObject $customObject): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($this->randomHelper->getSentence(rand(2, 6)));

        return $customItem;
    }

    /**
     * @param CustomItem $customItem
     * @param CustomObject $customObject
     * 
     * @return CustomItem
     */
    private function generateCustomFieldValues(CustomItem $customItem, CustomObject $customObject): CustomItem
    {
        foreach ($customObject->getCustomFields() as $field) {
            if ($field->getType() === 'text') {
                $customItem->addCustomFieldValue(new CustomFieldValueText($field, $customItem, $this->randomHelper->getSentence(rand(0, 100))));
            }

            if ($field->getType() === 'int') {
                $customItem->addCustomFieldValue(new CustomFieldValueInt($field, $customItem, rand(0, 1000)));
            }
        }

        return $customItem;
    }

    /**
     * Generates up to 10 custom item - contact references and adds them to the CustomItem entity.
     * 
     * @param CustomItem $customItem
     * 
     * @return CustomItem
     */
    private function generateContactReferences(CustomItem $customItem): CustomItem
    {
        for ($i = 1; $i <= rand(0, 10); $i++) {
            $contact   = new Lead();
            $reference = new CustomItemXrefContact($customItem, $contact);
            $contact->setFirstname(ucfirst($this->randomHelper->getWord()));
            $contact->setLastname(ucfirst($this->randomHelper->getWord()));
    
            $this->contactModel->saveEntity($contact);
    
            $customItem->addContactReference($reference);
        }

        return $customItem;
    }

    /**
     * @param CustomItem $customItem
     */
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
