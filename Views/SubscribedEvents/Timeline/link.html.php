<?php declare(strict_types=1);

$link = $event['extra'];

?>
<dl class="dl-horizontal">
<?php if (!empty($link['user_id'])) : ?>
    <dt>
        <?php echo $view['translator']->trans('mautic.core.createdby'); ?>
    </dt>
    <dd>
        <a href="<?php echo $view['router']->path('mautic_user_action', ['objectAction' => 'view', 'objectId' => $link['user_id']]); ?>" data-toggle="ajax">
            <?php echo $link['user_name']; ?>
        </a>
    </dd>
<?php endif; ?>
</dl>
