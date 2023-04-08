<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactListController extends CommonController
{
    use EntityContactsTrait;

    /**
     * @codeCoverageIgnore as this just calls a Mautic core method
     */
    public function listAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        int $objectId,
        int $page = 1): Response {
        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            'lead:lists:viewother',
            'custom_item',
            'custom_item_xref_contact',
            null,
            'custom_item_id',
            [],
            [],
            'contact_id'
        );
    }
}
