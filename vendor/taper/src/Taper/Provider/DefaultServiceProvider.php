<?php
namespace Taper\Provider;

use Taper\Container\Container,
    Taper\Container\ContainerInterface,
    Taper\Http\Environment,
    Taper\Http\Headers,
    Taper\Http\Request,
    Taper\Http\Response,
    Taper\Dispatcher\Dispatcher,
    Taper\Debug,
    Taper\Log,
    Taper\Route,
    Taper\Controller,
    Taper\Hook,
    Taper\Handlers\PhpError,
    Taper\Handlers\Error,
    Taper\Handlers\NotFound,
    Taper\Handlers\NotAllowed,
    Taper\Handlers\ErrorHandler;

/**
 * Default Service Provider.
 */
class DefaultServiceProvider implements ServiceProviderInterface
{
    /**
     * Register default service provider.
     *
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container)
    {
        if (!isset($container['environment'])) {
            /**
             * This service MUST return a shared instance
             * of \Taper\Http\Environment.
             *
             * @return \Taper\Http\Environment
             */
            $container['environment'] = function () {
                return new Environment($_SERVER);
            };
        }

        if (!isset($container['request'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $container
             *
             * @return \Taper\Http\Request
             */
            $container['request'] = $container->share(function () use ($container) {
                return Request::createFromEnvironment($container->_get('environment'));
            });
        }

        if (!isset($container['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $container
             * @return \Taper\Http\Response
             */
            $container['response'] = $container->share(function () use ($container) {
                $headers = new Headers(['Content-Type' => 'text/html; charset=utf-8']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($container['settings']['http.version']);
            });
        }

        if (!isset($container['route'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $container
             * @return \Taper\Route
             */
            $container['route'] = $container->share(function () use ($container) {
                $route = new Route();

                if (is_callable([$route, 'setContainer'])) {
                    $route->setContainer($container);
                }

                return $route;
            });
        }

        if (!isset($container['debugger'])) {
            /**
             * Debug object
             *
             * @param Container $container
             * @return \Taper\Debug
             */
            $container['debugger'] = $container->share(function () use ($container) {
                return new Debug($container);
            });
        }

        if (!isset($container['dispatcher'])) {
            /**
             * Dispatcher object
             *
             * @param Container $container
             * @return \Taper\Dispatcher\Dispatcher
             */
            $container['dispatcher'] = $container->share(function () use ($container) {
                return new Dispatcher($container);
            });
        }

        if (!isset($container['logger'])) {
            /**
             * Dispatcher object
             *
             * @param Container $container
             * @return \Taper\Log
             */
            $container['logger'] = $container->share(function () use ($container) {
                return new Log($container);
            });
        }

        if (!isset($container['controller'])) {
            /**
             * Controller object
             *
             * @param Container $container
             * @return \Taper\Controller
             */
            $container['controller'] = $container->share(function () use ($container) {
                return new Controller($container);
            });
        }

        if (!isset($container['hook'])) {
            /**
             * Hook object
             *
             * @param Container $container
             * @return \Taper\Hook
             */
            $container['hook'] = $container->share(function () use ($container) {
                return new Hook($container);
            });
        }

        if (!isset($container['errorHandler'])) {
            $container['errorHandler'] = $container->share(function () use ($container) {
                return new ErrorHandler($container);
            });
        }

        if (!isset($container['phpErrorHandler'])) {
            /**
             * This service MUST return a callable
             * that accepts three arguments:
             *
             * 1. Instance of \Psr\Http\Message\ServerRequestInterface
             * 2. Instance of \Psr\Http\Message\ResponseInterface
             * 3. Instance of \Error
             *
             * The callable MUST return an instance of
             * \Psr\Http\Message\ResponseInterface.
             *
             * @param Container $container
             *
             * @return callable
             */
            $container['phpErrorHandler'] = function ($container) {
                return new PhpError($container['settings']['displayErrorDetails']);
            };
        }

        if (!isset($container['errorHandler'])) {
            /**
             * This service MUST return a callable
             * that accepts three arguments:
             *
             * 1. Instance of \Psr\Http\Message\ServerRequestInterface
             * 2. Instance of \Psr\Http\Message\ResponseInterface
             * 3. Instance of \Exception
             *
             * The callable MUST return an instance of
             * \Psr\Http\Message\ResponseInterface.
             *
             * @param Container $container
             *
             * @return callable
             */
            $container['errorHandler'] = function ($container) {
                return new Error($container['settings']['displayErrorDetails']);
            };
        }

        if (!isset($container['notFoundHandler'])) {
            /**
             * This service MUST return a callable
             * that accepts two arguments:
             *
             * 1. Instance of \Psr\Http\Message\ServerRequestInterface
             * 2. Instance of \Psr\Http\Message\ResponseInterface
             *
             * The callable MUST return an instance of
             * \Psr\Http\Message\ResponseInterface.
             *
             * @return callable
             */
            $container['notFoundHandler'] = function () {
                return new NotFound;
            };
        }

        if (!isset($container['notAllowedHandler'])) {
            /**
             * This service MUST return a callable
             * that accepts three arguments:
             *
             * 1. Instance of \Psr\Http\Message\ServerRequestInterface
             * 2. Instance of \Psr\Http\Message\ResponseInterface
             * 3. Array of allowed HTTP methods
             *
             * The callable MUST return an instance of
             * \Psr\Http\Message\ResponseInterface.
             *
             * @return callable
             */
            $container['notAllowedHandler'] = function () {
                return new NotAllowed;
            };
        }
    }


}
