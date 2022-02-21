<?php

declare(strict_types=1);

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
