<?php

namespace Taper\Exception;

use InvalidArgumentException;

/**
 * Container Exception
 */
class TaperException extends InvalidArgumentException
{
    public function __construct($message = "", $code = 0) {
        parent::__construct($message, $code);
    }
}
