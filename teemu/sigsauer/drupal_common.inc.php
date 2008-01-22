<?php

function ip_address() {
  static $ip_address = NULL;

  if (!isset($ip_address)) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
  }

  return $ip_address;
}
