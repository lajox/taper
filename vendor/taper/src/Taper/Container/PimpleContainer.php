<?php
namespace Taper\Container;

use Interop\Container\ContainerInterface;
use Pimple\Container as BaseContainer;
use Taper\Exception\ContainerValueNotFoundException;
use Taper\Exception\ContainerException as BaseContainerException;
use Taper\Collection\Collection;
use Taper\Provider\ServiceProvider;

class PimpleContainer extends BaseContainer implements ContainerInterface
{

    protected $_setting = [
    ];

    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['settings']) ? $values['settings'] : [];
        $this->registerDefaultServices($userSettings);
    }

    private function registerDefaultServices($userSettings)
    {
        $_setting = $this->_setting;

        $this['settings'] = function () use ($userSettings, $_setting) {
            return new Collection(array_merge($_setting, $userSettings));
        };

        //$defaultProvider = new ServiceProvider();
        //$defaultProvider->register($this);
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
        return $this->get($name);
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
