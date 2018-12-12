<?php

namespace LigoApi;

use LigoApi\Client;
use LigoApi\Model;

class ContractTemplate extends Model
{
  public static function all($params = null, $opts = null)
  {
    $response = self::client()->request('get', '/api/contract_templates');
    $output = [];

    foreach ($response["contract_templates"] as $index => $contract_template) {
      array_push($output, new self($response["contract_templates"][$index]));
    }

    return $output;
  }

  public function __construct($params) 
  {
    $this->id = $params["id"];
    $this->name = $params["name"];
    $this->country = $params["country"];
    $this->language = $params["language"];
    $this->template_type = $params["template_type"];
    $this->created_at = $params["created_at"];
    $this->updated_at = $params["updated_at"];
  }
}
