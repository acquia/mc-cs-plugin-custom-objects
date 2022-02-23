<?php

declare(strict_types=1);

$view['slots']->append('modal', $view->render('MauticCoreBundle:Helper:modal.html.php', [
    'id'     => 'customItemLookupModal',
    'size'   => 'xl',
]));
