<?php
/**
 * This file is a part of "furqansiddiqui/http-client" package.
 * https://github.com/furqansiddiqui/http-client
 *
 * Copyright (c) 2017. Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/http-client/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace HttpClient;

/**
 * Class Request
 * @package HttpClient
 */
class Request
{
    /** @var string|null */
    private $accept;
    /** @var string */
    private $url;
    /** @var string */
    private $method;
    /** @var array */
    private $data;
    /** @var string|null */
    private $dataEncoding;
    /** @var array */
    private $headers;
    /** @var bool */
    private $https;
    /** @var SSL */
    private $ssl;
    /** @var Authentication */
    private $authentication;

    /**
     * Request constructor.
     * @param string $method
     * @param string $url
     * @throws \HttpClientException
     */
    public function __construct(string $url = null, string $method = "GET")
    {
        // Check request method
        $method =   strtoupper($method);
        if(!in_array($method, ["GET","POST","PUT","DELETE"])) {
            throw new \HttpClientException(
                sprintf('"%1$s" is not a valid or unsupported HTTP request method', $method)
            );
        }

        $this->method   =   $method;
        $this->headers  =   [];
        $this->setUrl($url ?? "http://localhost");
        $this->ssl  =   new SSL();
        $this->ssl->check(true);
        $this->authentication   =   new Authentication();
    }

    /**
     * @param string $url
     * @return Request
     * @throws \HttpClientException
     */
    public function setUrl(string $url) : self
    {
        if(!preg_match('/^(http|https):\/\/.*$/', $url)) {
            throw new \HttpClientException('Invalid URL');
        }

        $this->url  =   $url;
        $this->https    =   strtolower(substr($url, 0, 5))  === "https" ? true : false;
        return $this;
    }

    /**
     * @return array
     */
    public function getOpts() : array
    {
        return [
            "accept"    =>  $this->accept,
            "method"    =>  $this->method,
            "url"   =>  $this->url,
            "https" =>  $this->https
        ];
    }

    /**
     * Check SSL integrity?
     * Pass boolean FALSE as first argument if you are making request to HTTPS url that does not have a
     * valid SSL certificate
     *
     * @param bool $check
     * @return Request
     */
    public function checkSSL(bool $check) : self
    {
        $this->ssl->check($check);
        return $this;
    }

    /**
     * @return SSL
     */
    public function ssl() : SSL
    {
        return $this->ssl;
    }

    /**
     * Set request payload
     * First argument must be an Array while second argument can be optionally passed with desired encoding to be
     * performed on given payload.
     *
     * Currently only "json" is supported.
     *
     * @param array $data
     * @param string|null $encoding
     * @return Request
     * @throws \HttpClientException
     */
    public function payload(array $data, string $encoding = null) : self
    {
        if(!is_null($encoding)) {
            switch (strtolower($encoding)) {
                case "json":
                    $this->dataEncoding =   "json";
                    break;
                default:
                    throw new \HttpClientException(
                        sprintf('"%1$s" is not acceptable payload encoding', $encoding)
                    );
                    break;
            }
        }

        $this->data =   $data;
        return $this;
    }

    /**
     * This method is alias of payload() method
     *
     * @param array $data
     * @param string|null $encoding
     * @return Request
     * @see payload
     */
    public function setData(array $data, string $encoding = null) : self
    {
        return $this->payload($data, $encoding);
    }

    /**
     * @return bool
     */
    public function hasPayload() : bool
    {
        return is_array($this->data);
    }

    /**
     * @return string
     */
    public function getPayload() : string
    {
        $payload    =   is_array($this->data) ? $this->data : [];

        switch($this->dataEncoding) {
            case "json":
                $payload    =   json_encode($payload);
                $this->setHeader("Content-Type", "application/json; charset=utf-8");
                $this->setHeader("Content-Length", strval(strlen($payload)));
                break;
            default:
                $payload    =   http_build_query($payload);
                break;
        }

        return $payload;
    }

    /**
     * Set a HTTP header
     *
     * @param string $header
     * @param string $value
     * @return Request
     */
    public function setHeader(string $header, string $value) : self
    {
        $this->headers[$header] =   $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        $headers    =   [];
        foreach($this->headers as $key => $value) {
            $headers[]  =   sprintf('%1$s: %2$s', $key, $value);
        }

        return $headers;
    }

    /**
     * Set "accept" header and enforce it
     * If response content type header does not match with expected data type set here, an exception will be thrown
     *
     * Currently only "json" is supported.
     *
     * @param string $format
     * @return Request
     * @throws \HttpClientException
     */
    public function accept(string $format) : self
    {
        $format =   explode("/", $format);
        $format =   $format[1] ?? $format[0];

        switch($format) {
            case "json":
                $this->setHeader("Accept", "application/json; charset=utf-8");
                $this->accept   =   "application/json";
                break;
            default:
                throw new \HttpClientException(
                    sprintf('"%1$s" is not acceptable data type/format', $format)
                );
        }

        return $this;
    }

    /**
     * @return Authentication
     */
    public function authentication() : Authentication
    {
        return $this->authentication;
    }

    /**
     * @return Response
     */
    public function send() : Response
    {
        return \HttpClient::Send($this);
    }
}