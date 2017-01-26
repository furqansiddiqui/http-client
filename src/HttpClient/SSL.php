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
 * Class SSL
 * @package HttpClient
 */
class SSL
{
    /** @var bool */
    private $check;
    /** @var null|string */
    private $caBundle;
    /** @var null|string */
    private $caDir;
    /** @var null|string */
    private $crtFile;
    /** @var null|string */
    private $crtPassword;
    /** @var null|string */
    private $keyFile;
    /** @var null|string */
    private $keyPassword;

    /**
     * SSL constructor.
     */
    public function __construct()
    {
        $this->check    =   true;
    }

    /**
     *
     * @param bool $bool
     * @return SSL
     */
    public function check(bool $bool) : self
    {
        $this->check    =   $bool;
        return $this;
    }

    /**
     * Set path to CA bundle file or directory containing CA bundles
     *
     * @param string $path
     * @return SSL
     * @throws \HttpClientException
     */
    public function setCA(string $path) : self
    {
        if(!@is_readable($path)) {
            throw new \HttpClientException(
                'Provided CA bundle path or directory is NOT readable'
            );
        }

        if(@is_file($path)) {
            $this->caBundle =   $path;
            $this->caDir    =   null;
        } elseif(@is_dir($path)) {
            $this->caBundle =   null;
            $this->caDir    =   $path;
        }

        return $this;
    }

    /**
     * Set path to SSL certificate (PEM)
     *
     * @param string $path
     * @param string|null $password
     * @return SSL
     * @throws \HttpClientException
     */
    public function setCert(string $path, string $password = null) : self
    {
        if(!@is_readable($path) ||  !@is_file($path)) {
            throw new \HttpClientException(
                sprintf(
                    'SSL certificate file "%1$s" not readable in "%2$s',
                    basename($path),
                    dirname($path)
                )
            );
        }

        $this->crtFile  =   $path;
        if($password) {
            $this->crtPassword  =   $password;
        }

        return $this;
    }

    /**
     * Set path to SSL private key (PEM)
     *
     * @param string $path
     * @param string|null $password
     * @return SSL
     * @throws \HttpClientException
     */
    public function setKey(string $path, string $password = null) : self
    {
        if(!@is_readable($path) ||  !@is_file($path)) {
            throw new \HttpClientException(
                sprintf(
                    'SSL private key file "%1$s" not readable in "%2$s',
                    basename($path),
                    dirname($path)
                )
            );
        }

        $this->keyFile  =   $path;
        if($password) {
            $this->keyPassword  =   $password;
        }

        return $this;
    }

    /**
     * @param $ch
     * @throws \HttpClientException
     */
    public function register($ch)
    {
        // Verify param is a resource
        if(!is_resource($ch)) {
            throw new \HttpClientException('First parameter must be a valid cURL resource');
        }

        // Make sure cUrl can work with SSL
        if(!(curl_version()["features"] &   CURL_VERSION_SSL)) {
            throw new \HttpClientException('SSL support is unavailable');
        }

        // Bypass SSL check?
        if(!$this->check) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            return;
        }

        // Work with SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // CA Bundle
        if(isset($this->caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caBundle);
        } elseif(isset($this->caDir)) {
            curl_setopt($ch, CURLOPT_CAPATH, $this->caDir);
        }

        // Certificate and Key Files
        if(isset($this->crtFile)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->crtFile);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
            if(isset($this->crtPassword)) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->crtPassword);
            }
        }

        if(isset($this->keyFile)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->keyFile);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
            if(isset($this->keyPassword)) {
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->keyPassword);
            }
        }
    }
}