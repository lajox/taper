<?php
namespace Taper\Container;

class Container extends PimpleContainer
{
    public function __construct(array $values = [])
    {
        parent::__construct($values);
    }
}
