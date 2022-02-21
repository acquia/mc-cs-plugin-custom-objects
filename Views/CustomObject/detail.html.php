<?php declare(strict_types=1);

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'customObject');
$view['slots']->set('headerTitle', $customObject->getNameSingular());
$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        ['item' => $customObject]
    )
);

$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $customObject])
);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- form detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-2 text-right">
                    </div>
                </div>
            </div>
            <!--/ form detail header -->

            <!-- form detail collapseable -->
            <div class="collapse" id="custom-object-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
    'MauticCoreBundle:Helper:details.html.php',
    ['entity' => $customObject]
); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ form detail collapseable -->
        </div>

        <!--/ detail collapseable toggler -->
        <div class="bg-auto bg-dark-xs">
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#custom-object-details">
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

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#fields-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('custom.object.tab.fields'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->

        </div>

        <div class="tab-content pa-md">

            <!-- #fields-container -->
            <div class="tab-pane fade active in bdr-w-0" id="fields-container">
                <ul class="list-group mb-xs">
                    <?php /** @var MauticPlugin\CustomObjectsBundle\Entity\CustomField $field */
                    foreach ($customObject->getCustomFields() as $field) : ?>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <?php $requiredTitle = $field->isRequired() ? 'mautic.core.required'
                                        : 'mautic.core.not_required'; ?>
                                    <h3><span class="fa fa-<?php echo $field->isRequired() ? 'check'
                                            : 'times'; ?> text-white dark-xs" data-toggle="tooltip"
                                              data-placement="left"
                                              title="<?php echo $view['translator']->trans($requiredTitle); ?>"></span>
                                    </h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary mb-xs"><?php echo $field->getLabel(); ?></h5>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <em class="text-white dark-sm"><?php echo $field->getTypeObject()->getName(); ?></em>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!--/ #fields-container -->

        </div>

    </div>

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
</div>
