<?php

declare(strict_types=1);

?>
<div class="tab-pane fade bdr-w-0 custom-object-tab" id="<?php echo $tabId; ?>-container">
    <div class="row">
        <div class="col-md-3">
            <?php echo $view->render(
    'MauticCoreBundle:Helper:search.html.php',
    [
        'searchValue' => $searchValue,
        'action'      => $searchRoute,
        'searchId'    => $searchId,
        'target'      => '#'.$namespace,
        'searchHelp'  => '',
    ]
); ?>
        </div>
        <div class="col-md-9 text-right">
            <a href="<?php echo $linkRoute; ?>" class="btn btn-default" data-toggle="ajaxmodal" data-target="#customItemLookupModal" data-header="<?php echo $linkHeader; ?>">
                <span>
                    <i class="fa fa-link"></i>
                    <span class="hidden-xs hidden-sm">
                        <?php echo $view['translator']->trans('custom.item.link.existing'); ?>
                    </span>
                </span>
            </a>
            <a href="<?php echo $newRoute; ?>" class="btn btn-default" data-toggle="ajax">
                <span>
                    <i class="fa fa-plus"></i>
                    <span class="hidden-xs hidden-sm">
                        <?php echo $view['translator']->trans('custom.item.link.new'); ?>
                    </span>
                </span>
            </a>
        </div>
    </div>
    <div class='custom-item-list page-list mt-20' id="<?php echo $namespace; ?>">
        Loading...
    </div>
</div>
<script type="text/javascript">
    CustomObjects.reloadItemsTable(
        <?php echo $customObjectId; ?>,
        <?php echo $currentEntityId; ?>,
        '<?php echo $currentEntityType; ?>',
        '<?php echo $tabId; ?>'
    );
</script>