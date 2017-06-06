<?php

namespace Drupal\calibr8_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\calibr8_import\Importer\ImporterFactory;

class SettingsForm extends ConfigFormBase  {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['calibr8_import.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calibr8_import_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('calibr8_import.settings');
    
    // Email addresses to which to sent error notifications.
    $form['calibr8_import_email_to_notification'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email to'),
      '#size' => 120,
      '#description' => $this->t('Add email addresses to which error notifications are sent in case something goes wrong while importing. Seperate multiple addresses can be added with the combination of a comma and a space (, ).'),
      '#default_value' => $config->get('email_to_notification'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('calibr8_import.settings');
    $config->set('email_to_notification', $form_state->getValue('calibr8_import_email_to_notification'))->save();

    parent::submitForm($form, $form_state);
  }
}