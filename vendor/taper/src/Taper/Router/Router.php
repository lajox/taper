<?php

namespace Taper\Router;

use Taper\Container\ContainerInterface;

class Router implements RouterInterface
{
    /**
     * @var string URL pattern
     */
    public $pattern;

    /**
     * @var mixed Callback function
     */
    public $callback;

    /**
     * @var array HTTP methods
     */
    public $methods = array();

    /**
     * @var array Route parameters
     */
    public $params = array();

    /**
     * @var string Matching regular expression
     */
    public $regex;

    /**
     * @var string URL splat content
     */
    public $splat = '';

    /**
     * @var ContainerInterface app
     */
    private $app;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $app) {
        $this->app = $app;
        $this->methods = [ $this->app['request']->getMethod() ];
    }

    public function setContainer(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     */
    public function map(array $methods = [], $pattern = null, $callable = null)
    {
        if (!is_string($pattern)) {
            throw new \InvalidArgumentException('Route pattern must be a string');
        }

        $this->pattern = $pattern;
        $this->callback = $callable;
        $this->methods = $methods ? $methods : [ $this->app['request']->getMethod() ];

        return $this;
    }

    /**
     * Checks if a URL matches the route pattern. Also parses named parameters in the URL.
     *
     * @param string $url Requested URL
     * @return boolean Match status
     */
    public function matchUrl($url) {
        // Wildcard or exact match
        if ($this->pattern === '*' || $this->pattern === $url) {
            return true;
        }

        $ids = array();
        $last_char = substr($this->pattern, -1);

        // Get splat
        if ($last_char === '*') {
            $n = 0;
            $len = strlen($url);
            $count = substr_count($this->pattern, '/');

            for ($i = 0; $i < $len; $i++) {
                if ($url[$i] == '/') $n++;
                if ($n == $count) break;
            }

            $this->splat = (string)substr($url, $i+1);
        }

        // Build the regex for matching
        $regex = str_replace(array(')','/*'), array(')?','(/?|/.*?)'), $this->pattern);

        $regex = preg_replace_callback(
            '#@([\w]+)(:([^/\(\)]*))?#',
            function($matches) use (&$ids) {
                $ids[$matches[1]] = null;
                if (isset($matches[3])) {
                    return '(?P<'.$matches[1].'>'.$matches[3].')';
                }
                return '(?P<'.$matches[1].'>[^/\?]+)';
            },
            $regex
        );

        // Fix trailing slash
        if ($last_char === '/') {
            $regex .= '?';
        }
        // Allow trailing slash
        else {
            $regex .= '/?';
        }

        // Attempt to match route and named parameters
        if (preg_match('#^'.$regex.'(?:\?.*)?$#'.('i'), $url, $matches)) {
            foreach ($ids as $k => $v) {
                $this->params[$k] = (array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
            }

            $this->regex = $regex;

            return true;
        }

        return false;
    }

    /**
     * Checks if an HTTP method matches the route methods.
     *
     * @param string $method HTTP method
     * @return bool Match status
     */
    public function matchMethod($method) {
        return count(array_intersect(array($method, '*'), $this->methods)) > 0;
    }
    
}
