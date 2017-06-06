<?php

namespace Drupal\calibr8_import\Plugin\Calibr8Importer;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Database\connection;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\node\Entity\Node;
use Drupal\calibr8_import\Controller\calibr8ImportController;
use Drupal\calibr8_import\Exception\ImporterException;

/**
 * Base class for Calibr8 Importer plugins.
 */
abstract class Calibr8ImporterBase extends PluginBase implements Calibr8ImporterInterface {

  protected $start_timestamp;
  protected $db;
  protected $import_table;
  protected $database;

  /**
   * Get the import data.
   */
  protected abstract function getData();

  /**
   * Get the id for a row.
   */
  protected abstract function getIdForRow($row);

  /**
   * Actually do the import.
   */
  protected abstract function doImport();
  
  /**
   * Calibr8ImporterBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
  	parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->start_timestamp = time();
    $this->import_table = $plugin_definition['import_table'];
    $this->db = \Drupal::service('database');;
  }

  /**
   * {@inheritdoc}
   */
  public function Import() {

    // first, get the data from a source
    // second, write that data to the import table
    $transaction = $this->db->startTransaction();
    try {
      $data = $this->getData();
      foreach ($data as $index => &$row) {
        $id = $this->getIdForRow($row);
        $extra_columns = $this->getExtraColumnsValues($row);
        $sha = hash('sha256', serialize($row));
        $this->checkDatabaseEntry($this->import_table, $id, $sha, $row, $extra_columns);
      }
      // Set deleted to 1 where last_import < $this->start_timestamp.
      $this->invalidate($this->import_table);
    } catch (Exception $ex) {
      // Rollback if an error occurs.
      $transaction->rollback();
      throw new ImporterException($ex->getMessage());
    }

    // third, proces the data from the import table, and do the actual import logic
    $this->doImport();
  }

  /**
   * Get extra columns.
   */
  protected function getExtraColumnsValues($row) {
    return array();
  }

  /**
   * Return SHA for $id in $table.
   */
  protected function getSha($table, $id, $extra_conditions = array()) {
    $db_sha_calibruery = $this->db->select($table, 'st')
      ->fields('st', array('sha'))
      ->condition('id', $id);
    if(!empty($extra_conditions)) {
      foreach($extra_conditions as $key => $value) {
        $db_sha_calibruery = $db_sha_calibruery->condition($key, $value);
      }
    }
    return $db_sha_calibruery->execute()->fetchField();
  }

  /**
   * Set rows to deleted in $table for data that doesn't exist anymore.
   */
  protected function invalidate($table) {
    $this->db->update($table)
      ->fields(array(
        'deleted' => 1,
      ))
      ->condition('last_import', $this->start_timestamp, '<')
      ->execute();
  }

  /**
   * The logic to create/update an entity based on the entry in the custom table.
   */
  protected function checkDatabaseEntry($table, $id, $sha, $entity, $extra_columns = array(), $extra_conditions = array()) {
    $db_sha = $this->getSha($table, $id, $extra_conditions);
    // Insert new entity.
    if (empty($db_sha)) {
      $record = array(
        'id'          => $id,
        'data'        => serialize($entity),
        'created'     => $this->start_timestamp,
        'changed'     => $this->start_timestamp,
        'last_import' => $this->start_timestamp,
        'deleted'     => 0,
        'sha'         => $sha
      );
      if(!empty($extra_columns)) {
        $this->arraySpliceAssoc($record, 1, 0, $extra_columns);
      }
      $this->db->insert($table)
        ->fields($record)
        ->execute();
    } // Entity has not changed, update last_import timestamp.
    else if ($sha == $db_sha) {
      $this->db->update($table)
        ->fields(array(
          'last_import' => $this->start_timestamp,
          'deleted'     => 0
        ))
        ->condition('id', $id)
        ->execute();
    } // The entity has changed, update it.
    else {
      $record = array(
        'id'          => $id,
        'data'        => serialize($entity),
        'changed'     => $this->start_timestamp,
        'last_import' => $this->start_timestamp,
        'deleted'     => 0,
        'sha'         => $sha
      );
      if(!empty($extra_columns)) {
        $this->arraySpliceAssoc($record, 1, 0, $extra_columns);
      }
      $update_calibruery = $this->db->update($table)
        ->fields($record)
        ->condition('id', $id);
      if(!empty($extra_conditions)) {
        foreach($extra_conditions as $key => $value) {
          $update_calibruery = $update_calibruery->condition($key, $value);
        }
      }
      $update_calibruery->execute();
    }
  }

  /**
   * Insert a Database entry regardless of any logic.
   */
  protected function insertDatabaseEntry($table, $id, $sha, $entity, $extra_columns = array()) {
    $record = [
      'id'          => $id,
      'data'        => serialize($entity),
      'created'     => $this->start_timestamp,
      'changed'     => $this->start_timestamp,
      'last_import' => $this->start_timestamp,
      'deleted'     => 0,
      'sha'         => $sha,
    ];
    if(!empty($extra_columns)) {
      $this->arraySpliceAssoc($record, 1, 0, $extra_columns);
    }
    $this->db->insert($table)
      ->fields($record)
      ->execute();
  }

  /**
   * Helper function to merge assoc arrays.
   */
  protected function arraySpliceAssoc(&$input, $offset, $length, $replacement) {
    $replacement = (array)$replacement;
    $key_indices = array_flip(array_keys($input));
    if (isset($input[$offset]) && is_string($offset)) {
      $offset = $key_indices[$offset];
    }
    if (isset($input[$length]) && is_string($length)) {
      $length = $key_indices[$length] - $offset;
    }

    $input = array_slice($input, 0, $offset, TRUE)
      + $replacement
      + array_slice($input, $offset + $length, NULL, TRUE);
  }

  /**
   * get created rows
   */
  protected function getCreated() {
    $new_ids = $this->db->select($this->import_table, 'it')
      ->fields('it', array('id', 'data'))
      ->condition('created', $this->start_timestamp)
      ->execute()->fetchAssoc();

      return $new_ids;
  }

  /**
   * Get updated rows
   */
  protected function getUpdated() {
    $updated_ids = $this->db->select($this->import_table, 'it')
      ->fields('it', array('id', 'data'))
      ->condition('created', $this->start_timestamp, '!=')
      ->condition('changed', $this->start_timestamp)
      ->condition('deleted', 0)
      ->execute()->fetchAssoc();

      return $updated_ids;
  }

  /**
   * Get deleted rows
   */
  protected function getDeleted() {
    $ids = $this->db->select($this->import_table, 'it')
      ->fields('it', array('id', 'data'))
      ->condition('deleted', '1')
      ->execute()->fetchAssoc();

    return $ids;
  }

  /**
   * Remove deleted rows from the import table
   */
  protected function removeDeleted() {
    // Delete entries where deleted = 1.
    $this->db->delete($this->import_table)
      ->condition('deleted', '1')
      ->execute();
  }

}
