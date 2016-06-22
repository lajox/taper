<?php

namespace Taper\Provider;

use Taper\Container\ContainerInterface;

/**
 * Interface that all Taper service providers must implement.
 *
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(ContainerInterface $container);
}
