<?php

use Drupal\calibr8_import\Exception\ImporterException;
use Drupal\calibr8_import\Importer\ImporterFactory;
use Drupal\calibr8_import\Importer\BaseImporter;

/**
 * Implements hook_drush_command().
 */
function calibr8_import_drush_command() {
  $items = array();
  $items['calibr8-import'] = [
    'description' => 'Start the Calibr8 import.',
    'drupal dependencies' => ['calibr8_import'],
    'arguments' => [
       'importer id' => 'The importer id.',
     ],
  ];
  return $items;
}

/**
 * Callback function drush_calibr8_import_import_data()
 */
function drush_calibr8_import($id = 'default') {
  $calibr8_importer_manager = \Drupal::service('plugin.manager.calibr8_importer');
  $importer = $calibr8_importer_manager->createInstance($id);
  calibr8_import_handle_drush_import($importer, $id);
}

/**
 * Do the actual import and handle error/messages.
 *
 * @param BaseImporter $importer
 * @param $type
 */
function calibr8_import_handle_drush_import($importer, $type) {
  try {
    $importer->Import();
    drush_print(t('The import has run successfully.'));
  }
  catch (ImporterException $exception) {
    // Some feedback for anyone running the drush command in terminal.
    drush_print($exception->getMessage());

    // Send email with error message and any other possible information.
    $site_name = \Drupal::config('system.site')->get('name');
    $subject = t('An error occurred while running the @type import on @site_name', array(
      '@type' => $type,
      '@site_name' => $site_name
    ));
    $message = t("An error occurred on @time while running the @type import on @site_name with the following message: @message", array(
      '@type' => $type,
      '@time' => date('l d/m/Y H:i:s'),
      '@site_name' => $site_name,
      '@message' => $exception->getMessage()
    ));

    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'calibr8_import';
    $key = 'import_error_notifier';
    $to = \Drupal::config('calibr8_import.settings')->get('email_to_notification');
    $params['subject'] = $subject;
    $params['message'] = $message;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== TRUE) {
      drush_print(t('FUBAR: there was a problem mailing the error message.'), 'error');
    } else {
      drush_print(t('An error message has been mailed.'));
    }
  }
}