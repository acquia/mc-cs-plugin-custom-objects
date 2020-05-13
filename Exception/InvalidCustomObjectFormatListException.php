<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Exception;

class InvalidCustomObjectFormatListException extends \Exception
{
    public function __construct(string $format)
    {
        $message = sprintf("'%s' is not a valid custom object list format.", $format);
        parent::__construct($message);
    }
}
