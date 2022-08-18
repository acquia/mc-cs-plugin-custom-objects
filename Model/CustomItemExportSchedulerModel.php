<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Helper\MailHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemExportSchedulerRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CustomItemExportSchedulerModel extends AbstractCommonModel
{
    private const EXPORT_FILE_NAME_DATE_FORMAT = 'Y_m_d_H_i_s';
    private SessionInterface $session;
    private RequestStack $requestStack;
    private ExportHelper $exportHelper;
    private MailHelper $mailHelper;
    private CustomItemRouteProvider $customItemRouteProvider;
    private string $filePath;
    private CustomFieldValueModel $customFieldValueModel;
    private $handler;
    private const CUSTOM_ITEM_LIMIT = 200;
    private const CONTACT_LIMIT = 1000000;

    public function __construct(
        SessionInterface $session,
        RequestStack     $requestStack,
        ExportHelper     $exportHelper,
        MailHelper       $mailHelper,
        CustomFieldValueModel $customFieldValueModel,
        CustomItemRouteProvider $customItemRouteProvider
    )
    {
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->exportHelper = $exportHelper;
        $this->mailHelper = $mailHelper;
        $this->customFieldValueModel = $customFieldValueModel;
        $this->customItemRouteProvider = $customItemRouteProvider;
    }

    /**
     * @return CustomItemExportSchedulerRepository
     */
    public function getRepository(): CustomItemExportSchedulerRepository
    {
        /** @var CustomItemExportSchedulerRepository $repo */
        $repo = $this->em->getRepository(CustomItemExportScheduler::class);

        return $repo;
    }

    /**
     * @param int $customObjectId
     * @return CustomItemExportScheduler
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveEntity(int $customObjectId): CustomItemExportScheduler
    {
        $customItemExportScheduler = new CustomItemExportScheduler();
        $customItemExportScheduler
            ->setUser($this->userHelper->getUser())
            ->setScheduledDateTime(new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setCustomObjectId($customObjectId);

        $this->em->persist($customItemExportScheduler);
        $this->em->flush();

        return $customItemExportScheduler;
    }

    /**
     * @param CustomItemExportScheduler $customItemExportScheduler
     * @return string
     */
    public function processDataAndGetExportFilePath(CustomItemExportScheduler $customItemExportScheduler): string
    {
        $scheduledDateTime = $customItemExportScheduler->getScheduledDateTime();
        $fileName          = 'custom_items_export_'.$scheduledDateTime->format(self::EXPORT_FILE_NAME_DATE_FORMAT).'.csv';

        $filePath = $this->exportHelper->getValidCustomItemExportFileName($fileName);

        $this->filePath = $filePath;

        $this->processCustomItemData($customItemExportScheduler);

        return $this->exportHelper->zipFile($this->filePath, 'custom_item_exports.csv');
    }

    /**
     * @param CustomItemExportScheduler $customItemExportScheduler
     */
    private function processCustomItemData(CustomItemExportScheduler $customItemExportScheduler)
    {
        $customObjectRepo = $this->em->getRepository(CustomObject::class);

        $customObject = $customObjectRepo->find($customItemExportScheduler->getCustomObject());

        $customFields = $customObject->getCustomFields()->toArray();

        $this->openFileHandler();
        $this->addExportFileHeaderToCsvFile($customFields);

        $this->addCustomItemsToCsvFile($customFields, $customObject);
        $this->closeFile();
    }

    /**
     * @param array $customFields
     * @return array
     */
    private function getCSVHeader(array $customFields): array
    {
        $header = ["customItemId", "customItemName"];

        foreach ($customFields as $customField) {
            $header[] = $customField->getId();
        }

        $header[] = "linkedContactIds";

        return $header;
    }

    /**
     * @param array $customFields
     * @param CustomObject $customObject
     */
    private function addCustomItemsToCsvFile(array $customFields, CustomObject $customObject): void
    {
        $offset = 0;
        $result = true;

        while($result) {
            $customItems = $this->em->getRepository(CustomItem::class)
                ->getCustomItemsRelatedToProvidedCustomObject($customObject->getId(), self::CUSTOM_ITEM_LIMIT, $offset);

            if (count($customItems) == 0) {
                return;
            }

            if (count($customItems) < self::CUSTOM_ITEM_LIMIT) {
                $result = false;
            } else {
                $offset += self::CUSTOM_ITEM_LIMIT;
            }

            $listData = $this->customFieldValueModel->getItemsListData($customObject->getCustomFields(), $customItems);

            foreach ($customItems as $customItem) {
                $rowData = [];
                $rowData[] = $customItem->getId();
                $rowData[] = $customItem->getName();

                foreach ($customFields as $customField) {
                    $rowData[] = $listData->getFields($customItem->getId())[$customField->getId()]->getValue();
                }

                $fetchResult = true;
                $savedRow = $rowData;
                $contactLimit = 200;
                $contactOffset = 0;
                $customItemAdded = false;

                while($fetchResult) {
                    $results = $this->getContactIds($customItem, $contactLimit, $contactOffset);

                    if (count($results) == 0 && $customItemAdded) {
                        break;
                    }

                    if(count($results) < $contactLimit) {
                        $fetchResult = false;
                    } else {
                        $contactOffset+=$contactLimit;
                    }

                    $rowData = $savedRow;
                    $rowData[] = implode(',', $results);
                    $this->addToCsvFile($rowData);
                    $customItemAdded = true;
                }
            }
        }
    }

    /**
     * @param array $customFields
     */
    private function addExportFileHeaderToCsvFile(array $customFields): void
    {
        $header = $this->getCSVHeader($customFields);

        $this->addToCsvFile($header);
    }

    /**
     * @param CustomItem $customItem
     * @return array
     */
    private function getContactIds(CustomItem $customItem, int $limit = 200, int $offset = 0): array
    {
        $rowData = [];

        $contactIds = $this->em->getRepository(CustomItemXrefContact::class)
            ->getContactIdsLinkedToCustomItem($customItem->getId(), $limit, $offset);

        foreach($contactIds as $row) {
            $rowData[] = $row->getContact()->getId();
        }

        return $rowData;
    }

    private function openFileHandler(): void
    {
        $this->handler = fopen($this->filePath, 'ab');
    }

    /**
     * @param $data
     */
    private function addToCsvFile($data): void
    {
        fputcsv($this->handler, $data);
    }

    private function closeFile(): void
    {
        fclose($this->handler);
    }

    /**
     * @param CustomItemExportScheduler $customItemExportScheduler
     * @param string $filePath
     */
    public function sendEmail(CustomItemExportScheduler $customItemExportScheduler, string $filePath): void
    {
        $user = $customItemExportScheduler->getUser();

        $message = $this->getEmailMessageWithLink($filePath);
        $this->mailHelper->setTo([$user->getEmail() => $user->getName()]);
        $this->mailHelper->setSubject(
            $this->translator->trans('custom.item.export.email_subject', ['%file_name%' => basename($filePath)])
        );
        $this->mailHelper->setBody($message);
        $this->mailHelper->parsePlainText($message);
        $this->mailHelper->attachFile($filePath);
        $this->mailHelper->send(true);
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function getEmailMessageWithLink(string $filePath): string
    {
        $link = $this->customItemRouteProvider->buildExportDownloadRoute(basename($filePath));

        return $this->translator->trans(
            'custom.item.export.email',
            ['%link%' => $link, '%label%' => basename($filePath)]
        );
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param CustomItemExportScheduler $customItemExportScheduler
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteEntity(CustomItemExportScheduler $customItemExportScheduler): void
    {
        $this->em->remove($customItemExportScheduler);
        $this->em->flush();
    }

    /**
     * @param string $fileName
     * @return BinaryFileResponse
     */
    public function getExportFileToDownload(string $fileName): BinaryFileResponse
    {
        $filePath    = $this->coreParametersHelper->get('custom_item_export_dir').'/'.$fileName;

        return new BinaryFileResponse(
            $filePath,
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Expires'             => 0,
                'Cache-Control'       => 'must-revalidate',
                'Pragma'              => 'public',
            ]
        );
    }
}
