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
$route = $view['router']->path('mautic_contactnote_index', ['leadId' => $lead->getId(), 'page' => 1]);
?>
<div class="tab-pane fade bdr-w-0" id="custom-object-<?php echo $customObject->getId(); ?>-container">
    <div class="box-layout mb-lg">
        <div class="form-control-icon pa-xs">
            <input 
                type="text" 
                data-toggle='typeahead' 
                class="form-control bdr-w-0"
                placeholder="<?php echo $placeholder ; ?>" 
                data-action="<?php echo $route; ?>">
        </div>
    </div>

    <div class='custom-item-list'>
        Loading...
    </div>
</div>