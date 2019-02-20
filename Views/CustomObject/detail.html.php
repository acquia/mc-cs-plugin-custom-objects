<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $customObject]); ?>
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
    </div>

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php //echo $view['translator']->trans('mautic.webhook.webhook_url'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                </div>
            </div>

            <hr class="hr-w-2" style="width:50%">

            <!-- recent activity -->
            <?php //echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
        </div>
    </div>
    <!--/ right section -->
</div>
