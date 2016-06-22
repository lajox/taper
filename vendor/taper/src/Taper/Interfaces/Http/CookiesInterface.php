<?php

namespace Taper\Interfaces\Http;

/**
 * Cookies Interface
 */
interface CookiesInterface
{
    public function getHeader($name, $default = null);
    public function setHeader($name, $value);
    public function toHeaders();
    public static function parseHeader($header);
}
