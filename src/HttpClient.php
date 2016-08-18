<?php
declare(strict_types=1);

/**
 * Class HttpClient
 */
class HttpClient
{
    const VERSION   =   "0.1.0";

    /**
     * @param \HttpClient\Request $request
     * @return \HttpClient\Response
     * @throws HttpClientException
     */
    public static function Request(\HttpClient\Request $request) : \HttpClient\Response
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