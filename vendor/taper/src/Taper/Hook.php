<?php

namespace Taper;

class Hook
{
    
    //Hook
    static private $hook = array();

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Add Hook
     * @param string $hook  hook name
     * @param array|string $method hook method
     */
    public function add($hook, $method)
    {
        if (!isset(self::$hook[$hook])) {
            self::$hook[$hook] = array();
        }
        if (is_array($method)) {
            self::$hook[$hook] = array_merge(self::$hook[$hook], $method);
        } else {
            self::$hook[$hook][] = $method;
        }
    }

    /**
     * Get Hook
     * @param string $hook Hook name
     * @return array
     */
    public function get($hook = '')
    {
        if (empty($hook)) {
            return self::$hook;
        } else {
            return self::$hook[$hook];
        }
    }

    /**
     * import hook
     * @param array $data Hook data
     * @param bool $recursive whether recursive merge
     */
    public function import($data, $recursive = true)
    {
        if ($recursive === false) {
            self::$hook = array_merge(self::$hook, $data);
        } else {
            foreach ($data as $hook => $value) {
                if (!isset(self::$hook[$hook]))
                    self::$hook[$hook] = array();
                if (isset($value['_overflow'])) {
                    unset($value['_overflow']);
                    self::$hook[$hook] = $value;
                } else {
                    self::$hook[$hook] = array_merge(self::$hook[$hook], $value);
                }
            }
        }
    }

    /**
     * listen hook
     * @param string $hook hook name
     * @param array $params
     * @return bool
     */
    public function listen($hook, &$params = [])
    {
        $this->app['logger']->record("[ $hook ] --START--", Log::INFO);
        if (!isset(self::$hook[$hook])) return false;
        foreach (self::$hook[$hook] as $name) {
            if (false == $this->exec($name, $params)) return;
        }
    }

    /**
     * Executes a callback function.
     *
     * @param callback $name Callback function
     * @param array $params Function parameters
     * @return mixed Function results
     * @throws \Exception
     */
    public function exec($name, &$params = array())
    {
        if (is_callable($name)) {
            return is_array($name) ?
                self::invokeMethod($name, $params) :
                self::callFunction($name, $params);
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
}