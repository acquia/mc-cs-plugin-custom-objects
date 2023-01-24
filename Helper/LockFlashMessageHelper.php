<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

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
                '%date%'     => $datetime->format($this->coreParametersHelper->get('date_format_dateonly')),
                '%time%'     => $datetime->format($this->coreParametersHelper->get('date_format_timeonly')),
                '%datetime%' => $datetime->format($this->coreParametersHelper->get('date_format_full')),
                '%override%' => $override,
            ],
            'error'
        );
    }
}
