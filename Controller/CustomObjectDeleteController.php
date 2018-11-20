<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomObjectDeleteController extends CommonController
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param CustomObjectModel $customObjectModel
     * @param Session $session
     * @param TranslatorInterface $translator
     */
    public function __construct(
        CustomObjectModel $customObjectModel,
        Session $session,
        TranslatorInterface $translator
    )
    {
        $this->customObjectModel = $customObjectModel;
        $this->session           = $session;
        $this->translator        = $translator;
    }

    /**
     * @todo implement permissions
     * 
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId)
    {
        try {
            $entity = $this->customObjectModel->fetchEntity($objectId);
            $this->customObjectModel->deleteEntity($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        $this->session->getFlashBag()->add(
            'notice',
            $this->translator->trans(
                'mautic.core.notice.deleted',
                [
                    '%name%' => $entity->getName(),
                    '%id%'   => $objectId,
                ], 
                'flashes'
            )
        );

        return $this->forward(
            'CustomObjectsBundle:CustomObjectList:list',
            ['page' => $this->session->get('custom.object.page')]
        );
    }
}