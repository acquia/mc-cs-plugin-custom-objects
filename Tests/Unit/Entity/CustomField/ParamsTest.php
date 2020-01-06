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

class ParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorAndToArray()
    {
        $placeholder = 'placeholder';

        $paramsArray = [
            'placeholder' => $placeholder,
        ];

        $params = new Params($paramsArray);

        $this->assertSame($placeholder, $params->getPlaceholder());
        $this->assertSame($paramsArray, $params->__toArray());
    }

    public function testToArrayRemovingFalseAndNullValues()
    {
        $placeholder = null;

        $paramsArray = [
            'placeholder' => $placeholder,
        ];

        $params = new Params($paramsArray);

        $this->assertSame(array_filter($paramsArray), $params->__toArray());
    }

    public function testGettersSetters()
    {
        $placeholder = 'placeholder';

        $params = new Params();

        $params->setPlaceholder($placeholder);
        $this->assertSame($placeholder, $params->getPlaceholder());
    }
}
