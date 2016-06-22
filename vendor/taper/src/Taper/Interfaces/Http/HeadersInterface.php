<?php

namespace Taper\Interfaces\Http;

use Taper\Collection\CollectionInterface;

/**
 * Headers Interface
 */
interface HeadersInterface extends CollectionInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
