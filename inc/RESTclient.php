<?php
/**
 * @file
 * Contains \Optit\RESTclient.
 */

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

  public function get($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'GET', $urlParams, $postParams, $format);
  }

  public function post($route, $urlParams = NULL, $postParams = NULL, $options = array(), $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'POST', $urlParams, $postParams, $format, $options);
  }

  public function put($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'PUT', $urlParams, $postParams, $format);
  }

  public function delete($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'DELETE', $urlParams, $postParams, $format);
  }


  private function drupalHTTPNightmare($route, $method = 'GET', $urlParams = NULL, $postParams = NULL, $format = 'json', $options = array()) {
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
        // Due to fact that some callbacks return broken json documents, i need to handle situation where response is 200, but
        // details are broken.
        $decoded = json_decode($data, TRUE);
        if ($decoded === NULL) {
          return TRUE;
        }
        return $decoded;
      case "xml":
        // @todo: Another relic od D7. It is difficult to inject the dependency in Drupal 7...
        return XML2Array::createArray($data);
        break;
    }
    return TRUE;
  }

  private function handleError($response) {
    //dsm($response);
    return FALSE;
  }
}
