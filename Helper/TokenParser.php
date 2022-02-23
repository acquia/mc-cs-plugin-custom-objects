<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\DTO\Token;

class TokenParser
{
    public const TOKEN = '{custom-object=(.*?)}';

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

                if ('format' === $keyword) {
                    $token->setFormat($value);
                }
            }

            $tokens->set($token->getToken(), $token);
        }

        return $tokens;
    }

    public function buildTokenWithDefaultOptions(string $customObjectAlias, string $customFieldAlias): string
    {
        return "{custom-object={$customObjectAlias}:{$customFieldAlias} | where=segment-filter | order=latest | limit=1 | default= | format=default}";
    }

    public function buildTokenLabel(string $customObjectName, string $customFieldLabel): string
    {
        return "{$customObjectName}: {$customFieldLabel}";
    }

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
        return array_map(
            function ($part) {
                return trim($part);
            },
            $array
        );
    }
}
