<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class LockFlashMessageHelperTest extends TestCase
{
    public function testAddFlash(): void
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $flashBag             = $this->createMock(FlashBag::class);
        $router               = $this->createMock(Router::class);

        $helper = new LockFlashMessageHelper($coreParametersHelper, $translator, $flashBag, $router);

        $id               = 1;
        $name             = 'name';
        $checkedOut       = $this->createMock(\DateTime::class);
        $checkedOutBy     = 'getCheckedOutBy';
        $checkedOutByUser = 'checkedOutByUser';
        $returnUrl        = 'returnUrl';
        $contactUrl       = 'contactUrl';
        $canEdit          = false;
        $modelName        = 'model';

        $dateFormat1 = 'dateFormat1';
        $dateFormat2 = 'dateFormat2';
        $dateFormat3 = 'dateFormat3';

        $entity = $this->createMock(CustomObject::class);
        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $entity->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $entity->expects($this->once())
            ->method('getCheckedOut')
            ->willReturn($checkedOut);
        $entity->expects($this->once())
            ->method('getCheckedOutBy')
            ->willReturn($checkedOutBy);
        $entity->expects($this->once())
            ->method('getCheckedOutByUser')
            ->willReturn($checkedOutByUser);

        $router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_user_action',
                [
                    'objectAction' => 'contact',
                    'objectId'     => $checkedOutBy,
                    'id'           => $id,
                    'subject'      => 'locked',
                    'returnUrl'    => $returnUrl,
                ]
            )
            ->willReturn($contactUrl);

        $coreParametersHelper
            ->method('get')
            ->withConsecutive(['date_format_dateonly'], ['date_format_timeonly'], ['date_format_full'])
            ->willReturnOnConsecutiveCalls($dateFormat1, $dateFormat2, $dateFormat3);

        $checkedOut
            ->method('format')
            ->withConsecutive([$dateFormat1], [$dateFormat2], [$dateFormat3])
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $flashBag->expects($this->once())
            ->method('add')
            ->with(
                'mautic.core.error.locked',
                [
                    '%name%'       => $name,
                    '%user%'       => $checkedOutByUser,
                    '%contactUrl%' => $contactUrl,
                    '%date%'       => 1,
                    '%time%'       => 2,
                    '%datetime%'   => 3,
                    '%override%'   => '',
                ]
            );

        $helper->addFlash($entity, $returnUrl, $canEdit, $modelName);
    }
}
