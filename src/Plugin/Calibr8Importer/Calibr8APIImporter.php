<?php

namespace Drupal\calibr8_import\Plugin\Calibr8Importer;

use Drupal\calibr8_import\Exception\ImporterException;
use Drupal\calibr8_import\Plugin\Calibr8Importer\Calibr8ImporterBase;

abstract class Calibr8APIImporter extends Calibr8ImporterBase {

  /**
   * Get api url
   */
  protected abstract function getApiUrl();

  /**
   * Perform a curl call and return the JSON decoded result or FALSE if the curl did not return JSON.
   *
   * @param $relative_url
   * @return mixed
   */
  protected function call($relative_url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->getApiUrl() . $relative_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "X-IDS-UserId: id (number)",
      "X-IDS-ProfileId: id (number)",
//      "Accept: application/json",
      "TabId: dnn Tab Id",
      "ModuleId: dnn Module Id"
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    $decoded_response = json_decode($response, TRUE);

    // JSON decode and return result, if $response was not a JSON string, return FALSE.
    if(json_last_error() == JSON_ERROR_NONE) {
      return $decoded_response;
    }
    else {
      return FALSE;
    }
  }

}