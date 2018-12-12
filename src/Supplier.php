<?php

namespace LigoApi;

use LigoApi\Client;
use LigoApi\Model;

class Supplier extends Model
{
  public static function all($params = null, $opts = null)
  {
    $response = self::client()->request('get', '/api/suppliers');
    $output = [];

    foreach ($response["suppliers"] as $index => $supplier) {
      array_push($output, new self($response["suppliers"][$index]));
    }

    return $output;
  }

  public static function find($id)
  {
    $url = '/api/suppliers/' . $id;
    $response = self::client()->request('get', $url);

    return new self($response["supplier"]);
  }

  public static function create($params)
  {
    $response = self::client()->request('post', '/api/suppliers', array("supplier" => $params));

    return new self($response["supplier"]);
  }

  public function __construct($params) 
  {
    $this->id = $params["id"];
    $this->name = $params["name"];
    $this->created_at = $params["created_at"];
    $this->updated_at = $params["updated_at"];
  }
}
