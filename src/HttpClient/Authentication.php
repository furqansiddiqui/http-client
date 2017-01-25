<?php
declare(strict_types=1);

namespace HttpClient;

/**
 * Class Authentication
 * @package HttpClient
 */
class Authentication
{
    const BASIC =   1;
    const DIGEST    =   2;

    /** @var null|int */
    private $type;
    /** @var null|string */
    private $username;
    /** @var null|string */
    private $password;

    /**
     * Basic HTTP authentication
     *
     * @param string $username
     * @param string $password
     */
    public function basic(string $username, string $password)
    {
        $this->type =   self::BASIC;
        $this->username =   $username;
        $this->password =   $password;
    }

    /**
     * @param $ch
     * @throws \HttpClientException
     */
    public function register($ch)
    {
        if(!is_resource($ch)) {
            throw new \HttpClientException('First parameter must be a valid cURL resource');
        }

        switch ($this->type) {
            case self::BASIC:
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->username, $this->password));
                break;
        }
    }
}