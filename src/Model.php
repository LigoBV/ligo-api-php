<?php

namespace LigoApi;

use LigoApi\Client;

abstract class Model
{
  public static function client()
  {
    return \LigoApi\Client::httpClient();
  }
}
