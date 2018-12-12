<?php

namespace LigoApi;

use LigoApi\Client;
use LigoApi\Model;

class Contract extends Model
{
  public static function find($id)
  {
    $url = '/api/contracts/' . $id;
    $response = self::client()->request('get', $url);

    return new self($response["contract"]);
  }

  public static function create($params)
  {
    $response = self::client()->request('post', '/api/contracts', array("contract" => $params));

    return new self($response["contract"]);
  }

  public function __construct($params) 
  {
    $keys = array_keys($params);
    $desired_keys = array(
      "id", 
      "content",
      "documents_generating",
      "expiration_date",
      "pdf_document_url",
      "pdf_signed_document_url",
      "png_image_url",
      "word_document_url",
      "name",
      "created_at",
      "updated_at",
      "errors"
    );

    foreach($desired_keys as $desired_key){
       if(in_array($desired_key, $keys)) continue;  // already set
       $params[$desired_key] = '';
    }

    $this->id = $params["id"];
    $this->content = $params["content"];
    $this->errors = $params["errors"];
    $this->documents_generating = $params["documents_generating"];
    $this->expiration_date = $params["expiration_date"];
    $this->pdf_document_url = $params["pdf_document_url"];
    $this->pdf_signed_document_url = $params["pdf_signed_document_url"];
    $this->png_image_url = $params["png_image_url"];
    $this->word_document_url = $params["word_document_url"];
    $this->name = $params["name"];
    $this->created_at = $params["created_at"];
    $this->updated_at = $params["updated_at"];
  }
}
