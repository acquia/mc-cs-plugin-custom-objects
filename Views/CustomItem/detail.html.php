<?php declare(strict_types=1);

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'customItem');
$view['slots']->set('headerTitle', $item->getName());
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'item' => $item,
]));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-muted"><?php echo $view->escape($item->getCustomObject()->getNameSingular()); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item]); ?>
                    </div>
                </div>
            </div>
            <!--/ page detail header -->

            <div class="collapse" id="custom-item-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
    'MauticCoreBundle:Helper:details.html.php',
    ['entity' => $item]
); ?>
                            <?php foreach ($item->getCustomFieldValues() as $fieldValue) : ?>
                            <tr>
                                <th><?php echo $view->escape($fieldValue->getCustomField()->getName()); ?></th>
                                <td>
                                    <?php echo $view->render('CustomObjectsBundle:CustomField:value.html.php', ['fieldValue'  => $fieldValue]); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!--/ detail collapseable toggler -->
        <div class="bg-auto bg-dark-xs">
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                        data-target="#custom-item-details">
                        <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?>
                    </a>
                </span>
            </div>
            <!-- some stats -->

            <!--/ stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('custom.item.links.in.time'); ?>
                                    </h5>
                                </div>
                                <div class="col-md-9 va-m">
                                    <?php echo $view->render(
    'MauticCoreBundle:Helper:graph_dateselect.html.php',
    ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']
); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
    'MauticCoreBundle:Helper:chart.html.php',
    ['chartData' => $stats, 'chartType' => 'line', 'chartHeight' => 300]
); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo $view['content']->getCustomContent('details.stats.graph.below', $mauticTemplateVars); ?>
        </div>

        <!-- tabs controls -->
        <ul class="nav nav-tabs pr-md pl-md">
            <li class="active">
                <a href="#contacts-container" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                </a>
            </li>
            <?php echo $view['content']->getCustomContent('tabs', $mauticTemplateVars); ?>
        </ul>
        <!--/ tabs controls -->

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0 page-list" id="contacts-container">
                <?php echo $contacts; ?>
            </div>
            <?php echo $view['content']->getCustomContent('tabs.content', $mauticTemplateVars); ?>
        </div>
        <!-- end: tab-content -->
    </div>

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
</div>
