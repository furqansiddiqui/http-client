<?php
declare(strict_types=1);

namespace HttpClient;

use HttpClient\Exception\JSON_RPC_Exception;

/**
 * Class JSON_RPC
 * @package HttpClient
 * @property-read string $specification
 * @property-read string $version
 * @property-read string $url
 */
class JSON_RPC
{
    public const VERSIONS = ["1.0", "2.0"];

    /** @var string */
    private $_spec;

    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var null|Authentication */
    private $auth;
    /** @var null|SSL */
    private $ssl;

    /**
     * JSON_RPC constructor.
     * @param string $spec
     * @throws JSON_RPC_Exception
     */
    public function __construct(string $spec = "1.0")
    {
        if (!in_array($spec, self::VERSIONS)) {
            throw new JSON_RPC_Exception('Invalid JSON RPC specification/version');
        }

        $this->_spec = $spec;
    }

    /**
     * @param string $prop
     * @return string|null
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "specification":
            case "version":
                return $this->_spec;
            case "url":
                $protocol = $this->ssl ? "https" : "http";
                return sprintf('%s://%s:%d', $protocol, $this->host, $this->port);
        }

        return null;
    }

    /**
     * @param string $method
     * @param $arguments
     * @throws JSON_RPC_Exception
     */
    public function __call(string $method, $arguments)
    {
        switch ($method) {
            case "prepare_req_objs":
                $req = $arguments[0] ?? null;
                if ($req instanceof Request) {
                    if ($this->auth) {
                        call_user_func_array([$req, "obj_auth"], [$this->auth]);
                    }

                    if ($this->ssl) {
                        call_user_func_array([$req, "obj_ssl"], [$this->ssl]);
                    }
                }
                break;
        }

        throw new JSON_RPC_Exception('Cannot call inaccessible method');
    }

    /**
     * @param string|null $host
     * @param int|null $port
     * @return json_RPC
     */
    public function server(string $host = null, int $port = null): self
    {
        $this->host = $host;
        $this->port = $port;
        return $this;
    }

    /**
     * @return SSL
     * @throws Exception\SSLException
     */
    public function ssl(): SSL
    {
        if (!$this->ssl) {
            $this->ssl = new SSL();
        }

        return $this->ssl;
    }

    /**
     * @return SSL
     * @throws Exception\SSLException
     */
    public function tls(): SSL
    {
        return $this->ssl();
    }

    /**
     * @return Authentication
     */
    public function authentication(): Authentication
    {
        if (!$this->auth) {
            $this->auth = new Authentication();
        }

        return $this->auth;
    }

    /**
     * @param string $endpoint
     * @return JSON_RPC\Request
     * @throws Exception\JSON_RPC_RequestException
     */
    public function get(string $endpoint): JSON_RPC\Request
    {
        return new JSON_RPC\Request($this, $endpoint, 'GET');
    }

    /**
     * @param string $endpoint
     * @return JSON_RPC\Request
     * @throws Exception\JSON_RPC_RequestException
     */
    public function post(string $endpoint): JSON_RPC\Request
    {
        return new JSON_RPC\Request($this, $endpoint, 'POST');
    }

    /**
     * @param string $endpoint
     * @return JSON_RPC\Request
     * @throws Exception\JSON_RPC_RequestException
     */
    public function put(string $endpoint): JSON_RPC\Request
    {
        return new JSON_RPC\Request($this, $endpoint, 'PUT');
    }

    /**
     * @param string $endpoint
     * @return JSON_RPC\Request
     * @throws Exception\JSON_RPC_RequestException
     */
    public function delete(string $endpoint): JSON_RPC\Request
    {
        return new JSON_RPC\Request($this, $endpoint, 'DELETE');
    }
}