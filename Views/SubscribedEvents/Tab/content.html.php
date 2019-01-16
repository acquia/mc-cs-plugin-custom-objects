<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$placeholder = $view['translator']->trans('custom.item.link.search.placeholder', ['%object%' => $customObject->getNameSingular()]);
?>
<div class="tab-pane fade bdr-w-0" id="custom-object-<?php echo $customObject->getId(); ?>-container">
    <div class="box-layout mb-lg">
        <div class="form-control-icon pa-xs">
            <input 
                type="text" 
                name="search" 
                data-toggle='field-lookup' 
                id="NoteFilter" 
                class="form-control bdr-w-0" placeholder="<?php echo $placeholder ; ?>" 
                data-lookup-callback="updateLookupListFilter"
                data-action="<?php echo $view['router']->path('mautic_contactnote_index', ['leadId' => $lead->getId(), 'page' => 1]); ?>">
            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
        </div>
    </div>

    <div class='custom-item-list'>
        Loading...
    </div>
</div>