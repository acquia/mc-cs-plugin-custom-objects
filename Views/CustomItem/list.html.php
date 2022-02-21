<?php declare(strict_types=1);

use MauticPlugin\CustomObjectsBundle\DTO\CustomItemFieldListData;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;

/** @var CustomItemFieldListData|null $fieldData */
$fieldData = $fieldData ?? null;

if ('index' === $tmpl) {
    $view->extend('CustomObjectsBundle:CustomItem:index.html.php');
}

$target    = '#'.$namespace;
$routeSelf = $view['router']->path(
    CustomItemRouteProvider::ROUTE_LIST,
    [
        'objectId'         => $customObject->getId(),
        'filterEntityId'   => $filterEntityId,
        'filterEntityType' => $filterEntityType,
        'lookup'           => $lookup,
        'tmpl'             => 'list',
    ]
);
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered" id="custom-items-<?php echo $customObject->getId(); ?>-table">
            <thead>
            <tr>
                <?php
                echo $view->render(
    'MauticCoreBundle:Helper:tableheader.html.php',
    [
                        'checkall'  => 'true',
                        'target'    => $target,
                        'langVar'   => 'custom.item',
                        'routeBase' => 'custom_item',
                        'baseUrl'   => $routeSelf,
                    ]
);

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => $sessionVar,
                        'orderBy'    => CustomItem::TABLE_ALIAS.'.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-custom_item_name',
                        'baseUrl'    => $routeSelf,
                        'target'     => $target,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => $sessionVar,
                        'orderBy'    => CustomItem::TABLE_ALIAS.'.id',
                        'text'       => 'mautic.core.id',
                        'default'    => true,
                        'baseUrl'    => $routeSelf,
                        'target'     => $target,
                    ]
                );
                ?>
                <?php if ($fieldData): ?>
                    <?php foreach ($fieldData->getColumnLabels() as $columnLabel): ?>
                        <?php echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', ['text' => $columnLabel]); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                            <?php echo empty($filterEntityId) ? $view->render(
                    'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                    [
                                    'item'  => $item,
                                    'model' => 'custom.item',
                                ]
                ) : ''; ?>
                            <a href="<?php echo $view['router']->path(CustomItemRouteProvider::ROUTE_VIEW, ['objectId' => $customObject->getId(), 'itemId' => $item->getId()]); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                    </td>
                    <td><?php echo $item->getId(); ?></td>
                    <?php if ($fieldData): ?>
                        <?php foreach ($fieldData->getFields($item->getId()) as $fieldValue): ?>
                            <td>
                                <?php echo $view->render('CustomObjectsBundle:CustomField:value.html.php', ['fieldValue'  => $fieldValue]); ?>
                            </td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
                    'MauticCoreBundle:Helper:pagination.html.php',
                    [
                'totalItems'  => $itemCount,
                'page'        => $page,
                'limit'       => $limit,
                'baseUrl'     => $routeSelf = $view['router']->path(CustomItemRouteProvider::ROUTE_LIST, ['objectId'  => $customObject->getId()]),
                'queryString' => "&filterEntityId={$filterEntityId}&filterEntityType={$filterEntityType}&lookup={$lookup}",
                'sessionVar'  => $sessionVar,
                'routeBase'   => CustomItemRouteProvider::ROUTE_LIST,
                'target'      => $target,
            ]
                ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => $lookup ? 'custom.item.link.noresults.tip' : 'custom.object.noresults.tip']); ?>
<?php endif; ?>
