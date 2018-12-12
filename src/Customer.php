<?php

namespace LigoApi;

use LigoApi\Client;
use LigoApi\Model;

class Customer extends Model
{
  public static function all($params = null, $opts = null)
  {
    $response = self::client()->request('get', '/api/customers');
    $output = [];

    foreach ($response["customers"] as $index => $customer) {
      array_push($output, new self($response["customers"][$index]));
    }

    return $output;
  }

  public static function find($id)
  {
    $url = '/api/customers/' . $id;
    $response = self::client()->request('get', $url);

    return new self($response["customer"]);
  }

  public static function create($params)
  {
    $response = self::client()->request('post', '/api/customers', array("customer" => $params));

    return new self($response["customer"]);
  }

  public function __construct($params) 
  {
    $this->id = $params["id"];
    $this->name = $params["name"];
    $this->created_at = $params["created_at"];
    $this->updated_at = $params["updated_at"];
  }
}
