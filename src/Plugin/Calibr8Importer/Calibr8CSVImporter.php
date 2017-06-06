<?php

namespace Drupal\calibr8_import\Plugin\Calibr8Importer;


use Drupal\file\Entity\File;
use Drupal\calibr8_import\Exception\ImporterException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Drupal\calibr8_import\Plugin\Calibr8Importer\Calibr8ImporterBase;

abstract class Calibr8CSVImporter extends Calibr8ImporterBase {

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
      throw new ImporterException(t('CSV file @path does not exist.', array('@path' => $path)));
    }

    // get data.
    ini_set('auto_detect_line_endings',TRUE);
    $handle = fopen($path,'r');
    $first = TRUE;
    $keys = array();
    $data = array();
    while ( ($row = fgetcsv($handle, 0, ';') ) !== FALSE ) {

      if($first) {
        $first = FALSE;
        $keys = $row;
        continue;
      }
      if(is_array($row) && count($row) == count($keys)) {
        $index_row = array();
        foreach ($row as $i => $value) {
          $key = $keys[$i];
          $index_row[$key] = $value;

        }
        $data[] = $index_row;
      } else {
        throw new ImporterException("Incorrect import file format on line $line.");
      }
    }
    ini_set('auto_detect_line_endings',FALSE);

    return $data;
  }

}