<?php

namespace Taper\Dispatcher;

use Taper\Container\ContainerInterface,
    Taper\Log;

/**
 * The Dispatcher class is responsible for dispatching events. Events
 * are simply aliases for class methods or functions. The Dispatcher
 * allows you to hook other functions to an event that can modify the
 * input parameters and/or the output.
 */
class Dispatcher {
    /**
     * Mapped events.
     *
     * @var array
     */
    protected $events = array();

    /**
     * Method filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * @var ContainerInterface app
     */
    private $app;

    /**
     * Instantiate the dispatcher.
     */
    public function __construct(ContainerInterface $app) {
        $this->app = $app;
    }

    /**
     * Dispatches an event.
     *
     * @param string $name Event name
     * @param array $params Callback parameters
     * @return string Output of callback
     */
    public function run($name, array $params = array()) {
        $output = '';

        // Run pre-filters
        if (!empty($this->filters[$name]['before'])) {
            // Logger
            $this->app['logger']->record("[ $name ] --before--", Log::INFO);
            $this->filter($this->filters[$name]['before'], $params, $output);
        }

        // Logger
        $this->app['logger']->record("[ $name ] --process--", Log::INFO);
        // Run requested method
        $output = $this->execute($this->get($name), $params);

        // Run post-filters
        if (!empty($this->filters[$name]['after'])) {
            // Logger
            $this->app['logger']->record("[ $name ] --after--", Log::INFO);
            $this->filter($this->filters[$name]['after'], $params, $output);
        }

        return $output;
    }

    /**
     * Assigns a callback to an event.
     *
     * @param string $name Event name
     * @param callback $callback Callback function
     */
    public function set($name, $callback) {
        $this->events[$name] = $callback;
    }

    /**
     * Gets an assigned callback.
     *
     * @param string $name Event name
     * @return callback $callback Callback function
     */
    public function get($name) {
        return isset($this->events[$name]) ? $this->events[$name] : null;
    }

    /**
     * Checks if an event has been set.
     *
     * @param string $name Event name
     * @return bool Event status
     */
    public function has($name) {
        return isset($this->events[$name]);
    }

    /**
     * Clears an event. If no name is given,
     * all events are removed.
     *
     * @param string $name Event name
     */
    public function clear($name = null) {
        if ($name !== null) {
            unset($this->events[$name]);
            unset($this->filters[$name]);
        }
        else {
            $this->events = array();
            $this->filters = array();
        }
    }

    /**
     * Hooks a callback to an event.
     *
     * @param string $name Event name
     * @param string $type Filter type
     * @param callback $callback Callback function
     */
    public function hook($name, $type, $callback) {
        $this->filters[$name][$type][] = $callback;
    }

    /**
     * Executes a chain of method filters.
     *
     * @param array $filters Chain of filters
     * @param array $params Method parameters
     * @param mixed $output Method output
     */
    public function filter($filters, &$params, &$output) {
        $args = array(&$params, &$output);
        foreach ($filters as $callback) {
            $continue = $this->execute($callback, $args);
            if ($continue === false) break;
        }
    }

    /**
     * Executes a callback function.
     *
     * @param callback $callback Callback function
     * @param array $params Function parameters
     * @return mixed Function results
     * @throws \Exception
     */
    public static function execute($callback, array &$params = array()) {
        if (is_callable($callback)) {
            return is_array($callback) ?
                self::invokeMethod($callback, $params) :
                self::callFunction($callback, $params);
        } else {
            throw new \Exception('Invalid callback specified.');
        }
    }

    /**
     * Calls a function.
     *
     * @param string $func Name of function to call
     * @param array $params Function parameters
     * @return mixed Function results
     */
    public static function callFunction($func, array &$params = array()) {
		return count($params) ? call_user_func_array($func, $params) : $func();
    }

    /**
     * Invokes a method.
     *
     * @param mixed $func Class method
     * @param array $params Class method parameters
     * @return mixed Function results
     */
    public static function invokeMethod($func, array &$params = array()) {
        //list($class, $method) = $func;
		//$instance = is_object($class);

		return call_user_func_array($func, $params);
    }

    /**
     * Resets the object to the initial state.
     */
    public function reset() {
        $this->events = array();
        $this->filters = array();
    }
}
