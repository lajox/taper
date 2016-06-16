<?php
namespace Taper\Provider;

use Taper\Container\Container;

/**
 * Service Provider.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Register default services.
     */
    public function register(Container $container)
    {
        if (!isset($container['router'])) {
            $container['router'] = function ($container) {
                return $container->get('settings');
            };
        }
    }

    public function boot(Container $container)
    {
        // TODO: Implement boot() method.
    }
}
