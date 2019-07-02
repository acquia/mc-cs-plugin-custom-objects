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
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;
use MauticPlugin\CustomObjectsBundle\DTO\Token;
use Mautic\EmailBundle\Entity\Email;

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

        $tokens->map(function (Token $token) use ($event): void {
            try {
                $customObject = $this->customObjectModel->fetchEntityByAlias($token->getCustomObjectAlias());
            } catch (NotFoundException $e) {
                return;
            }

            $fieldValues = $this->getCustomFieldValues($customObject, $token, $event->getEmail(), (int) $event->getLead()['id']);
            $result      = empty($fieldValues) ? $token->getDefaultValue() : implode(', ', $fieldValues);

            $event->addToken($token->getToken(), $result);
        });
    }

    /**
     * Add some where conditions to the query requesting the right custom items for the token replacement.
     *
     * @param CustomItemListDbalQueryEvent $event
     */
    public function onListQuery(CustomItemListDbalQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        $contactId   = $tableConfig->getParameter('filterEntityId');
        $entityType  = $tableConfig->getParameter('filterEntityType');
        $token       = $tableConfig->getParameter('token');
        $email       = $tableConfig->getParameter('email');

        if ('contact' !== $entityType || !$contactId || !$email instanceof Email || !$token instanceof Token) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();

        if ('segment-filter' === $token->getWhere()) {
            $segmentConditions = [];

            if ('list' === $email->getEmailType()) {
                // Validation check that all segments have the same CO filters, so let's take the first one.
                /** @var LeadList $segment */
                $segment = clone $email->getLists()->first();
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

            /** @var ContactSegmentFilter $condition */
            foreach ($segmentConditions as $id => $condition) {
                $queryAlias        = 'filter_'.$id;
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

    /**
     * @param CustomObject $customObject
     * @param Token        $token
     * @param Email        $email
     * @param int          $contactId
     *
     * @return mixed[]
     */
    private function getCustomFieldValues(CustomObject $customObject, Token $token, Email $email, int $contactId): array
    {
        $orderBy  = CustomItem::TABLE_ALIAS.'.date_added';
        $orderDir = 'DESC';

        if ('latest' === $token->getOrder()) {
            // There is no other ordering option implemented at the moment.
            // Use the default order and direction.
        }

        $tableConfig = new TableConfig($token->getLimit(), 1, $orderBy, $orderDir);
        $tableConfig->addParameter('customObjectId', $customObject->getId());
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', (int) $contactId);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $email);
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

        return $fieldValues;
    }
}
