<?php

namespace LigoApi;

use LigoApi\Ligo;
use LigoApi\Error;

// cURL constants are not defined in PHP < 5.5
//
// PSR2 requires all constants be upper case. Sadly, the CURL_SSLVERSION
// constants do not abide by those rules.
// Note the values 1 and 6 come from their position in the enum that
// defines them in cURL's source code.

if (!defined('CURL_SSLVERSION_TLSv1')) {
    define('CURL_SSLVERSION_TLSv1', 1);
}
if (!defined('CURL_SSLVERSION_TLSv1_2')) {
    define('CURL_SSLVERSION_TLSv1_2', 6);
}
// @codingStandardsIgnoreEnd
if (!defined('CURL_HTTP_VERSION_2TLS')) {
    define('CURL_HTTP_VERSION_2TLS', 4);
}


class Requester
{
  const DEFAULT_TIMEOUT = 80;
  const DEFAULT_CONNECT_TIMEOUT = 30;

  private $timeout = self::DEFAULT_TIMEOUT;
  private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

  public function request($method, $absUrl, $headers, $params)
  {

    $fullUrl = Ligo::$apiUrl . $absUrl;

    $rheaders = []; // new Util\CaseInsensitiveArray();
    $headerCallback = function ($curl, $header_line) use (&$rheaders) {
        // Ignore the HTTP request line (HTTP/1.1 200 OK)
        if (strpos($header_line, ":") === false) {
            return strlen($header_line);
        }
        list($key, $value) = explode(":", trim($header_line), 2);
        $rheaders[trim($key)] = trim($value);
        return strlen($header_line);
    };

    // By default for large request body sizes (> 1024 bytes), cURL will
    // send a request without a body and with a `Expect: 100-continue`
    // header, which gives the server a chance to respond with an error
    // status code in cases where one can be determined right away (say
    // on an authentication problem for example), and saves the "large"
    // request body from being ever sent.
    //
    // Unfortunately, the bindings don't currently correctly handle the
    // success case (in which the server sends back a 100 CONTINUE), so
    // we'll error under that condition. To compensate for that problem
    // for the time being, override cURL's behavior by simply always
    // sending an empty `Expect:` header.
    array_push($headers, 'Expect: ');
    array_push($headers, 'User-Agent: ligo-php-client');



    $opts = [];

    if ($method == 'get') {
      $opts[CURLOPT_HTTPGET] = 1;
      // if (count($params) > 0) {
        //   $encoded = Util\Util::encodeParameters($params);
          // $absUrl = "$absUrl?$encoded";
      //  }
    } elseif ($method == 'post') {
      array_push($headers, 'Content-Type: application/json');
      $opts[CURLOPT_POST] = 1;
      $opts[CURLOPT_POSTFIELDS] = json_encode($params);
    } elseif ($method == 'delete') {
      // implement me
    } elseif ($method == 'put') {
      // implement me
    } else {
      throw new Error\Api("Unrecognized method $method");
    }


    // $absUrl = Util\Util::utf8($absUrl);
    $opts[CURLOPT_URL] = $fullUrl;
    $opts[CURLOPT_RETURNTRANSFER] = true;
    $opts[CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
    $opts[CURLOPT_TIMEOUT] = $this->timeout;
    $opts[CURLOPT_HEADERFUNCTION] = $headerCallback;
    $opts[CURLOPT_HTTPHEADER] = $headers;

    // For HTTPS requests, enable HTTP/2, if supported
    $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;

    list($rbody, $rcode) = $this->executeRequestWithRetries($opts, $fullUrl);

    return [$rbody, $rcode];
  }

  private function executeRequestWithRetries($opts, $absUrl)
  {
      $numRetries = 0;

      while (true) {
          $rcode = 0;
          $errno = 0;

          $curl = curl_init();
          curl_setopt_array($curl, $opts);
          $rbody = curl_exec($curl);

          if ($rbody === false) {
              $errno = curl_errno($curl);
              $message = curl_error($curl);
          } else {
              $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
          }

          curl_close($curl);

          if ($this->shouldRetry($errno, $rcode, $numRetries)) {
              $numRetries += 1;
              $sleepSeconds = 1;
              usleep(intval($sleepSeconds * 1000000));
          } else {
              break;
          }
      } // end retry loop

      if ($rbody === false) {
          $this->handleCurlError($absUrl, $errno, $message, $numRetries);
      }

      $converted_body = json_decode($rbody, true);

      return [$converted_body, $rcode];
  }

  private function handleCurlError($url, $errno, $message, $numRetries)
  {
    switch ($errno) {
      case CURLE_COULDNT_CONNECT:
      case CURLE_COULDNT_RESOLVE_HOST:
      case CURLE_OPERATION_TIMEOUTED:
          $msg = "Could not connect to Ligo ($url).  Please check your "
           . "internet connection and try again. "
           . "If this problem persists,";
          break;
      case CURLE_SSL_CACERT:
      case CURLE_SSL_PEER_CERTIFICATE:
          $msg = "Could not verify Ligo's SSL certificate.  Please make sure "
           . "that your network is not intercepting certificates.  "
           . "(Try going to $url in your browser.)  "
           . "If this problem persists,";
          break;
      default:
          $msg = "Unexpected error communicating with Ligo.  "
           . "If this problem persists,";
    }

    $msg .= " let us know at support@ligo.nl.";
    $msg .= "\n\n(Network error [errno $errno]: $message)";

    if ($numRetries > 0) {
        $msg .= "\n\nRequest was retried $numRetries times.";
    }
    throw new Error\ApiConnection($msg);
  }


  private function shouldRetry($errno, $rcode, $numRetries)
  {
    if ($numRetries >= 3) {
        return false;
    }
    // Retry on timeout-related problems (either on open or read).
    if ($errno === CURLE_OPERATION_TIMEOUTED) {
        return true;
    }
    // Destination refused the connection, the connection was reset, or a
    // variety of other connection failures. This could occur from a single
    // saturated server, so retry in case it's intermittent.
    if ($errno === CURLE_COULDNT_CONNECT) {
        return true;
    }
    // 409 conflict
    if ($rcode === 409) {
        return true;
    }
    return false;
  }

}
