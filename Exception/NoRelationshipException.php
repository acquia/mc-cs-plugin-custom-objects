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

use Exception;
use Throwable;

class NoRelationshipException extends Exception
{
    public function __construct(
        int $code = 403,
        ?Throwable $throwable = null
    ) {
        parent::__construct(
            "This custom object does not have relationship fields defined.",
            $code,
            $throwable
        );
    }
}