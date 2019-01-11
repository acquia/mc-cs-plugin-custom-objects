<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="tab-pane fade bdr-w-0" id="<?php echo $key; ?>-container">
    <div class="box-layout mb-lg">
        <div class="col-xs-10 va-m">
            <form action="<?php echo $view['router']->path('mautic_contactnote_index', ['page' => $page, 'leadId' => $lead->getId(), 'tmpl' => 'list']); ?>" class="panel" id="note-filters" method="post">
                <div class="form-control-icon pa-xs">
                    <input type="text" name="search" value="<?php echo $view->escape($search); ?>" id="NoteFilter" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" data-toggle="livesearch" data-target="#NoteList" data-action="<?php echo $view['router']->path('mautic_contactnote_index', ['leadId' => $lead->getId(), 'page' => 1]); ?>">
                    <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                </div>
                <input type="hidden" name="leadId" id="leadId" value="<?php echo $view->escape($lead->getId()); ?>" />
            </form>
        </div>
        <div class="col-xs-2 va-t">
            <a class="btn btn-primary btn-leadnote-add pull-right" href="<?php echo $view['router']->path('mautic_contactnote_action', ['leadId' => $lead->getId(), 'objectAction' => 'new']); ?>" data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.new'); ?>"><i class="fa fa-plus fa-lg"></i> <?php echo $view['translator']->trans('mautic.lead.add.note'); ?></a>
        </div>
    </div>

    <div class='custom-item-list'>
        Loading...
    </div>
</div>