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

use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Symfony\Component\Form\FormView;

/* @var PhpEngine $view */
/* @var FormView $form */

foreach ($form->children['customFields']->getIterator() as $customField):
    $customFieldEntity = $customField->vars['data'];
    if (!in_array($customFieldEntity->getId(), $deletedFields, true)) :
        echo $view->render(
            "CustomObjectsBundle:CustomObject:Form\\Panel\\{$customFieldEntity->getType()}.html.php",
            [
                'customField'  => $customField,
                'customObject' => $customObject,
                'panelId'      => $panelId ?? null,
            ]
        );
    endif;
endforeach;
$form->children['customFields']->setRendered();
