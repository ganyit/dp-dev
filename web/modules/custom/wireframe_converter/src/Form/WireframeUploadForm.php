<?php

namespace Drupal\wireframe_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * Form for uploading wireframe images.
 */
class WireframeUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wireframe_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'wireframe_converter/upload_form';

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['form-description']],
      '#value' => $this->t('Upload a wireframe image (PNG, JPG, JPEG) to convert it to Drupal content using Azure Computer Vision API.'),
    ];

    $form['wireframe_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Wireframe Image'),
      '#description' => $this->t('Upload a wireframe image file. Supported formats: PNG, JPG, JPEG. Maximum file size: 10MB.'),
      '#upload_location' => 'public://wireframes/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [10485760], // 10MB
      ],
      '#required' => TRUE,
    ];

    $form['conversion_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conversion Options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['conversion_options']['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the content type for the generated page.'),
      '#options' => $this->getContentTypeOptions(),
      '#default_value' => 'page',
    ];

    $form['conversion_options']['auto_publish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-publish content'),
      '#description' => $this->t('Automatically publish the generated content.'),
      '#default_value' => TRUE,
    ];

    $form['conversion_options']['create_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create blocks from sections'),
      '#description' => $this->t('Create custom blocks for identified wireframe sections.'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert Wireframe'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'wireframe-conversion-results',
      ],
    ];

    $form['results'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'wireframe-conversion-results'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $file_id = $form_state->getValue(['wireframe_image', 0]);
    if (!$file_id) {
      $form_state->setError($form['wireframe_image'], $this->t('Please upload a wireframe image.'));
      return;
    }

    // Check if Azure credentials are configured.
    $config = $this->config('wireframe_converter.settings');
    if (empty($config->get('azure_endpoint')) || empty($config->get('azure_key'))) {
      $form_state->setError($form, $this->t('Azure Computer Vision API credentials are not configured. Please configure them in the <a href="@settings">settings page</a>.', [
        '@settings' => Url::fromRoute('wireframe_converter.admin')->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is not used when using AJAX.
  }

  /**
   * AJAX callback for form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    try {
      $file_id = $form_state->getValue(['wireframe_image', 0]);
      if (!$file_id) {
        $response->addCommand(new HtmlCommand('#wireframe-conversion-results', 
          '<div class="messages messages--error"><div class="message">' . $this->t('No file uploaded.') . '</div></div>'));
        return $response;
      }

      $file = \Drupal::service('entity_type.manager')->getStorage('file')->load($file_id);
      if (!$file) {
        $response->addCommand(new HtmlCommand('#wireframe-conversion-results', 
          '<div class="messages messages--error"><div class="message">' . $this->t('File not found.') . '</div></div>'));
        return $response;
      }

      // Show processing message with embedded script to trigger conversion
      $html = '<div class="messages messages--status">' . 
              '<div class="message">' . $this->t('Processing wireframe... Please wait.') . '</div>' .
              '<script>console.log("Processing wireframe for file ID: ' . $file_id . '"); ' .
              'if (window.processWireframeConversion) { window.processWireframeConversion(' . $file_id . '); } ' .
              'else { console.error("processWireframeConversion function not found"); }</script>' .
              '</div>';
      
      $response->addCommand(new HtmlCommand('#wireframe-conversion-results', $html));

      // Debug: Log the response commands
      \Drupal::logger('wireframe_converter')->info('AJAX response commands: @commands', [
        '@commands' => print_r($response->getCommands(), TRUE)
      ]);

    }
    catch (\Exception $e) {
      $response->addCommand(new HtmlCommand('#wireframe-conversion-results', 
        '<div class="messages messages--error"><div class="message">' . $this->t('Error: @error', ['@error' => $e->getMessage()]) . '</div></div>'));
    }

    return $response;
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