<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mautic\CoreBundle\Controller\MauticController;

/**
 * Even though we use nice controllers with defined dependencies, when we call some method like
 * `forward` or `postActionRedirect` it use container and all that nasty stuff. This trait should
 * take care of it.
 */
trait ControllerDependenciesTrait
{
    private function addSymfonyDependencies(Controller $controller): void
    {
        $requestStack = empty($this->requestStack) ? $this->createMock(RequestStack::class) : $this->requestStack;
        $request      = empty($this->request) ? $this->createMock(Request::class) : $this->request;
        $session      = empty($this->session) ? $this->createMock(Session::class) : $this->session;

        $container         = $this->createMock(ContainerInterface::class);
        $httpKernel        = $this->createMock(HttpKernel::class);
        $response          = $this->createMock(Response::class);
        $phpEngine         = $this->createMock(PhpEngine::class);
        $modelFactory      = $this->createMock(ModelFactory::class);
        $notificationModel = $this->createMock(NotificationModel::class);
        $security          = $this->createMock(CorePermissions::class);
        $translator        = $this->createMock(TranslatorInterface::class);

        $container->method('get')->will($this->returnValueMap([
            ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $requestStack],
            ['http_kernel', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $httpKernel],
            ['templating', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $phpEngine],
            ['mautic.model.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $modelFactory],
            ['session', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $session],
            ['mautic.security', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $security],
        ]));

        $phpEngine->method('renderResponse')->willReturn($response);

        $container->method('has')->will($this->returnValueMap([
            ['templating', true],
        ]));

        $modelFactory->method('getModel')->will($this->returnValueMap([
            ['core.notification', $notificationModel],
        ]));

        $request->method('duplicate')->willReturnSelf();
        $httpKernel->method('handle')->willReturn($response);
        $notificationModel->method('getNotificationContent')->willReturn([[], '', '']);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $controller->setContainer($container);

        if ($controller instanceof MauticController) {
            $controller->setRequest($request);
            $controller->setTranslator($translator);
        }
    }
}
