<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\CoreBundle\Event\BuilderEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilters;
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;
use MauticPlugin\CustomObjectsBundle\DTO\Token;

/**
 * Handles Custom Object token replacements with the correct value in emails.
 */
class TokenSubscriber implements EventSubscriberInterface
{
    use MatchFilterForLeadTrait;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ContactSegmentFilterFactory
     */
    private $contactSegmentFilterFactory;

    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var TokenParser
     */
    private $tokenParser;

    /**
     * @param ConfigProvider              $configProvider
     * @param ContactSegmentFilterFactory $contactSegmentFilterFactory
     * @param QueryFilterHelper           $queryFilterHelper
     * @param CustomObjectModel           $customObjectModel
     * @param CustomItemModel             $customItemModel
     * @param TokenParser                 $tokenParser
     */
    public function __construct(
        ConfigProvider $configProvider,
        ContactSegmentFilterFactory $contactSegmentFilterFactory,
        QueryFilterHelper $queryFilterHelper,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        TokenParser $tokenParser
    ) {
        $this->configProvider              = $configProvider;
        $this->contactSegmentFilterFactory = $contactSegmentFilterFactory;
        $this->queryFilterHelper           = $queryFilterHelper;
        $this->customObjectModel           = $customObjectModel;
        $this->customItemModel             = $customItemModel;
        $this->tokenParser                 = $tokenParser;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD                      => ['onBuilderBuild', 0],
            EmailEvents::EMAIL_ON_SEND                       => ['decodeTokens', 0],
            EmailEvents::EMAIL_ON_DISPLAY                    => ['decodeTokens', 0],
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY => ['onListQuery', -1],
        ];
    }

    /**
     * @param BuilderEvent $event
     */
    public function onBuilderBuild(BuilderEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if (!$event->tokensRequested(TokenParser::TOKEN)) {
            return;
        }

        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();

        /** @var CustomObject $customObject */
        foreach ($customObjects as $customObject) {
            /** @var CustomField $customField */
            foreach ($customObject->getCustomFields() as $customField) {
                $token = "{custom-object={$customObject->getAlias()}:{$customField->getAlias()} | where=segment-filter | order=latest | limit=1 | default=}";
                $label = "{$customObject->getName()}: {$customField->getLabel()}";
                $event->addToken($token, $label);
            }
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function decodeTokens(EmailSendEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $tokens = $this->tokenParser->findTokens($event->getContent());

        if (0 === $tokens->count()) {
            return;
        }

        $tokens->map(function (Token $token) use ($event) {
            $orderBy = CustomItem::TABLE_ALIAS.'.date_added';
            $orderDir = 'DESC';
            try {
                $customObject = $this->customObjectModel->fetchEntityByAlias($token->getCustomObjectAlias());
            } catch (NotFoundException $e) {
                return;
            }

            $segmentConditions = null;

            if ('segment-filter' === $token->getWhere()) {
                if ('list' === $event->getEmail()->getEmailType()) {
                    // Validation check that all segments have the same CO filters, so let's take the first one.
                    /** @var LeadList $segment */
                    $segment = clone $event->getEmail()->getLists()->first();
                    $filters = [];
                    /** @var ContactSegmentFilter $filter */
                    foreach ($segment->getFilters() as $filter) {
                        if ('custom_object' === $filter['object']) {
                            $filters[] = $filter;
                        }
                    }
                    $segment->setFilters($filters);
                    $segmentConditions = $this->contactSegmentFilterFactory->getSegmentFilters($segment);
                }
                // @todo implement also campaign emails.
            }

            if ('latest' === $token->getOrder()) {
                // Use the default order and direction.
            }

            $tableConfig = new TableConfig($token->getLimit(), 1, $orderBy, $orderDir);
            $tableConfig->addParameter('customObjectId', $customObject->getId());
            $tableConfig->addParameter('filterEntityType', 'contact');
            $tableConfig->addParameter('filterEntityId', (int) $event->getLead()['id']);
            $tableConfig->addParameter('tokenWhere', $token->getWhere());
            $tableConfig->addParameter('segmentConditions', $segmentConditions);
            $customItems = $this->customItemModel->getArrayTableData($tableConfig);
            $fieldValues = [];

            foreach ($customItems as $customItemData) {
                $customItem = new CustomItem($customObject);
                $customItem->populateFromArray($customItemData);
                $customItem = $this->customItemModel->populateCustomFields($customItem);

                try {
                    $fieldValues[] = $customItem->findCustomFieldValueForFieldAlias($token->getCustomFieldAlias())->getValue();
                } catch (NotFoundException $e) {
                    // Custom field not found.
                }
            }

            $result = empty($fieldValues) ? $token->getDefaultValue() : implode(', ', $fieldValues);

            $event->addToken($token->getToken(), $result);
        });
    }

    /**
     * @param CustomItemListDbalQueryEvent $event
     */
    public function onListQuery(CustomItemListDbalQueryEvent $event): void
    {
        $tableConfig       = $event->getTableConfig();
        $contactId         = $tableConfig->getParameter('filterEntityId');
        $segmentConditions = $tableConfig->getParameter('segmentConditions');
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $contactId && $segmentConditions && $segmentConditions instanceof ContactSegmentFilters) {
            $queryBuilder = $event->getQueryBuilder();

            /** @var ContactSegmentFilter $condition */
            foreach ($segmentConditions as $segmentId => $condition) {
                $queryAlias        = 'filter_'.$segmentId;
                $customFieldId     = (int) $condition->getField();
                $innerQueryBuilder = $this->queryFilterHelper->createValueQueryBuilder($queryBuilder->getConnection(), $queryAlias, $customFieldId, $condition->getType());
                $innerQueryBuilder->select($queryAlias.'_contact.custom_item_id');
                $this->queryFilterHelper->addCustomFieldValueExpressionFromSegmentFilter($innerQueryBuilder, $queryAlias, $condition);
                foreach ($innerQueryBuilder->getParameters() as $key => $value) {
                    $queryBuilder->setParameter($key, $value);
                }
                $queryBuilder->andWhere($innerQueryBuilder->expr()->exists($innerQueryBuilder->getSQL()));
            }
        }
    }
}
