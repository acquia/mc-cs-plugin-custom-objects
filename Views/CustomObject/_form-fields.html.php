<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
foreach ($form->children['customFields']->getIterator() as $customField):
    $customFieldEntity = $customField->vars['data'];
    if (!in_array($customFieldEntity->getId(), $deletedFields, true)) :
        echo $view->render(
            "CustomObjectsBundle:CustomObject:Form\\Panel\\{$customFieldEntity->getType()}.html.php",
            [
                'customField' => $customField,
                'customObject' => $customObject,
                'panelId' => isset($panelId) ? $panelId : null,
            ]
        );
    endif;
endforeach;
$form->children['customFields']->setRendered();
