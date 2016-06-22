<?php
namespace Taper\Container;

class Container extends PimpleContainer
{
    /**
     * Constructor.
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
    }
}
