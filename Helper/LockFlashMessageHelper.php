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

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class LockFlashMessageHelper
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param CoreParametersHelper $coreParametersHelper
     * @param TranslatorInterface  $translator
     * @param FlashBag             $flashBag
     * @param Router               $router
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        TranslatorInterface $translator,
        FlashBag $flashBag,
        Router $router
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
        $this->flashBag             = $flashBag;
        $this->router               = $router;
    }

    /**
     * @param mixed  $entity
     * @param string $returnUrl
     * @param bool   $canEdit
     * @param string $modelName pattern 'bundle.modelName'
     */
    public function addFlash($entity, string $returnUrl, bool $canEdit, string $modelName): void
    {
        $datetime = $entity->getCheckedOut();
        $override = '';

        if ($canEdit) {
            $override = $this->translator->trans(
                'mautic.core.override.lock',
                [
                    '%url%' => $this->router->generate(
                        'mautic_core_form_action',
                        [
                            'objectAction' => 'unlock',
                            'objectModel'  => $modelName,
                            'objectId'     => $entity->getId(),
                            'returnUrl'    => $returnUrl,
                            'name'         => urlencode($entity->getName()),
                        ]
                    ),
                ]
            );
        }

        $this->flashBag->add(
            'mautic.core.error.locked',
            [
                '%name%'       => $entity->getName(),
                '%user%'       => $entity->getCheckedOutByUser(),
                '%contactUrl%' => $this->router->generate(
                    'mautic_user_action',
                    [
                        'objectAction' => 'contact',
                        'objectId'     => $entity->getCheckedOutBy(),
                        'id'           => $entity->getId(),
                        'subject'      => 'locked',
                        'returnUrl'    => $returnUrl,
                    ]
                ),
                '%date%'     => $datetime->format($this->coreParametersHelper->getParameter('date_format_dateonly')),
                '%time%'     => $datetime->format($this->coreParametersHelper->getParameter('date_format_timeonly')),
                '%datetime%' => $datetime->format($this->coreParametersHelper->getParameter('date_format_full')),
                '%override%' => $override,
            ],
            'error'
        );
    }
}
