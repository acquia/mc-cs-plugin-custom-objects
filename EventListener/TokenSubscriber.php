<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Mautic\LeadBundle\Segment\OperatorOptions;
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
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
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

    public function __construct(
        private ConfigProvider $configProvider,
        private QueryFilterHelper $queryFilterHelper,
        private QueryFilterFactory $queryFilterFactory,
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel,
        private CustomFieldModel $customFieldModel,
        private TokenParser $tokenParser,
        private EventModel $eventModel,
        private EventDispatcherInterface $eventDispatcher,
        private TokenFormatter $tokenFormatter,
        private int $leadCustomItemFetchLimit
    ) {
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
            EmailEvents::TOKEN_REPLACEMENT                   => ['onTokenReplacement', 100],
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
                $fieldValues  = $this->getCustomFieldValues($customObject, $token, $event);
            } catch (NotFoundException) {
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
                    } catch (InvalidCustomObjectFormatListException) {
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

                /** @var ?Event $campaignEvent */
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
                } catch (InvalidSegmentFilterException) {
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
                if (empty($fieldValue->getValue())) {
                    $fieldValue->setValue($fieldValue->getCustomField()->getDefaultValue());
                }
                $fieldValues[] = $fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue);
            } catch (NotFoundException) {
                // Custom field not found.
            }
        }

        return $fieldValues;
    }

    public function onTokenReplacement(TokenReplacementEvent $event): void
    {
        $clickthrough = $event->getClickthrough();

        if (!array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokenData = $clickthrough['dynamicContent'];

        foreach ($tokenData as $data) {
            $filterContent = $data['content'];

            $isCustomObject = false;
            foreach ($data['filters'] as $filter) {
                $customFieldValues = $this->getCustomFieldDataForLead($filter['filters'], (string) $lead['id']);

                if (!empty($customFieldValues)) {
                    $isCustomObject = true;
                    $lead           = array_merge($lead, $customFieldValues);
                }

                if ($isCustomObject && $this->matchFilterForLeadInCustomObject($filter['filters'], $lead)) {
                    $filterContent = $filter['content'];
                    break;
                }
            }

            if ($isCustomObject) {
                $event->addToken('{dynamiccontent="'.$data['tokenName'].'"}', $filterContent);
            }
        }
    }

    /**
     * @param array<mixed> $filters
     *
     * @return array<mixed>
     */
    private function getCustomFieldDataForLead(array $filters, string $leadId): array
    {
        $customFieldValues = $cachedCustomItems = [];

        foreach ($filters as $condition) {
            try {
                if ('custom_object' !== $condition['object']) {
                    continue;
                }

                if (str_starts_with($condition['field'], 'cmf_')) {
                    $customField  = $this->customFieldModel->fetchEntity(
                        (int) explode('cmf_', $condition['field'])[1]
                    );
                    $customObject = $customField->getCustomObject();
                    $fieldAlias   = $customField->getAlias();
                } elseif (str_starts_with($condition['field'], 'cmo_')) {
                    $customObject = $this->customObjectModel->fetchEntity(
                        (int) explode('cmo_', $condition['field'])[1]
                    );
                    $fieldAlias   = 'name';
                } else {
                    continue;
                }

                $key = $customObject->getId().'-'.$leadId;
                if (!isset($cachedCustomItems[$key])) {
                    $cachedCustomItems[$key] = $this->getCustomItems($customObject, $leadId);
                }

                $result = $this->getCustomFieldValue($customObject, $fieldAlias, $cachedCustomItems[$key]);

                $customFieldValues[$condition['field']] = $result;
            } catch (NotFoundException|InvalidCustomObjectFormatListException) {
                continue;
            }
        }

        return $customFieldValues;
    }

    /**
     * @param array<mixed> $customItems
     *
     * @return array<mixed>
     */
    private function getCustomFieldValue(
        CustomObject $customObject,
        string $customFieldAlias,
        array $customItems
    ): array {
        $fieldValues = [];

        foreach ($customItems as $customItemData) {
            // Name is known from the CI data array.
            if ('name' === $customFieldAlias) {
                $fieldValues[] = $customItemData['name'];

                continue;
            }

            // Custom Field values are handled like this.
            $customItem = new CustomItem($customObject);
            $customItem->populateFromArray($customItemData);
            $customItem = $this->customItemModel->populateCustomFields($customItem);

            try {
                $fieldValue = $customItem->findCustomFieldValueForFieldAlias($customFieldAlias);
                // If the CO item doesn't have a value, get the default value
                if (empty($fieldValue->getValue())) {
                    $fieldValue->setValue($fieldValue->getCustomField()->getDefaultValue());
                }

                if (in_array($fieldValue->getCustomField()->getType(), ['multiselect', 'select'])) {
                    $fieldValues[] = $fieldValue->getValue();
                } else {
                    $fieldValues[] = $fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue);
                }
            } catch (NotFoundException) {
                // Custom field not found.
            }
        }

        return $fieldValues;
    }

    /**
     * @return array<mixed>
     */
    private function getCustomItems(CustomObject $customObject, string $leadId): array
    {
        $orderBy  = CustomItem::TABLE_ALIAS.'.id';
        $orderDir = 'DESC';

        $tableConfig = new TableConfig($this->leadCustomItemFetchLimit, 1, $orderBy, $orderDir);
        $tableConfig->addParameter('customObjectId', $customObject->getId());
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', $leadId);

        return $this->customItemModel->getArrayTableData($tableConfig);
    }

    // We have a similar function in MatchFilterForLeadTrait since we are unable to alter anything in Mautic 4.4,
    // hence there is some duplication of code.

    /**
     * @param array<mixed> $filter
     * @param array<mixed> $lead
     *
     * @throws OperatorsNotFoundException
     */
    protected function matchFilterForLeadInCustomObject(array $filter, array $lead): bool
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }

        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
            if (!array_key_exists($data['field'], $lead)) {
                continue;
            }

            /*
             * Split the filters into groups based on the glue.
             * The first filter and any filters whose glue is
             * "or" will start a new group.
             */
            if (0 === $groupNum || 'or' === $data['glue']) {
                ++$groupNum;
                $groups[$groupNum] = null;
            }

            /*
             * If the group has been marked as false, there
             * is no need to continue checking the others
             * in the group.
             */
            if (false === $groups[$groupNum]) {
                continue;
            }

            /*
             * If we are checking the first filter in a group
             * assume that the group will not match.
             */
            if (null === $groups[$groupNum]) {
                $groups[$groupNum] = false;
            }

            $leadValues   = $lead[$data['field']];
            $leadValues   = 'custom_object' === $data['object'] ? $leadValues : [$leadValues];
            $filterVal    = $data['filter'];
            $subgroup     = null;

            if (is_array($leadValues)) {
                foreach ($leadValues as $leadVal) {
                    if ($subgroup) {
                        break;
                    }

                    switch ($data['type']) {
                        case 'boolean':
                            if (null !== $leadVal) {
                                $leadVal = (bool) $leadVal;
                            }

                            if (null !== $filterVal) {
                                $filterVal = (bool) $filterVal;
                            }
                            break;
                        case 'datetime':
                        case 'time':
                            if (!is_null($leadVal) && !is_null($filterVal)) {
                                $leadValCount   = substr_count($leadVal, ':');
                                $filterValCount = substr_count($filterVal, ':');

                                if (2 === $leadValCount && 1 === $filterValCount) {
                                    $filterVal .= ':00';
                                }
                            }
                            break;
                        case 'tags':
                        case 'select':
                        case 'multiselect':
                            if (!is_array($leadVal) && !empty($leadVal)) {
                                $leadVal = explode('|', $leadVal);
                            }
                            if (!is_null($filterVal) && !is_array($filterVal)) {
                                $filterVal = explode('|', $filterVal);
                            }
                            break;
                        case 'number':
                            $leadVal   = (int) $leadVal;
                            $filterVal = (int) $filterVal;
                            break;
                    }

                    switch ($data['operator']) {
                        case '=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal === $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal == $filterVal;
                            }
                            break;
                        case '!=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal !== $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal != $filterVal;
                            }
                            break;
                        case 'gt':
                            $groups[$groupNum] = $leadVal > $filterVal;
                            break;
                        case 'gte':
                            $groups[$groupNum] = $leadVal >= $filterVal;
                            break;
                        case 'lt':
                            $groups[$groupNum] = $leadVal < $filterVal;
                            break;
                        case 'lte':
                            $groups[$groupNum] = $leadVal <= $filterVal;
                            break;
                        case 'empty':
                            $groups[$groupNum] = empty($leadVal);
                            break;
                        case '!empty':
                            $groups[$groupNum] = !empty($leadVal);
                            break;
                        case 'like':
                            $matchVal          = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);
                            $groups[$groupNum] = 1 === preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case '!like':
                            $matchVal          = str_replace(['.', '*'], ['\.', '\*'], $filterVal);
                            $matchVal          = str_replace('%', '.*', $matchVal);
                            $groups[$groupNum] = 1 !== preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case OperatorOptions::IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, false);
                            break;
                        case OperatorOptions::NOT_IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, true);
                            break;
                        case 'regexp':
                            $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case '!regexp':
                            $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case 'startsWith':
                            $groups[$groupNum] = str_starts_with($leadVal, $filterVal);
                            break;
                        case 'endsWith':
                            $endOfString       = substr($leadVal, strlen($leadVal) - strlen($filterVal));
                            $groups[$groupNum] = 0 === strcmp($endOfString, $filterVal);
                            break;
                        case 'contains':
                            $groups[$groupNum] = str_contains((string) $leadVal, (string) $filterVal);
                            break;
                        default:
                            throw new OperatorsNotFoundException('Operator is not defined or invalid operator found.');
                    }

                    $subgroup = $groups[$groupNum];
                }
            }
        }

        return in_array(true, $groups);
    }
}
