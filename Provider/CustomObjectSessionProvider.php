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

namespace MauticPlugin\CustomObjectsBundle\Provider;

class CustomObjectSessionProvider extends StandardSessionProvider
{
    public const KEY_PAGE = 'custom.object.page';

    public const KEY_LIMIT = 'mautic.custom.object.limit';

    public const KEY_ORDER_BY = 'mautic.custom.object.orderby';

    public const KEY_ORDER_BY_DIR = 'mautic.custom.object.orderbydir';

    public const KEY_FILTER = 'mautic.custom.object.filter';
}
