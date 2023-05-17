<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadList;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\DTO\Token;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryBuilderManipulatorTrait;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter;
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles Custom Object token replacements with the correct value in emails.
 */
class TokenSubscriber implements EventSubscriberInterface
{
    use MatchFilterForLeadTrait;
    use QueryBuilderManipulatorTrait;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    /**
     * @var QueryFilterFactory
     */
    private $queryFilterFactory;

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
     * @var EventModel
     */
    private $eventModel;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TokenFormatter
     */
    private $tokenFormatter;

    public function __construct(
        ConfigProvider $configProvider,
        QueryFilterHelper $queryFilterHelper,
        QueryFilterFactory $queryFilterFactory,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        TokenParser $tokenParser,
        EventModel $eventModel,
        EventDispatcherInterface $eventDispatcher,
        TokenFormatter $tokenFormatter
    ) {
        $this->configProvider     = $configProvider;
        $this->queryFilterHelper  = $queryFilterHelper;
        $this->queryFilterFactory = $queryFilterFactory;
        $this->customObjectModel  = $customObjectModel;
        $this->customItemModel    = $customItemModel;
        $this->tokenParser        = $tokenParser;
        $this->eventModel         = $eventModel;
        $this->eventDispatcher    = $eventDispatcher;
        $this->tokenFormatter     = $tokenFormatter;
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
            $event->addToken(
                $this->tokenParser->buildTokenWithDefaultOptions($customObject->getAlias(), 'name'),
                $this->tokenParser->buildTokenLabel($customObject->getName(), 'Name')
            );
            /** @var CustomField $customField */
            foreach ($customObject->getCustomFields() as $customField) {
                $event->addToken(
                    $this->tokenParser->buildTokenWithDefaultOptions($customObject->getAlias(), $customField->getAlias()),
                    $this->tokenParser->buildTokenLabel($customObject->getName(), $customField->getLabel())
                );
            }
        }
    }

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
                $fieldValues = $this->getCustomFieldValues($customObject, $token, $event);
            } catch (NotFoundException $e) {
                $fieldValues = null;
            }

            if (empty($fieldValues)) {
                $result = $token->getDefaultValue();
            } else {
                if (!empty($token->getFormat())) {
                    try {
                        $formatEvent = new CustomObjectListFormatEvent($fieldValues, $token->getFormat());

                        $this->eventDispatcher->dispatch(
                            $formatEvent,
                            CustomObjectEvents::ON_CUSTOM_OBJECT_LIST_FORMAT
                        );

                        $result = $formatEvent->hasBeenFormatted() ?
                            $formatEvent->getFormattedString() :
                            $this->tokenFormatter->format($fieldValues, TokenFormatter::DEFAULT_FORMAT);
                    } catch (InvalidCustomObjectFormatListException $e) {
                        $result = $this->tokenFormatter->format($fieldValues, TokenFormatter::DEFAULT_FORMAT);
                    }
                } else {
                    $result = $this->tokenFormatter->format($fieldValues, TokenFormatter::DEFAULT_FORMAT);
                }
            }

            $event->addToken($token->getToken(), $result);
        });
    }

    /**
     * Add some where conditions to the query requesting the right custom items for the token replacement.
     *
     * @throws InvalidArgumentException
     */
    public function onListQuery(CustomItemListDbalQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        $contactId   = $tableConfig->getParameter('filterEntityId');
        $entityType  = $tableConfig->getParameter('filterEntityType');
        $token       = $tableConfig->getParameter('token');
        $email       = $tableConfig->getParameter('email');
        $source      = $tableConfig->getParameter('source');

        if ('contact' !== $entityType || !$contactId || !$email instanceof Email || !$token instanceof Token) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();

        if ('segment-filter' === $token->getWhere()) {
            $segmentFilters = [];

            if ('list' === $email->getEmailType()) {
                $segmentFilters = $email->getLists()->first()->getFilters();
            } elseif ('template' && isset($source[0]) && 'campaign.event' === $source[0] && !empty($source[1])) {
                $campaignEventId = (int) $source[1];

                /** @var Event $campaignEvent */
                $campaignEvent = $this->eventModel->getEntity($campaignEventId);

                if (!$campaignEvent) {
                    return;
                }

                /** @var LeadList $segment */
                $segment = $campaignEvent->getCampaign()->getLists()->first();

                if (!$segment) {
                    return;
                }

                $segmentFilters = $segment->getFilters();
            }

            foreach ($segmentFilters as $id => $filter) {
                try {
                    $queryAlias        = 'filter_'.$id;
                    $innerQueryBuilder = $this->queryFilterFactory->configureQueryBuilderFromSegmentFilter($filter, $queryAlias);
                } catch (InvalidSegmentFilterException $e) {
                    continue;
                }

                foreach ($innerQueryBuilder as $segmentQueryBuilder) {
                    $segmentQueryBuilder->select($queryAlias.'_value.custom_item_id');
                    $this->queryFilterHelper->addContactIdRestriction($segmentQueryBuilder, $queryAlias, $contactId);
                    $segmentQueryBuilder->andWhere("{$queryAlias}_contact.custom_item_id = {$queryAlias}_value.custom_item_id");
                }

                $queryBuilder->innerJoin(
                    CustomItem::TABLE_ALIAS,
                    "({$innerQueryBuilder->getSQL()})",
                    $queryAlias,
                    CustomItem::TABLE_ALIAS.".id = {$queryAlias}.custom_item_id"
                );

                $this->copyParams($innerQueryBuilder, $queryBuilder);
            }
        }
    }

    /**
     * This method searches for the right custom items and the right custom field values.
     * The custom field filters are actually added in the method `onListQuery` above.
     *
     * @return mixed[]
     */
    private function getCustomFieldValues(CustomObject $customObject, Token $token, EmailSendEvent $event): array
    {
        $orderBy  = CustomItem::TABLE_ALIAS.'.id';
        $orderDir = 'DESC';

        if ('latest' === $token->getOrder()) {
            // There is no other ordering option implemented at the moment.
            // Use the default order and direction.
        }

        $tableConfig = new TableConfig($token->getLimit(), 1, $orderBy, $orderDir);
        $tableConfig->addParameter('customObjectId', $customObject->getId());
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', (int) $event->getLead()['id']);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $event->getEmail());
        $tableConfig->addParameter('source', $event->getSource());
        $customItems = $this->customItemModel->getArrayTableData($tableConfig);
        $fieldValues = [];

        foreach ($customItems as $customItemData) {
            // Name is known from the CI data array.
            if ('name' === $token->getCustomFieldAlias()) {
                $fieldValues[] = $customItemData['name'];

                continue;
            }

            // Custom Field values are handled like this.
            $customItem = new CustomItem($customObject);
            $customItem->populateFromArray($customItemData);
            $customItem = $this->customItemModel->populateCustomFields($customItem);

            try {
                $fieldValue    = $customItem->findCustomFieldValueForFieldAlias($token->getCustomFieldAlias());
                // If the CO item doesn't have a value, get the default value
                if ('' == $fieldValue->getValue()) {
                    $fieldValue->setValue($fieldValue->getCustomField()->getDefaultValue());
                }
                $fieldValues[] = $fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue);
            } catch (NotFoundException $e) {
                // Custom field not found.
            }
        }

        return $fieldValues;
    }
}
