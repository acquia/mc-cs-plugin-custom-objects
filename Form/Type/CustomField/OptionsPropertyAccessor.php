<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class OptionsPropertyAccessor extends PropertyAccessor
{
    /**
     * @var array
     */
    private $customSets;
    /**
     * @var array
     */
    private $customGets;
    /**
     * @inheritDoc
     */
    public function __construct(
        array $propertySets = [],
        array $propertyGets = [],
        $magicCall = false,
        $throwExceptionOnInvalidIndex = false
    ) {
        parent::__construct($magicCall, $throwExceptionOnInvalidIndex);
        $this->customSets = $propertySets;
        $this->customGets = $propertyGets;
    }
    /**
     * @inheritDoc
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        if (isset($this->customGets[(string)$propertyPath])) {
            $propertyPath = new PropertyPath($this->customGets[(string)$propertyPath]);
        }
        return parent::getValue($objectOrArray, $propertyPath);
    }
    /**
     * @inheritDoc
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        if (isset($this->customSets[(string)$propertyPath])) {
            $propertyPath = new PropertyPath($this->customSets[(string)$propertyPath]);
        }
        parent::setValue($objectOrArray, $propertyPath, $value);
    }
}