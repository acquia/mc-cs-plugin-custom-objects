<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('CustomObjectsBundle:CustomField:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="custom-fields-table">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#custom-fields-table',
                        'langVar'         => 'custom.field',
                        'routeBase'       => 'custom_field',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'custom.field',
                        'orderBy'    => 'e.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-custom_field-name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'custom.field',
                        'orderBy'    => 'e.id',
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
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', ['item' => $item,]);
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                [
                                    'item'  => $item,
                                    'model' => 'custom.field',
                                ]
                            ); ?>
                            <a href="<?php echo $view['router']->path('mautic_custom_field_view', ['objectId' => $item->getId()]); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        <?php if ($type = $item->getType()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $type; ?></small>
                            </div>
                        <?php endif; ?>
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
                'totalItems' => count($items),
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path('mautic_custom_field_list'),
                'sessionVar' => 'custom.field',
                'routeBase'  => 'mautic_custom_field_list',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'custom.field.noresults.tip']); ?>
<?php endif; ?>

