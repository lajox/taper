<?php
namespace Taper\Container;

use Pimple\Container as BaseContainer,
    Pimple\ServiceProviderInterface as PimpleServiceProviderInterface,
    Taper\Provider\ServiceProviderInterface,
    Taper\Exception\ContainerValueNotFoundException,
    Taper\Exception\ContainerException as BaseContainerException,
    Taper\Collection\Collection;

class PimpleContainer extends BaseContainer implements ContainerInterface
{

    const VERSION = '1.0.0';

    private $providers;

    private $provider_list;

    protected $_setting = [
        'version' => self::VERSION,
        'http.version' => '1.1',
        'base_url' => null,
        'web.path' => './web/',
        'request.http_port' => 80,
        'request.https_port' => 443,
        'debug' => false,
        'charset' => 'UTF-8',
        'locale' => 'en',
        //errors
        'handle_errors' => true,
        'log_errors' => false,
        //debug
        'debug_tool' => true,
        //logger
        'log.record' => true,
        'log.level' => array('FATAL','ERROR','WARNING','NOTICE','INFO','SQL'), //写入日志的错误级别
        'log.path' => './web/data/log/',
        //..
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
    ];

    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['settings']) ? $values['settings'] : [];

        // Service Provider list
        $this->provider_list = array(
            '\\Taper\\Provider\\DefaultServiceProvider',
        );

        $this->registerServices($userSettings);
    }

    private function registerServices($userSettings)
    {
        $_setting = $this->_setting;

        $this['settings'] = function () use ($userSettings, $_setting) {
            return new Collection(array_merge($_setting, $userSettings));
        };

        foreach($this->provider_list as $provider) {
            $this->registerServiceProvider( new $provider() );
        }
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array   $values   An array of values that customizes the provider
     *
     * @return PimpleContainer
     */
    private function registerServiceProvider(ServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new ContainerValueNotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $exception) {
            if ($this->exceptionThrownByContainer($exception)) {
                throw new BaseContainerException(
                    sprintf('Container error while retrieving "%s"', $id),
                    null,
                    $exception
                );
            } else {
                throw $exception;
            }
        }
    }

    public function _get($id)
    {
        return self::get($id);
    }

    private function exceptionThrownByContainer(\InvalidArgumentException $exception)
    {
        $trace = $exception->getTrace()[0];

        return $trace['class'] === 'Pimple\Container' && $trace['function'] === 'offsetGet';
    }

    public function has($id)
    {
        return $this->offsetExists($id);
    }

    public function __get($name)
    {
        //return $this->get($name);
        return $this->_get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Returns a closure that stores the result of the given service definition
     * for uniqueness in the scope of this instance of Pimple.
     *
     * @param callable $callable A service definition to wrap for uniqueness
     *
     * @return \Closure The wrapped closure
     */
    public static function share($callable)
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Service definition is not a Closure or invokable object.');
        }

        return function ($c) use ($callable) {
            static $object;

            if (null === $object) {
                $object = $callable($c);
            }

            return $object;
        };
    }
}
