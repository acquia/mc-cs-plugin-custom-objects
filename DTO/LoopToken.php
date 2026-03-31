<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

/**
 * Object that represents parsed token like this one:.
 *
 * {custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=Nothing to see here | format=or-list}
 */
final class LoopToken
{
    private string $token;

    private int $limit = 1;

    private string $where = '';

    private string $order = 'latest';

    private string $customObjectAlias = '';

    /**
     * @var array<string, array<string, string>>
     */
    private array $loopContentTokens = [];

    private string $loopContent = '';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getLoopContent(): string
    {
        return $this->loopContent;
    }

    public function setLoopContent(string $loopContent): void
    {
        $this->loopContent = $loopContent;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getLoopContentTokens(): array
    {
        return $this->loopContentTokens;
    }

    /**
     * @param array<string, string> $contentTokenParams
     */
    public function addLoopContentToken(string $loopContentToken, array $contentTokenParams): void
    {
        $this->loopContentTokens[$loopContentToken] = $contentTokenParams;
    }

    /**
     * @param array<string, array<string, string>> $loopContentTokens
     */
    public function setLoopContentTokens(array $loopContentTokens): void
    {
        $this->loopContentTokens = $loopContentTokens;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    public function getWhere(): string
    {
        return $this->where;
    }

    public function setWhere(string $where): void
    {
        $this->where = $where;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCustomObjectAlias(): string
    {
        return $this->customObjectAlias;
    }

    public function setCustomObjectAlias(string $customObjectAlias): void
    {
        $this->customObjectAlias = $customObjectAlias;
    }
}
