<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

class RandomHelper
{
    public function getSentence(int $limit): string
    {
        $words = [];

        for ($i = 1; $i <= $limit; ++$i) {
            $words[] = $this->getWord();
        }

        return ucfirst(implode(' ', $words));
    }

    public function getString(int $limit): string
    {
        $string = '';

        while (strlen($string) <= $limit) {
            $string .= $this->getWord();
        }

        return substr($string, 0, $limit);
    }

    public function getWord(): string
    {
        return md5(uniqid());
    }

    public function getEmail(): string
    {
        return uniqid('', true).'@'.uniqid('', true).'.net';
    }
}
