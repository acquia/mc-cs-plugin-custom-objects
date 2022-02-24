<?php

declare(strict_types=1);

?>
<li>
    <a href="#<?php echo $tabId; ?>-container" role="tab" data-toggle="tab" id="<?php echo $tabId; ?>">
        <?php if (isset($count)) : ?>
        <span class="label label-primary mr-sm" id="<?php echo $tabId; ?>-count">
            <?php echo $count; ?>
        </span>
        <?php endif; ?>
        <?php echo $view->escape($view['translator']->trans($title)); ?>
    </a>
</li>