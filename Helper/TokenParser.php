<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\DTO\LoopToken;
use MauticPlugin\CustomObjectsBundle\DTO\Token;

class TokenParser
{
    public const TOKEN = '{custom-object=(.*?)}';

    public const TOKEN_CUSTOM_OBJECT_LOOP       = '{custom-object-loop\s+([^}]*)\}([\s\S]*?)\{\/custom-object-loop\}';
    public const TOKEN_CUSTOM_OBJECT_LOOP_VALUE = '{custom-object-loop-value\s+([^}]*)\}';

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

    public function findCustomObjectLoopTokens(string $content): ArrayCollection
    {
        $tokens = new ArrayCollection();

        preg_match_all('/'.self::TOKEN_CUSTOM_OBJECT_LOOP.'/', $content, $matches);

        if (empty($matches[1])) {
            return $tokens;
        }

        foreach ($matches[1] as $key => $loopParams) {
            $loopToken = new LoopToken($matches[0][$key]);
            $rawParams = $this->getPartsDividedByPipe($loopParams);
            foreach ($rawParams as $rawParam) {
                $options = $this->trimArrayElements(explode('=', $rawParam));
                $keyword = $options[0];
                $value   = $options[1];

                if ('object' === $keyword) {
                    $loopToken->setCustomObjectAlias($value);
                }

                if ('where' === $keyword) {
                    $loopToken->setWhere($value);
                }

                if ('order' === $keyword) {
                    $loopToken->setOrder($value);
                }

                if ('limit' === $keyword) {
                    $loopToken->setLimit((int) $value);
                }
            }

            $loopContent = $matches[2][$key] ?? '';
            $loopToken->setLoopContent($loopContent);
            preg_match_all('/'.self::TOKEN_CUSTOM_OBJECT_LOOP_VALUE.'/', $loopContent, $fieldMatches);

            foreach ($fieldMatches[1] as $key => $fieldMatch) {
                $contentToken       = $fieldMatches[0][$key];
                $rawFieldParams     = $this->getPartsDividedByPipe($fieldMatch);
                $contentTokenParams = [];
                foreach ($rawFieldParams as $rawFieldParam) {
                    $options = $this->trimArrayElements(explode('=', $rawFieldParam));
                    $keyword = $options[0];
                    $value   = $options[1];

                    if ('object' === $keyword && $loopToken->getCustomObjectAlias() !== $value) {
                        continue;
                    }

                    if ('field' === $keyword) {
                        $contentTokenParams['field'] = $value;
                    }

                    if ('default' === $keyword) {
                        $contentTokenParams['default'] = $value;
                    }
                }

                $loopToken->addLoopContentToken($contentToken, $contentTokenParams);
            }

            $tokens->set($loopToken->getToken(), $loopToken);
        }

        return $tokens;
    }

    public function buildTokenWithDefaultOptions(string $customObjectAlias, string $customFieldAlias): string
    {
        return "{custom-object={$customObjectAlias}:{$customFieldAlias} | where=segment-filter | order=latest | limit=1 | default= | format=default}";
    }

    public function buildTokenCustomObjectLoop(string $customObjectAlias): string
    {
        return "{custom-object-loop object={$customObjectAlias} | where=segment-filter | order=latest | limit=1}\nThe content added here will be repeated for each item in the custom object.\n{/custom-object-loop}";
    }

    public function buildTokenCustomObjectFieldInLoop(string $customObjectAlias, string $customFieldAlias): string
    {
        return "{custom-object-loop-value object={$customObjectAlias} | field={$customFieldAlias} | default=}";
    }

    public function buildTokenCustomObjectFieldInLoopLabel(string $customObjectLabel, string $customFieldLabel): string
    {
        return "{$customObjectLabel}: {$customFieldLabel} in Loop";
    }

    public function buildTokenCustomObjectLoopLabel(string $customObjectLabel): string
    {
        return "{$customObjectLabel}: For Loop";
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
