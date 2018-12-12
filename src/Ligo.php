<?php

namespace LigoApi;

class Ligo
{
  public static $clientId;
  public static $clientSecret;
  public static $apiUrl = "https://www.ligo.nl";

  public static function setClientCredentials($clientId, $clientSecret)
  {
      self::$clientId = $clientId;
      self::$clientSecret = $clientSecret;
  }

  public static function setApiUrl($apiUrl)
  {
    self::$apiUrl = $apiUrl;
  }
}
