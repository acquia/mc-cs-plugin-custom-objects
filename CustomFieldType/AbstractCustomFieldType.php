<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\Translation\TranslatorInterface;


abstract class AbstractCustomFieldType implements CustomFieldTypeInterface
{
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getOperators(): array
    {
        return OperatorOptions::getFilterExpressionFunctions();
    }

    /**
     * @param TranslatorInterface $translator
     * 
     * @return array
     */
    public function getOperatorOptions(TranslatorInterface $translator): array
    {
        $operators = $this->getOperators();
        $options   = [];

        foreach ($operators as $key => $operator) {
            $options[$key] = $translator->trans($operator['label']);
        }

        return $options;
    }
}