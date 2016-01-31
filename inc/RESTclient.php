<?php

namespace Optit;

use LSS\XML2Array;

class RESTclient {
  private $username;
  private $password;
  private $apiEndpoint;

  public function __construct($username, $password, $apiEndpoint) {
    $this->username = $username;
    $this->password = $password;
    $this->apiEndpoint = $apiEndpoint;
  }

  public function get($route, $urlParams = null, $postParams = null, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'GET', $urlParams, $postParams, $format);
  }

  public function post($route, $urlParams = null, $postParams = null, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'POST', $urlParams, $postParams, $format);
  }

  public function put($route, $urlParams = null, $postParams = null, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'PUT', $urlParams, $postParams, $format);
  }

  public function delete($route, $urlParams = null, $postParams = null, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'DELETE', $urlParams, $postParams, $format);
  }


  private function drupalHTTPNightmare($route, $method = 'GET', $urlParams = null, $postParams = null, $format = 'json', $options = array()) {
    $url = "http://{$this->username}:{$this->password}@{$this->apiEndpoint}/{$route}.{$format}";

    $options['method'] = $method;

    if ($urlParams) {
      $url .= "?" . $this->mergeParams($urlParams);
    }

    if ($postParams) {
      $options['data'] = $this->mergeParams($postParams);
    }

    $response = drupal_http_request($url, $options);

    if ($response->code == 200) {
      return $this->decodeData($response->data, $format);
    }
    else {
      return $this->handleError($response);
    }
  }

  private function mergeParams($params) {
    $param = array();
    foreach ($params as $key => $value) {
      $param[] = $key . '=' . $value;
    }
    return implode('&', $param);
  }

  private function decodeData($data, $format) {
    switch ($format) {
      case "json":
        return json_decode($data, true);
      case "xml":
        // @todo: Another relic od D7. It is difficult to inject the dependency in Drupal 7...
        return XML2Array::createArray($data);
        break;
    }
    return false;
  }

  private function handleError($response) {
    dsm($response);
    return false;
  }
}
