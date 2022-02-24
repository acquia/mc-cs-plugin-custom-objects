<?php declare(strict_types=1);

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

if ('index' === $tmpl) {
    $view->extend('CustomObjectsBundle:CustomObject:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="custom-objects-table">
            <thead>
            <tr>
                <?php
                echo $view->render(
    'MauticCoreBundle:Helper:tableheader.html.php',
    [
                        'checkall'  => 'true',
                        'target'    => '#custom-objects-table',
                        'langVar'   => 'custom.object',
                        'routeBase' => 'custom_object',
                    ]
);

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => $sessionVar,
                        'orderBy'    => CustomObject::TABLE_ALIAS.'.namePlural',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-custom_object_-name',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => $sessionVar,
                        'orderBy'    => CustomObject::TABLE_ALIAS.'.id',
                        'text'       => 'mautic.core.id',
                        'default'    => true,
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
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
                                    'model' => 'custom.object',
                                ]
                ); ?>
                            <a href="<?php echo $view['router']->path(CustomObjectRouteProvider::ROUTE_VIEW, ['objectId' => $item->getId()]); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        <?php if ($item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $item->getDescription(); ?></small>
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
                'totalItems' => $count,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path(CustomObjectRouteProvider::ROUTE_LIST),
                'sessionVar' => $sessionVar,
                'routeBase'  => CustomObjectRouteProvider::ROUTE_LIST,
            ]
                ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'custom.object.noresults.tip']); ?>
<?php endif; ?>
