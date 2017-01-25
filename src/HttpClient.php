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

/**
 * Class HttpClient
 */
class HttpClient
{
    const VERSION   =   "0.2.0";

    /**
     * Initialize a new GET request
     *
     * @param string $url
     * @return \HttpClient\Request
     */
    public static function Get(string $url) : \HttpClient\Request
    {
        return new \HttpClient\Request($url, "GET");
    }

    /**
     * Initialize a new POST request
     *
     * @param string $url
     * @return \HttpClient\Request
     */
    public static function Post(string $url) : \HttpClient\Request
    {
        return new \HttpClient\Request($url, "POST");
    }

    /**
     * Initialize a new PUT request
     *
     * @param string $url
     * @return \HttpClient\Request
     */
    public static function Put(string $url) : \HttpClient\Request
    {
        return new \HttpClient\Request($url, "PUT");
    }

    /**
     * Initialize a new DELETE request
     *
     * @param string $url
     * @return \HttpClient\Request
     */
    public static function Delete(string $url) : \HttpClient\Request
    {
        return new \HttpClient\Request($url, "DELETE");
    }

    /**
     * @param \HttpClient\Request $request
     * @return \HttpClient\Response
     * @throws HttpClientException
     */
    public static function Send(\HttpClient\Request $request) : \HttpClient\Response
    {
        self::Test(); // Prerequisites Check
        $opts   =   $request->getOpts();

        $ch =   curl_init(); // Init cURL handler
        curl_setopt($ch, CURLOPT_URL, $opts["url"]); // Set URL

        // SSL
        if($opts["secure"]) {
            if(!(curl_version()["features"] &   CURL_VERSION_SSL)) {
                throw new HttpClientException('SSL support is unavailable');
            }

            if($opts["checkSSL"]    === false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            }
        }

        // Request Method & Payload
        switch($opts["method"]) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts["method"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getPayload());
                break;
        }

        // Headers
        $headers    =   $request->getHeaders();
        if(count($headers)  >   0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Authentication
        $request->authentication()->register($ch);

        // Prepare response
        $response   =   new \HttpClient\Response();

        // Finalise request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $headerLine) use ($response) {
            $response->writeHeader($headerLine);
            return strlen($headerLine);
        });

        $body   =   curl_exec($ch); // Execute cURL request
        $error  =   [
            curl_errno($ch),
            curl_error($ch)
        ];

        curl_close($ch); // Close cURL handle

        // Check if there was an error
        if($body    === false   ||  $error[0]    >   0   ||  !empty($error[1])) {
            throw new HttpClientException(
                sprintf('[%1$d] %2$s', $error[0], $error[1])
            );
        }

        // Write body
        $bodyType   =   strtolower(trim(explode(";", $response->getHeader("Content-Type"))[0]));
        $response->write($body, $bodyType);

        // Content Type with ACCEPT header
        if(isset($opts["accept"])   &&  $opts["accept"] !== $bodyType) {
            throw new HttpClientException(
                sprintf('Expecting response in "%1$s", got "%2$s"', $opts["accept"], $bodyType)
            );
        }

        return $response;
    }

    /**
     * Prerequisites Check
     * @return bool
     * @throws HttpClientException
     */
    public static function Test() : bool
    {
        // Curl
        if(!extension_loaded("curl")) {
            throw new HttpClientException('Required extension "curl" is unavailable');
        }

        // Json
        if(!function_exists("json_encode")) {
            throw new HttpClientException('Required extension "json" is unavailable');
        }

        return true;
    }
}