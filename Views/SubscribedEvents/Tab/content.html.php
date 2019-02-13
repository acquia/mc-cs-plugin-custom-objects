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

$placeholder = $view['translator']->trans('custom.item.link.search.placeholder', ['%object%' => $customObject->getNameSingular()]);
$route       = $view['router']->path(CustomItemRouteProvider::ROUTE_LOOKUP, ['objectId' => $customObject->getId()]);
?>
<div class="tab-pane fade bdr-w-0 custom-object-search" id="custom-object-<?php echo $customObject->getId(); ?>-container">
    <div class="box-layout mb-lg panel">
        <div class="form-group form-control-icon pa-xs">
            <span class="the-icon fa fa-search text-muted mt-xs"></span>
            <input 
                type="text" 
                id="custom-object-search-<?php echo $customObject->getId(); ?>"
                data-toggle='typeahead' 
                class="form-control bdr-w-0"
                placeholder="<?php echo $placeholder; ?>" 
                data-action="<?php echo $route; ?>">
        </div>
    </div>
    <div class='custom-item-list page-list'>
        Loading...
    </div>
</div>
<script type="text/javascript">CustomObjects.initContactTabForCustomObject(<?php echo $customObject->getId(); ?>)</script>