<?php

namespace Taper\Handlers;

use Taper\Container\ContainerInterface;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
class ErrorHandler
{

    /**
     * @var ContainerInterface app
     */
    private $app;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Enables/disables custom error handling.
     *
     * @param bool $enabled True or false
     */
    public function handleErrors($enabled)
    {
        if ($enabled) {
            set_error_handler(array($this, 'handleError'));
            set_exception_handler(array($this, 'handleException'));
        }
        else {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * Custom error handler. Converts errors into exceptions.
     *
     * @param int $errno Error number
     * @param int $errstr Error string
     * @param int $errfile Error file name
     * @param int $errline Error file line number
     * @throws \ErrorException
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if ($errno & error_reporting()) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
    }

    /**
     * Custom exception handler. Logs exceptions.
     *
     * @param object $e Thrown exception
     */
    public function handleException($e) {
        if ($this->app['settings']['log_errors']) {
            error_log($e->getMessage());
        }

        $this->error($e);
    }

    /**
     * Send an HTTP 500 response for any errors.
     *
     * @param object $e Thrown exception
     */
    public function error($e) {
        $msg = sprintf('<h1>500 Internal Server Error</h1>'.
            '<h3>%s (%s)</h3>'.
            '<pre>%s</pre>',
            $e->getMessage(),
            $e->getCode(),
            $e->getTraceAsString()
        );

        try {
            $this->app->_get('response')
                ->status(500)
                ->write($msg)
                ->send();
        }
        catch (\Throwable $t) {
            exit($msg);
        }
        catch (\Exception $ex) {
            exit($msg);
        }
    }

    public function notFound() {
        $this->app->_get('response')
            ->status(404)
            ->write(
                '<h1>404 Not Found</h1>'.
                '<h3>The page you have requested could not be found.</h3>'.
                str_repeat(' ', 512)
            )
            ->send();
    }

}
