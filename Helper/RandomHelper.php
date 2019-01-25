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
     * @var array
     */
    private $randomWords = [];

    /**
     * @param integer $limit
     * 
     * @return string
     */
    public function getSentence(int $limit): string
    {
        $words = [];

        for ($i = 1; $i <= $limit; $i++) {
            $words[] = $this->getWord();
        }

        return ucfirst(implode(' ', $words));
    }

    /**
     * @return string
     */
    public function getWord(): string
    {
        $randomWords = $this->getRandomWords();
        $randomKey   = array_rand($randomWords);
        
        return $randomWords[$randomKey];
    }

    /**
     * Loads the database of random words from a JSON file or cache if loaded already.
     *
     * @return array
     */
    private function getRandomWords(): array
    {
        if (empty($this->randomWords)) {
            $path              = __DIR__.'/../Assets/json/mnemonic-words.json';
            $string            = \file_get_contents($path);
            $this->randomWords = \json_decode($string, true);
        }

        return $this->randomWords;
    }
}