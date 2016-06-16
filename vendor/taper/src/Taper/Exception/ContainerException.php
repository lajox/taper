<?php

namespace Taper\Exception;

use InvalidArgumentException;
use Interop\Container\Exception\ContainerException as InteropContainerException;

/**
 * Container Exception
 */
class ContainerException extends InvalidArgumentException implements InteropContainerException
{
    public function __construct($str)
    {
        error_log($str);
        echo $str;
        exit;
    }
}
