<?php

namespace Drupal\calibr8_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\calibr8_import\Exception\ImporterException;
use Drupal\calibr8_import\Importer\ImporterFactory;

class ImportForm extends FormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  function getEditableConfigNames() {
    return [];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'calibr8_import_import_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * @todo build a form listing all importers, with their corresponding importer form
     */

    $config = $this->config('calibr8_import.settings');
    $calibr8_importer_manager = \Drupal::service('plugin.manager.calibr8_importer');
    $importers = $calibr8_importer_manager->getDefinitions();
    foreach ($importers as $id => $importer) {
      $form['container_' . $id] = array(
        '#type' => 'fieldset',
        '#title' => $importer['label'],
      );

      $importerPlugin = $calibr8_importer_manager->createInstance($id);
      $form['container_' . $id][] = $importerPlugin->buildConfigurationForm(array(), $form_state);


      $form['container_' . $id]['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#id' => $id,
      );
    }

    return $form;
  }

  /**
   * Form submission handler for import.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#id'];
    $calibr8_importer_manager = \Drupal::service('plugin.manager.calibr8_importer');
    $importerPlugin = $calibr8_importer_manager->createInstance($id);
    $importerPlugin->submitConfigurationForm($form, $form_state);
  }
}