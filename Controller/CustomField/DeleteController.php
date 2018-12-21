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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class DeleteController extends CommonController
{
    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param CustomFieldModel $customFieldModel
     * @param Session $session
     * @param TranslatorInterface $translator
     * @param CustomFieldPermissionProvider $permissionProvider
     */
    public function __construct(
        CustomFieldModel $customFieldModel,
        Session $session,
        TranslatorInterface $translator,
        CustomFieldPermissionProvider $permissionProvider
    )
    {
        $this->customFieldModel   = $customFieldModel;
        $this->session            = $session;
        $this->translator         = $translator;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int $objectId
     * 
     * @return Response|JsonResponse
     */
    public function deleteAction(int $objectId)
    {
        try {
            $entity = $this->customFieldModel->fetchEntity($objectId);
            $this->permissionProvider->canDelete($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $this->customFieldModel->deleteEntity($entity);

        $this->session->getFlashBag()->add(
            'notice',
            $this->translator->trans(
                'mautic.core.notice.deleted',
                [
                    '%name%' => $entity->getName(),
                    '%id%'   => $entity->getId(),
                ], 
                'flashes'
            )
        );

        return $this->forward(
            'CustomObjectsBundle:CustomField\List:list',
            ['page' => $this->session->get('custom.field.page', 1)]
        );
    }
}