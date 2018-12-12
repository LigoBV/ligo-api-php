<?php

namespace LigoApi;

use LigoApi\Ligo;
use LigoApi\Requester;
use LigoApi\Error;

class Client
{
  private static $_httpClient;

  private $expiresIn;
  private $accessToken;
  private $createdAt;
  private $tokenType;

  public function request($method, $url, $params=null)
  {
    $this->refreshToken();

    $requester = new Requester();

    list($response, $code) = $requester->request($method, $url, $this->headers(), $params);

    return $response;
  }

  private function headers() 
  {
    $headers = [
      'Authorization: Bearer ' . $this->accessToken
    ];

    return $headers;
  }

  public static function httpClient()
  {
    if (!self::$_httpClient) {
      self::$_httpClient = new self(); 
    }

    return self::$_httpClient;
  }

  private function refreshToken()
  {
    if (!$this->accessToken || $this->expiredToken()) {
      $this->performRefreshToken();
    } 
  }

  private function expiredToken()
  {
    if (($this->createdAt + $this->expiresIn - 30) > time()) {
      return true; 
    } else {
      return false;
    }
  }

  private function performRefreshToken()
  {
    $requester = new Requester();

    list($response, $responseCode) = $requester->request(
      'post', 
      $this->tokenUrl(),
      [], 
      $this->tokenParams()
    );

    if($responseCode == 200) {
      $this->expiresIn = $response["expires_in"];
      $this->accessToken = $response["access_token"];
      $this->createdAt = $response["created_at"];
      $this->tokenType = $response["token_type"];
    } else {
      $msg = 'Your client credentials are not properly configured.';
      throw new Error\Authentication($msg);
    }

    return $response;
  }

  private function tokenParams()
  {
    $clientId = Ligo::$clientId;
    $clientSecret = Ligo::$clientSecret;

    if(!$clientId || !$clientSecret) {
      $msg = 'Your client credentials are not properly configured.';
      throw new Error\Authentication($msg);
    }

    return [
      "client_id" => $clientId,
      "client_secret" => $clientSecret,
      "grant_type" => "client_credentials"
    ];
  }

  private function tokenUrl()
  {
    return '/oauth/token';
  }
}
