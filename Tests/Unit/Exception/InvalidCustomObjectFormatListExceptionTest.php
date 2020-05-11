<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Exception;

use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;

class InvalidCustomObjectFormatListExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $exception = new InvalidCustomObjectFormatListException('test');
        $this->assertSame(
            "'test' is not a valid custom object list format.",
            $exception->getMessage()
        );
    }
}
