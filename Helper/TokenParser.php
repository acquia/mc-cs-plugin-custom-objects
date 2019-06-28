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

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\DTO\Token;

class TokenParser
{
    public const TOKEN = '{custom-object=(.*?)}';

    /**
     * @param string $content
     *
     * @return ArrayCollection
     */
    public function findTokens(string $content): ArrayCollection
    {
        $tokens = new ArrayCollection();

        preg_match_all('/'.self::TOKEN.'/', $content, $matches);

        if (empty($matches[1])) {
            return $tokens;
        }

        foreach ($matches[1] as $key => $tokenDataRaw) {
            $token = new Token($matches[0][$key]);
            $parts = $this->getPartsDividedByPipe($tokenDataRaw);

            try {
                $this->extractAliases($parts[0], $token);
            } catch (\LengthException $e) {
                // Invalid token, pretend like we did not see it.
                continue;
            }

            foreach ($parts as $part) {
                $options = $this->trimArrayElements(explode('=', $part));

                if (2 !== count($options)) {
                    continue;
                }

                $keyword = $options[0];
                $value   = $options[1];

                if ('limit' === $keyword) {
                    $token->setLimit((int) $value);
                }

                if ('order' === $keyword) {
                    $token->setOrder($value);
                }

                if ('where' === $keyword) {
                    $token->setWhere($value);
                }

                if ('default' === $keyword) {
                    $token->setDefaultValue($value);
                }
            }

            $tokens->set($token->getToken(), $token);
        }

        return $tokens;
    }

    /**
     * @param string $firstPart
     * @param Token  $token
     *
     * @return Token
     */
    private function extractAliases(string $firstPart, Token $token): Token
    {
        $aliases = $this->trimArrayElements(explode(':', $firstPart));

        if (2 !== count($aliases)) {
            throw new \LengthException("There must be custom object alias and custom field alias separated by colon. {$firstPart} provided.");
        }

        $token->setCustomObjectAlias($aliases[0]);
        $token->setCustomFieldAlias($aliases[1]);

        return $token;
    }

    /**
     * @param string $tokenDataRaw
     *
     * @return string[]
     */
    private function getPartsDividedByPipe(string $tokenDataRaw): array
    {
        return $this->trimArrayElements(explode('|', $tokenDataRaw));
    }

    /**
     * @param string[] $array
     *
     * @return string[]
     */
    private function trimArrayElements(array $array): array
    {
        return array_map(function ($part) {
            return trim($part);
        }, $array);
    }
}
