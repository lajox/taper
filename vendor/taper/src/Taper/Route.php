<?php

namespace Taper;

use Taper\Container\Container,
    Taper\Router\Router;

class Route extends Container
{

    protected $routes = array();

    /**
     * @var Application app
     */
    private $app;

    /**
     * Constructor.
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
    }

    /**
     * Gets mapped routes.
     *
     * @return array Array of routes
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Clears all routes in the router.
     */
    public function clear() {
        $this->routes = array();
    }

    public function map($methods, $pattern, $callback) {
        $router = new Router();
        $router->map($methods, $pattern, $callback);
        $this->routes[] = $router;
    }

    public function setContainer(Application $app) {
        $this->app = $app;
    }
}
