<?php

namespace Optit;

abstract class Entity {
  public function get($parameter) {
    $method = 'get' . $this->snakeToCamelCase($parameter);
    if (method_exists($this, $method)) {
      return $this->{$method}();
    }
    return false;
  }

  public function allowedValues($parameter) {
    $method = 'allowedValues' . $this->snakeToCamelCase($parameter);
    if (method_exists($this, $method)) {
      return $this->{$method}();
    }
    return false;
  }

  public function set($parameter, $value) {
    $method = 'set' . $this->snakeToCamelCase($parameter);
    if (method_exists($this, $method)) {
      $this->{$method}($value);
    }
  }

  public function validate($parameter, $value) {
    $method = 'validate' . $this->snakeToCamelCase($parameter);
    if (method_exists($this, $method)) {
      return $this->{$method}($value);
    }

    // Allow all if validation method does not exist.
    return true;
  }

  protected function snakeToCamelCase($val) {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
  }

  public function toArray() {
    $array = array();
    foreach ($this as $key=>$value) {
      $array[$key] = $value;
    }
    return $array;
  }
}
