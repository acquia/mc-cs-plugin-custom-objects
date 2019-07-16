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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;

class ParamsTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorAndToArray()
    {
        $requiredValidationMessage = 'requiredValidationMessage';
        $placeholder               = 'placeholder';

        $paramsArray = [
            'requiredValidationMessage' => $requiredValidationMessage,
            'placeholder'               => $placeholder,
        ];

        $params = new Params($paramsArray);

        $this->assertSame($requiredValidationMessage, $params->getRequiredValidationMessage());
        $this->assertSame($placeholder, $params->getPlaceholder());

        $this->assertSame($paramsArray, $params->__toArray());
    }

    public function testToArrayRemovingFalseAndNullValues()
    {
        $requiredValidationMessage = 'requiredValidationMessage';
        $placeholder               = null;

        $paramsArray = [
            'requiredValidationMessage' => $requiredValidationMessage,
            'placeholder'               => $placeholder,
        ];

        $params = new Params($paramsArray);

        $this->assertSame(array_filter($paramsArray), $params->__toArray());
    }

    public function testGettersSetters()
    {
        $requiredValidationMessage  = 'requiredValidationMessage';
        $placeholder                = 'placeholder';

        $params = new Params();

        $params->setRequiredValidationMessage($requiredValidationMessage);
        $this->assertSame($requiredValidationMessage, $params->getRequiredValidationMessage());

        $params->setPlaceholder($placeholder);
        $this->assertSame($placeholder, $params->getPlaceholder());
    }
}
