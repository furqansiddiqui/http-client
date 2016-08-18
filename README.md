#http-client

Straightforward HTTP client based on PHP's Client URL Library

# Prerequisites

- PHP 7+
- cURL and JSON extensions installed and enabled

# Installation

_Installation via composer will be added soon_

# Usage
This library supports `GET`,`POST`,`PUT`,`DELETE` HTTP methods

Here are few examples of using this library:

### Simple GET Request

Perform a simple GET request and retrieve HTTP response code

```php
$req    =   \HttpClient\Request::Get("https://some-domain.com");
$res    =   HttpClient::Request($req);
echo $res->responseCode(); // 200
```

### POSTing some data

```php
$req    =   \HttpClient\Request::Post("https://some-domain.com/app/submit")
    ->payload(["foo" => "bar"]);
$res    =   HttpClient::Request($req);

var_dump($res->responseCode()); // Response code
var_dump($res->getHeader("content-type")); // Content-Type header
var_dump($res->getAllHeaders()); // Get all received headers
var_dump($res->getBody()); // Body
```

### Working with a RESTful JSON API

Lets assume you're working with a RESTful JSON api where you have to send a `PUT` request with `JSON` data and you expect `JSON` data in response.

```php
$req    =   \HttpClient\Request::Put("https://api.some-domain.com/endpoint")
    ->setHeader("Authorization", "Bearer <SOME-TOKEN>") // Authorization header?
    ->payload($data, "json") // Set payload and specify desired encoding
    ->accept("json"); // Expect response in JSON encoding, an exception will be thrown otherwise

$res    =   HttpClient::Request($req);
var_dump($res->responseCode()); // Response code
var_dump($res->getBody()); // Array will be returned, after decoding JSON response
```