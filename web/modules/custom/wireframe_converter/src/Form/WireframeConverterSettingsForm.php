<?php

namespace Drupal\wireframe_converter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Wireframe Converter settings.
 */
class WireframeConverterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wireframe_converter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wireframe_converter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wireframe_converter.settings');

    $form['azure_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure Computer Vision API Settings'),
      '#description' => $this->t('Configure your Azure Computer Vision API credentials.'),
    ];

    $form['azure_settings']['azure_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Azure Computer Vision Endpoint'),
      '#description' => $this->t('The endpoint URL for your Azure Computer Vision resource (e.g., https://your-resource.cognitiveservices.azure.com/).'),
      '#default_value' => $config->get('azure_endpoint'),
      '#required' => TRUE,
    ];

    $form['azure_settings']['azure_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Azure Computer Vision API Key'),
      '#description' => $this->t('The API key for your Azure Computer Vision resource.'),
      '#default_value' => $config->get('azure_key'),
      '#required' => TRUE,
    ];

    $form['conversion_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conversion Settings'),
      '#description' => $this->t('Configure how wireframes are converted to Drupal content.'),
    ];

    $form['conversion_settings']['default_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Content Type'),
      '#description' => $this->t('The content type to use when creating pages from wireframes.'),
      '#options' => $this->getContentTypeOptions(),
      '#default_value' => $config->get('default_content_type') ?: 'page',
      '#required' => TRUE,
    ];

    $form['conversion_settings']['auto_publish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-publish converted content'),
      '#description' => $this->t('Automatically publish content created from wireframes.'),
      '#default_value' => $config->get('auto_publish') ?: TRUE,
    ];

    $form['conversion_settings']['create_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create blocks from wireframe sections'),
      '#description' => $this->t('Automatically create custom blocks for identified wireframe sections.'),
      '#default_value' => $config->get('create_blocks') ?: TRUE,
    ];

    $form['conversion_settings']['create_menus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create menu items from navigation'),
      '#description' => $this->t('Automatically create menu items for detected navigation elements.'),
      '#default_value' => $config->get('create_menus') ?: FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wireframe_converter.settings')
      ->set('azure_endpoint', $form_state->getValue('azure_endpoint'))
      ->set('azure_key', $form_state->getValue('azure_key'))
      ->set('default_content_type', $form_state->getValue('default_content_type'))
      ->set('auto_publish', $form_state->getValue('auto_publish'))
      ->set('create_blocks', $form_state->getValue('create_blocks'))
      ->set('create_menus', $form_state->getValue('create_menus'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger()->addStatus($this->t('Wireframe Converter settings have been saved.'));
  }

  /**
   * Get available content type options.
   *
   * @return array
   *   Array of content type options.
   */
  protected function getContentTypeOptions() {
    $content_types = \Drupal::service('entity_type.manager')
      ->getStorage('node_type')
      ->loadMultiple();

    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }

    return $options;
  }

} 