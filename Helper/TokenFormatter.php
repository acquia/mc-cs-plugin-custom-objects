<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;

class TokenFormatter
{
    public const DEFAULT_FORMAT      = 'default';
    public const OR_LIST_FORMAT      = 'or-list';
    public const AND_LIST_FORMAT     = 'and-list';
    public const BULLET_LIST_FORMAT  = 'bullet-list';
    public const ORDERED_LIST_FORMAT = 'ordered-list';

    /**
     * Key = Name of a format
     * Value = Function to perform the formatting.
     */
    public const FORMATS = [
        self::DEFAULT_FORMAT      => 'formatDefault',
        self::OR_LIST_FORMAT      => 'formatOrList',
        self::AND_LIST_FORMAT     => 'formatAndList',
        self::BULLET_LIST_FORMAT  => 'formatBulletList',
        self::ORDERED_LIST_FORMAT => 'formatOrderedList',
    ];

    /**
     * @throws InvalidCustomObjectFormatListException
     */
    public function format(array $values, string $format): string
    {
        if (0 === count($values)) {
            return '';
        }

        if (!$this->isValidFormat($format)) {
            throw new InvalidCustomObjectFormatListException($format);
        }

        $method = self::FORMATS[$format];

        asort($values);

        return $this->$method(
            $this->removeEmptyValues(
                array_unique($values)
            )
        );
    }

    public function isValidFormat(string $format): bool
    {
        if (!array_key_exists($format, self::FORMATS)) {
            return false;
        }

        if (!method_exists(self::class, self::FORMATS[$format])) {
            return false;
        }

        return true;
    }

    private function formatDefault(array $values): string
    {
        if (0 === count($values)) {
            return '';
        }

        return implode(', ', $values);
    }

    private function formatOrList(array $values): string
    {
        return $this->conjunctionList($values, 'or');
    }

    private function formatAndList(array $values): string
    {
        return $this->conjunctionList($values, 'and');
    }

    private function formatBulletList(array $values): string
    {
        return $this->htmlList($values, 'ul');
    }

    private function formatOrderedList(array $values): string
    {
        return $this->htmlList($values, 'ol');
    }

    private function conjunctionList(array $values, string $conjunction): string
    {
        if (0 === count($values)) {
            return '';
        }

        if (1 === count($values)) {
            return $values[0];
        }

        $lastItem = array_pop($values);

        return implode(', ', $values)." $conjunction $lastItem";
    }

    private function htmlList(array $values, string $tag): string
    {
        if (0 === count($values)) {
            return '';
        }

        $list = "<$tag>";
        foreach ($values as $item) {
            $list .= '<li>'.$item.'</li>';
        }
        $list .= "</$tag>";

        return $list;
    }

    private function removeEmptyValues(array $values): array
    {
        return array_filter(
            $values,
            function ($value) {
                return '' !== trim($value);
            }
        );
    }
}
