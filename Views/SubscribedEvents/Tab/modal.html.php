<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['slots']->append('modal', $view->render('MauticCoreBundle:Helper:modal.html.php', [
    'id'     => 'customItemLookupModal',
    'size'   => 'xl',
]));
