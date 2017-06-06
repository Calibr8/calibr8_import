<?php

namespace Drupal\calibr8_import\Plugin\Calibr8Importer;

use Drupal\calibr8_import\Exception\ImporterException;
use Drupal\calibr8_import\Plugin\Calibr8Importer\Calibr8ImporterBase;

abstract class Calibr8XMLImporter extends Calibr8ImporterBase {

  /**
   * Get the import table.
   */
  protected abstract function getPath();
  
  /**
   * Get the import data.
   */
  protected function getData() {

    $path = $this->getPath();

    // Check if file exists.
    if(!file_exists($path)) {
      throw new ImporterException(t('XML file @path does not exist.', array('@path' => $path)));
    }

    // Let the XML parser use internal errors so we can collect them later if anything goes wrong.
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($path);
    if($xml === FALSE) {
      $error_message = "Failed loading XML:";
      foreach(libxml_get_errors() as $error) {
        $error_message.=  PHP_EOL . $error->message;
      }
      throw new ImporterException($error_message);
    }

    // XML -> JSON -> ARRAY
    $json = json_encode($xml);
    $array = json_decode($json, TRUE);
    return $array;
  }

}