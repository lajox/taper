<?php

namespace Taper;

use Taper\Container\Container,
    Taper\Provider\ServiceProviderInterface as BaseServiceProviderInterface,
    Pimple\ServiceProviderInterface as PimpleServiceProviderInterface,
    Taper\Http\Request,
    Taper\Http\Response,
    Taper\Dispatcher\Dispatcher;

class Application extends Container
{
    const VERSION = '1.0.0';

    private $rtsfs = array('get','post','put','delete','input','trace','options','head','connect');

    protected $providers = array();
    protected $booted = false;

    /**
     * Instantiate a new Application.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = array())
    {
        parent::__construct();

        session_start();

        header("Content-type: text/html; charset=utf-8");

        $app = $this;

        $this['version'] = self::VERSION;

        $this['base_url'] = null;
        $this['web.path'] = './web/';
        $this['request.http_port'] = 80;
        $this['request.https_port'] = 443;
        $this['debug'] = false;
        $this['charset'] = 'UTF-8';
        $this['locale'] = 'en';

        $this['handle_errors'] = true;
        $this['log_errors'] = false;

        //debug
        $this['debug_tool'] = true;

        $this['debugger'] = $this->share(function () use ($app) {
            return new Debug($app);
        });

        $this['debugger']->start("start");

        $this['dispatcher'] = $this->share(function () use ($app) {
            return new Dispatcher($app);
        });

        $this['route'] = $this->share(function () use ($app) {
            $route = new Route();

            if (is_callable([$route, 'setContainer'])) {
                $route->setContainer($this);
            }

            return $route;
        });

        $this['request'] = $this->share(function () use ($app) {
            return new Request();
        });

        $this['response'] = $this->share(function () use ($app) {
            return new Response();
        });

        $this['controller'] = $this->share(function () use ($app) {
            return new Controller($app);
        });

        $this['hook'] = $this->share(function () use ($app) {
            return new Hook($app);
        });

        //logger
        $this['logger'] = $this->share(function () use ($app) {
            return new Log($this);
        });
        $this['log.record'] = true;
        $this['log.level'] = array('FATAL','ERROR','WARNING','NOTICE','INFO','SQL'); //写入日志的错误级别
        $this['log.path'] = $this['web.path']. 'data/log/';

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        // Register framework methods
        $methods = array(
            'start','stop','json','jsonp','redirect'
        );
        foreach ($methods as $name) {
            if(!in_array($name, $this->rtsfs)) {
                $this['dispatcher']->set($name, array($this, '_'.$name));
            }
        }
    }

    /**
     * Handles calls to class methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed Callback results
     */
    public function __call($name, $params) {
        if (method_exists($this, $name)) {
            //throw new \Exception('Cannot override an existing framework method.');
        }

        if(in_array($name, $this->rtsfs)) {
            return $this->map([strtoupper($name)], $params[0], $params[1]);
        }

        $callback = $this['dispatcher']->get($name);
        if (is_callable($callback)) {
            $r = $this['dispatcher']->run($name, $params);
            if($name!='stop') {
                $this->stop();
            }
            return $r;
        }
    }

    /**
     * Registers a service provider.
     *
     * @param PimpleServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array   $values   An array of values that customizes the provider
     *
     * @return Application
     */
    public function register(PimpleServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if (!$this->booted) {
            foreach ($this->providers as $provider) {
                $provider->boot($this);
            }

            $this->booted = true;
        }
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $callable      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function match($pattern, $callable = null)
    {
        return $this->map(['MATCH'], $pattern, $callable);
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $callable      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function get($pattern, $callable = null)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  callable|string    $callable The route callback routine
     *
     * @return \Taper\Router\Router
     */
    public function map(array $methods, $pattern, $callable)
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }

        $this['route']->map($methods, $pattern, $callable);

        return $this['route'];
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
        if ($this['log_errors']) {
            error_log($e->getMessage());
        }

        $this->error($e);
    }


    /**
     * Stop processing and returns a given response.
     *
     * @param int $code HTTP status code
     * @param string $message Response message
     */
    public function halt($code = 200, $message = '') {
        $this['response']
            ->status($code)
            ->write($message)
            ->send();
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
            $this['response']
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
        $this['response']
            ->status(404)
            ->write(
                '<h1>404 Not Found</h1>'.
                '<h3>The page you have requested could not be found.</h3>'.
                str_repeat(' ', 512)
            )
            ->send();
    }

    /**
     * Stop the framework and outputs the current response.
     *
     * @param int $code HTTP status code
     */
    public function _stop($code = 200) {
        if($this['log.record']) {
            $this['logger']->save();
        }

        if($this['debug_tool']) {
            $this['debugger']->show('start', 'stop');
        }

        $this['response']
            ->status($code)
            ->write(ob_get_clean())
            ->send();
    }

    /**
     * Send a JSON response.
     *
     * @param mixed $data JSON data
     * @param int $code HTTP status code
     * @param bool $encode Whether to perform JSON encoding
     * @param string $charset Charset
     */
    public function _json($data, $code = 200, $encode = true, $charset = 'utf-8') {
        $json = ($encode) ? json_encode($data) : $data;

        $this['response']
            ->status($code)
            ->header('Content-Type', 'application/json; charset='.$charset)
            ->write($json)
            ->send();
    }

    /**
     * Send a JSONP response.
     *
     * @param mixed $data JSON data
     * @param string $param Query parameter that specifies the callback name.
     * @param int $code HTTP status code
     * @param bool $encode Whether to perform JSON encoding
     * @param string $charset Charset
     */
    public function _jsonp($data, $param = 'jsonp', $code = 200, $encode = true, $charset = 'utf-8') {
        $json = ($encode) ? json_encode($data) : $data;

        $callback = $this['request']->query[$param];

        $this['response']
            ->status($code)
            ->header('Content-Type', 'application/javascript; charset='.$charset)
            ->write($callback.'('.$json.');')
            ->send();
    }

    /**
     * Redirect the current request to another URL.
     *
     * @param string $url URL
     * @param int $code HTTP status code
     */
    public function _redirect($url, $code = 303) {
        $base = $this['base_url'];

        if ($base === null) {
            $base = $this['request']->base;
        }

        // Append base url to redirect url
        if ($base != '/' && strpos($url, '://') === false) {
            $url = preg_replace('#/+#', '/', $base.'/'.$url);
        }

        $this['response']
            ->status($code)
            ->header('Location', $url)
            ->write($url)
            ->send();
    }

    /**
     * Start the framework.
     */
    public function _start() {

        // Enable error handling
        $this->handleErrors( $this['handle_errors'] );

        $self = $this;
        $request = $this['request'];
        $response = $this['response'];
        $route = $this['route'];

        // Flush any existing output
        if (ob_get_length() > 0) {
            $response->write(ob_get_clean());
        }

        // Enable output buffering
        ob_start();

        // Allow post-filters to run
        $this->after('start', function() use ($self) {
            $self->stop();
        });

        $dispatched = false;
        $continue = true;

        // Route the request
        foreach ($route->getRoutes() as $router) {

            if ($router !== false && $router->matchMethod($request->method) && $router->matchUrl($request->url)) {
                $params = array_values($router->params);

                $this['dispatcher']->execute(
                    $router->callback,
                    $params
                );

                $continue = false;
            }

            $dispatched = true;

            if (!$continue) break;

            $dispatched = false;
        }

        if (!$dispatched) {
            $this->notFound();
        }
    }

    /**
     * Add a custom method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function hook($name, $callback)
    {
        $this['dispatcher']->set($name, $callback);
    }

    /**
     * Add a pre-filter to a method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function before($name, $callback) {
        $this['dispatcher']->hook($name, 'before', $callback);
    }

    /**
     * Add a post-filter to a method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function after($name, $callback) {
        $this['dispatcher']->hook($name, 'after', $callback);
    }

}
