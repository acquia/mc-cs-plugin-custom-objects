<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;

class CustomObjectTokenFormatter
{
    const DEFAULT_FORMAT = 'default';
    const OR_LIST_FORMAT = 'or-list';
    const AND_LIST_FORMAT = 'and-list';
    const BULLET_LIST_FORMAT = 'bullet-list';
    const ORDERED_LIST_FORMAT = 'ordered-list';

    /**
     * Key = Name of a format
     * Value = Function to perform the formatting
     */
    const FORMATS = array(
        self::DEFAULT_FORMAT => 'formatDefault',
        self::OR_LIST_FORMAT => 'formatOrList',
        self::AND_LIST_FORMAT => 'formatAndList',
        self::BULLET_LIST_FORMAT => 'formatBulletList',
        self::ORDERED_LIST_FORMAT => 'formatOrderedList'
    );

    public static function format(array $values, string $format): string
    {
        if (0 === count($values)) {
            return '';
        }

        if (!self::isValidFormat($format)) {
            throw new InvalidCustomObjectFormatListException($format);
        }

        $method = self::FORMATS[$format];

        return self::$method($values);
    }

    public static function isValidFormat(string $format): bool
    {
        if (!array_key_exists($format, self::FORMATS)) {
            return false;
        }

        if (!method_exists(self::class, self::FORMATS[$format])) {
            return false;
        }

        return true;
    }

    public static function formatDefault(array $values): string
    {
        if (0 === count($values)) {
            return '';
        }

        return implode(", ", $values);
    }

    public static function formatOrList(array $values): string
    {
        return self::conjunctionList($values, 'or');
    }

    public static function formatAndList(array $values): string
    {
        return self::conjunctionList($values, 'and');
    }

    public static function formatBulletList(array $values): string
    {
        return self::htmlList($values, "ul");
    }

    public static function formatOrderedList(array $values): string
    {
        return self::htmlList($values, "ol");
    }
    
    private static function conjunctionList(array $values, string $conjunction): string
    {
        if (0 === count($values)) {
            return '';
        }

        if (1 === count($values)) {
            return $values[0];
        }

        $lastItem = array_pop($values);

        return implode(", ", $values) . " $conjunction $lastItem";
    }

    private static function htmlList(array $values, string $tag): string
    {
        if (0 === count($values)) {
            return '';
        }

        $list = "<$tag>";
        foreach ($values as $item) {
            $list .= "<li>" . $item . "</li>";
        }
        $list .= "</$tag>";

        return $list;
    }
}
