<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Even though we use nice controllers with defined dependencies, when we call some method like
 * `forward` or `postActionRedirect` it use container and all that nasty stuff. This trait should
 * take care of it.
 */
class ControllerTestCase extends \PHPUnit\Framework\TestCase
{
    protected $requestStack;
    protected $request;
    protected $session;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    protected function addSymfonyDependencies(Controller $controller): void
    {
        $requestStack = empty($this->requestStack) ? $this->createMock(RequestStack::class) : $this->requestStack;
        $request      = empty($this->request) ? $this->createMock(Request::class) : $this->request;
        $session      = empty($this->session) ? $this->createMock(Session::class) : $this->session;

        $this->container   = $this->createMock(ContainerInterface::class);
        $httpKernel        = $this->createMock(HttpKernel::class);
        $response          = $this->createMock(Response::class);
        $phpEngine         = $this->createMock(PhpEngine::class);
        $modelFactory      = $this->createMock(ModelFactory::class);
        $notificationModel = $this->createMock(NotificationModel::class);
        $security          = $this->createMock(CorePermissions::class);
        $translator        = $this->createMock(TranslatorInterface::class);
        $this->router      = $this->createMock(RouterInterface::class);
        $this->userHelper  = $this->createMock(UserHelper::class);

        $this->container->method('get')->willReturnMap([
            ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $requestStack],
            ['http_kernel', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $httpKernel],
            ['templating', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $phpEngine],
            ['mautic.model.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $modelFactory],
            ['session', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $session],
            ['mautic.security', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $security],
            ['router', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->router],
            ['mautic.helper.user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->userHelper],
            ['twig', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->createMock(Environment::class)],
        ]);

        $phpEngine->method('renderResponse')->willReturn($response);

        $this->container->method('has')->willReturnMap([
            ['templating', false], // 'templating' will be removed in Symfony 5
            ['twig', true],
        ]);

        $modelFactory->method('getModel')->willReturnMap([
            ['core.notification', $notificationModel],
        ]);

        $request->query   = new ParameterBag();
        $request->headers = new HeaderBag();

        if (method_exists($request, 'method')) { // This is terrible, we should not mock Request at all. Damn you old me!
            $request->method('duplicate')->willReturnSelf();
        }

        $httpKernel->method('handle')->willReturn($response);
        $notificationModel->method('getNotificationContent')->willReturn([[], '', '']);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $controller->setContainer($this->container);

        if ($controller instanceof MauticController) {
            $controller->setRequest($request);
            $controller->setTranslator($translator);
        }

        if ($controller instanceof CommonController) {
            $controller->setCoreParametersHelper($this->createMock(CoreParametersHelper::class));
        }
    }
}
