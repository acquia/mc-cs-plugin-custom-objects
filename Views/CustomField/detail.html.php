<?php declare(strict_types=1);

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'customField');
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
                        <div class="text-muted"><?php echo $item->getType(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item]); ?>
                    </div>
                </div>
            </div>
            <!--/ page detail header -->
        </div>
        <div class="pa-md">
            <div class="row">
                <div class="col-md-12">
                </div>
            </div>
        </div>
    </div>

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php //echo $view['translator']->trans('mautic.webhook.webhook_url');?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                </div>
            </div>

            <hr class="hr-w-2" style="width:50%">

            <!-- recent activity -->
            <?php //echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]);?>
        </div>
    </div>
    <!--/ right section -->
</div>
