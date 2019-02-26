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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\HttpFoundation\Response;

class ContactListController extends CommonController
{
    use EntityContactsTrait;

    /**
     * @param int $objectId
     * @param int $page
     *
     * @return Response
     */
    public function listAction(int $objectId, int $page = 1): Response
    {
        return $this->generateContactsGrid(
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
