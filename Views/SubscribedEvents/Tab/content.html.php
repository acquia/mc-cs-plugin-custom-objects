<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$listId = 'custom-item-list-'.$customObjectId;
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
        'target'      => '#'.$listId,
        'searchHelp'  => '',
    ]
); ?>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <div class="form-control-icon pa-xs">
                    <span class="the-icon fa fa-link text-muted mt-xs"></span>
                    <input 
                        type="text"
                        data-toggle='typeahead' 
                        class="form-control bdr-w-0"
                        placeholder="<?php echo $placeholder; ?>" 
                        data-action="<?php echo $lookupRoute; ?>">
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="<?php echo $newRoute; ?>" class="btn btn-default pull-right" data-toggle="ajax">
                <span>
                    <i class="fa fa-plus"></i>
                    <span class="hidden-xs hidden-sm">
                        <?php echo $view['translator']->trans('mautic.core.form.new'); ?>
                    </span>
                </span>
            </a>
        </div>
    </div>
    <div class='custom-item-list page-list' id="<?php echo $listId; ?>">
        Loading...
    </div>
</div>
<script type="text/javascript">
    CustomObjects.initTabShowingLinkedItems(
        <?php echo $customObjectId; ?>,
        <?php echo $currentEntityId; ?>,
        '<?php echo $currentEntityType; ?>',
        '<?php echo $tabId; ?>',
        <?php echo isset($relationshipObjectId) ? $relationshipObjectId : 'null'; ?>

    );
</script>