<?php declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$lookup) {
    $view->extend('MauticCoreBundle:Default:content.html.php');

    $view['slots']->set('mauticContent', 'customItem');
    $view['slots']->set('headerTitle', $customObject->getName());
    $view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php'));
}
?>

<div class="clearfix">
    <div class="panel panel-default mb-0<?php if (!$lookup):?> bdr-t-wdh-0<?php endif; ?>">
        <?php echo $view->render(
    'MauticCoreBundle:Helper:list_toolbar.html.php',
    [
            'searchValue'     => $searchValue,
            'action'          => $currentRoute,
            'target'          => '#'.$namespace,
            'overlayDisabled' => $lookup,
        ]
); ?>
        <div class="page-list" id="<?php echo $namespace; ?>">
            <?php $view['slots']->output('_content'); ?>
        </div>
    </div>
</div>
