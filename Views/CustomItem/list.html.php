<?php

/*
* @copyright   2018 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;

if ($tmpl == 'index') {
    $view->extend('CustomObjectsBundle:CustomItem:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="custom-items-table">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'  => 'true',
                        'target'    => '#custom-items-table',
                        'langVar'   => 'custom.item',
                        'routeBase' => 'custom_item',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'custom.item',
                        'orderBy'    => CustomItemRepository::getAlias().'.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-custom_item_name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'custom.item',
                        'orderBy'    => CustomItemRepository::getAlias().'.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-asset-id',
                        'default'    => true,
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $k => $item): ?>
                <tr>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', ['item' => $item]); ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                [
                                    'item'  => $item,
                                    'model' => 'custom.item',
                                ]
                            ); ?>
                            <a href="<?php echo $view['router']->path(CustomItemRouteProvider::ROUTE_VIEW, ['objectId' => $item->getCustomObject()->getId(), 'itemId' => $item->getId()]); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                    </td>
                    <td><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => $itemCount,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path(CustomItemRouteProvider::ROUTE_LIST),
                'sessionVar' => 'custom.item',
                'routeBase'  => CustomItemRouteProvider::ROUTE_LIST,
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'custom.object.noresults.tip']); ?>
<?php endif; ?>

