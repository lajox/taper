<?php

namespace Taper;

use Taper\Container\Container;

class Application extends Container
{

    private $_rtsfs = array('get','post','put','delete','input','trace','options','head','connect');

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
        session_start();

        parent::__construct();

        foreach ($values as $key => $value) {
            $this['settings'][$key] = $value;
        }

        $this->_get('debugger')->start("start");

        // Register framework methods
        $methods = array(
            'start','stop','json','jsonp','redirect'
        );
        foreach ($methods as $name) {
            if(!in_array($name, $this->_rtsfs)) {
                $this->_get('dispatcher')->set($name, array($this, '_'.$name));
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

        if(in_array($name, $this->_rtsfs)) {
            return $this->map([strtoupper($name)], $params[0], $params[1]);
        }

        $callback = $this->_get('dispatcher')->get($name);
        if (is_callable($callback)) {
            $r = $this->_get('dispatcher')->run($name, $params);
            if($name!='stop') {
                $this->stop();
            }
            return $r;
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
        //兼容容器的默认get方法
        if($callable == null && $this->has($pattern)) {
            return $this->_get($pattern);
        }
        else if($callable == null) {
            return $this->_get($pattern);
        }
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

        $this->_get('route')->map($methods, $pattern, $callable);

        return $this->_get('route');
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

        $this->_get('response')
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

        $callback = $this->_get('request')->query[$param];

        $this->_get('response')
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
        $base = $this['settings']['base_url'];

        if ($base === null) {
            $base = $this->_get('request')->base;
        }

        // Append base url to redirect url
        if ($base != '/' && strpos($url, '://') === false) {
            $url = preg_replace('#/+#', '/', $base.'/'.$url);
        }

        $this->_get('response')
            ->status($code)
            ->header('Location', $url)
            ->write($url)
            ->send();
    }

    /**
     * Stop processing and returns a given response.
     *
     * @param int $code HTTP status code
     * @param string $message Response message
     */
    public function halt($code = 200, $message = '') {
        $this->_get('response')
            ->status($code)
            ->write($message)
            ->send();
    }

    /**
     * Start the framework.
     */
    public function _start() {

        // Enable error handling
        $this->_get('errorHandler')->handleErrors( $this['settings']['handle_errors'] );

        $self = $this;
        $request = $this->_get('request');
        $response = $this->_get('response');
        $route = $this->_get('route');

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

            if ($router !== false && $router->matchMethod($request->getMethod()) && $router->matchUrl($request->data->url)) {
                $params = array_values($router->params);

                $this->_get('dispatcher')->execute(
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
            $this->_get('errorHandler')->notFound();
        }
    }

    /**
     * Stop the framework and outputs the current response.
     *
     * @param int $code HTTP status code
     */
    public function _stop($code = 200) {
        if($this['settings']['log.record']) {
            $this->_get('logger')->save();
        }

        if($this['settings']['debug_tool']) {
            $this->_get('response')->sendHeaders();
            $this->_get('debugger')->show('start', 'stop');
        }

        $this->_get('response')
            ->status($code)
            ->write(ob_get_clean())
            ->send();
    }

    /**
     * Add a custom method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function hook($name, $callback)
    {
        $this->_get('dispatcher')->set($name, $callback);
    }

    /**
     * Add a pre-filter to a method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function before($name, $callback) {
        $this->_get('dispatcher')->hook($name, 'before', $callback);
    }

    /**
     * Add a post-filter to a method.
     *
     * @param string $name Method name
     * @param callback $callback Callback function
     */
    public function after($name, $callback) {
        $this->_get('dispatcher')->hook($name, 'after', $callback);
    }

}
