<?php

namespace Taper\Http;

use InvalidArgumentException,
    Taper\Interfaces\Http\CookiesInterface;

/**
 * Cookie helper
 */
class Cookies implements CookiesInterface
{
    /**
     * Cookies from HTTP request
     *
     * @var array
     */
    protected $requestCookies = [];

    /**
     * Cookies for HTTP response
     *
     * @var array
     */
    protected $responseCookies = [];

    /**
     * Default cookie properties
     *
     * @var array
     */
    protected $defaults = [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 值
        'value' => '',
        // cookie 有效域名
        'domain' => null,
        // cookie 保存路径
        'path' => null,
        // cookie 有效域名
        'expires' => null,
        // cookie 启用安全传输
        'secure' => false,
        // httponly设置
        'httponly' => false,
        // 是否使用 setcookie
        'setcookie' => true,
    ];

    /**
     * Create new cookies helper
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->defaults = array_merge($this->defaults, array_change_key_case($config));
    }

    /**
     * Set default cookie properties
     *
     * @param array $settings
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_replace($this->defaults, $settings);
    }

    /**
     * Get request cookie
     *
     * @param  string $name    Cookie name
     * @param  mixed  $default Cookie default value
     *
     * @return mixed Cookie value if present, else default
     */
    public function getHeader($name, $default = null)
    {
        return isset($this->requestCookies[$name]) ? $this->requestCookies[$name] : $default;
    }

    /**
     * Set response cookie
     *
     * @param string       $name  Cookie name
     * @param string|array $value Cookie value, or cookie properties
     */
    public function setHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = ['value' => (string)$value];
        }
        $this->responseCookies[$name] = array_replace($this->defaults, $value);
    }

    /**
     * Convert to `Set-Cookie` headers
     *
     * @return string[]
     */
    public function toHeaders()
    {
        $headers = [];
        foreach ($this->responseCookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }

        return $headers;
    }

    /**
     * Convert to `Set-Cookie` header
     *
     * @param  string $name       Cookie name
     * @param  array  $properties Cookie properties
     *
     * @return string
     */
    protected function toHeader($name, array $properties)
    {
        $result = urlencode($name) . '=' . urlencode($properties['value']);

        if (isset($properties['domain'])) {
            $result .= '; domain=' . $properties['domain'];
        }

        if (isset($properties['path'])) {
            $result .= '; path=' . $properties['path'];
        }

        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            } else {
                $timestamp = (int)$properties['expires'];
            }
            if ($timestamp !== 0) {
                $result .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (isset($properties['secure']) && $properties['secure']) {
            $result .= '; secure';
        }
        
        if (isset($properties['hostonly']) && $properties['hostonly']) {
            $result .= '; HostOnly';
        }

        return $result;
    }

    /**
     * Parse HTTP request `Cookie:` header and extract
     * into a PHP associative array.
     *
     * @param  string $header The raw HTTP request `Cookie:` header
     *
     * @return array Associative array of cookie names and values
     *
     * @throws InvalidArgumentException if the cookie data cannot be parsed
     */
    public static function parseHeader($header = null)
    {
        if (is_array($header) === true) {
            $header = isset($header[0]) ? $header[0] : '';
        }
        else if(is_null($header)) {
            $environment = new Environment($_SERVER);
            $headers = Headers::createFromEnvironment($environment);
            $header = $headers->get('Cookie', []);
        }

        if (is_string($header) === false) {
            throw new InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@\s*[;,]\s*@', $header);
        $cookies = [];

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }

    /**
     * 设置或者获取cookie作用域（前缀）
     * @param string $prefix
     * @return string|void
     */
    public function prefix($prefix = '')
    {
        if (empty($prefix)) {
            return $this->defaults['prefix'];
        }
        $this->defaults['prefix'] = $prefix;
    }

    /**
     * Cookie 设置、获取、删除
     *
     * @param string $name  cookie名称
     * @param mixed  $value cookie值
     * @param mixed  $option 可选参数 可能会是 null|integer|string
     *
     * @return mixed
     * @internal param mixed $options cookie参数
     */
    public function set($name, $value = '', $option = null)
    {
        // 参数设置(会覆盖黙认设置)
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge($this->defaults, array_change_key_case($option));
        } else {
            $config = $this->defaults;
        }
        $name = $config['prefix'] . $name;
        // 设置cookie
        if (is_array($value)) {
            array_walk_recursive($value, array($this, 'jsonFormatProtect'), 'encode');
            $value = 'taper:' . json_encode($value);
        }
        $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        if ($config['setcookie']) {
            if (isset($this->defaults['httponly']) && $this->defaults['httponly']) {
                ini_set('session.cookie_httponly', 1);
            }
            setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        }

        $_COOKIE[$name] = $value;
    }

    /**
     * Cookie获取
     * @param string $name cookie名称
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public function get($name, $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->defaults['prefix'];
        $name   = $prefix . $name;
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'taper:')) {
                $value = substr($value, 6);
                $value = json_decode($value, true);
                array_walk_recursive($value, array($this, 'jsonFormatProtect'), 'decode');
            }
            return $value;
        } else {
            return null;
        }
    }

    /**
     * Cookie删除
     * @param string $name cookie名称
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public function delete($name, $prefix = null)
    {
        $config = $this->defaults;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        $name   = $prefix . $name;
        if ($config['setcookie']) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        }
        // 删除指定cookie
        unset($_COOKIE[$name]);
    }

    /**
     * Cookie清空
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public function clear($prefix = null)
    {
        // 清除指定前缀的所有cookie
        if (empty($_COOKIE)) {
            return;
        }

        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $config = $this->defaults;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        if ($prefix) {
            // 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if ($config['setcookie']) {
                        setcookie($key, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                    }
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }

    private function jsonFormatProtect(&$val, $key, $type = 'encode')
    {
        if (!empty($val) && true !== $val) {
            $val = 'decode' == $type ? urldecode($val) : urlencode($val);
        }
    }
}
