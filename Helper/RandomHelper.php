<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;

class RandomHelper
{
    /**
     * @var string[]
     */
    private $randomWords = [];

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
        return(md5(uniqid()));
    }

    public function getEmail(): string
    {
        return uniqid('', true) . '@' . uniqid('', true) . '.net';
    }

    /**
     * Loads the database of random words from a JSON file or cache if loaded already.
     *
     * @return string[]
     *
     * @throws \Exception
     */
    private function getRandomWords(): array
    {
        if (empty($this->randomWords)) {
            $path     = __DIR__.'/../Assets/json/mnemonic-words.json';
            $contents = \file_get_contents($path);

            if (false === $contents) {
                throw new \Exception("Could not fetch file contents from {$path}");
            }

            $this->randomWords = \json_decode($contents, true);
        }

        return $this->randomWords;
    }
}
