<?php

namespace Drupal\calibr8_import\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Calibr8 Importer item annotation object.
 *
 * @see \Drupal\calibr8_import\Plugin\Calibr8ImporterManager
 * @see plugin_api
 *
 * @Annotation
 */
class Calibr8Importer extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The import table.
   *
   * @var string
   */
  public $import_table;

}
