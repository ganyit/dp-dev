<?php

namespace Drupal\wireframe_converter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for wireframe conversion functionality.
 */
class WireframeConverterController extends ControllerBase {

  /**
   * Display the wireframe conversion page.
   *
   * @return array
   *   A render array for the conversion page.
   */
  public function convertPage() {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['wireframe-converter-page']],
    ];

    $build['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['description']],
      '#value' => $this->t('Upload a wireframe image to convert it to Drupal content using Azure Computer Vision API.'),
    ];

    $build['upload_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Upload Wireframe'),
      '#url' => Url::fromRoute('wireframe_converter.upload'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    $build['settings_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Configure Settings'),
      '#url' => Url::fromRoute('wireframe_converter.admin'),
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for processing wireframe conversion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with conversion results.
   */
  public function processConversion(Request $request) {
    try {
      $file_id = $request->request->get('file_id');
      
      if (!$file_id) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'No file provided.',
        ]);
      }

      // Debug: Check if entityTypeManager method exists
      if (!method_exists($this, 'entityTypeManager')) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Controller method entityTypeManager not available.',
        ]);
      }

      $file = $this->entityTypeManager()->getStorage('file')->load($file_id);
      if (!$file) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'File not found.',
        ]);
      }

      $file_uri = $file->getFileUri();
      $file_path = \Drupal::service('file_system')->realpath($file_uri);

      if (!file_exists($file_path)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'File does not exist on disk.',
        ]);
      }

      // Debug: Log the file path being processed
      \Drupal::logger('wireframe_converter')->info('Processing file: @path', ['@path' => $file_path]);

      // Get the wireframe converter service.
      $converter_service = \Drupal::service('wireframe_converter.converter');
      
      // Convert the wireframe.
      $result = $converter_service->convertWireframe($file_path);

      if ($result['success']) {
        $content_info = [];
        foreach ($result['content'] as $type => $entity) {
          $content_info[$type] = [
            'id' => $entity->id(),
            'title' => $entity->label(),
            'url' => $entity->toUrl()->toString(),
          ];
        }

        return new JsonResponse([
          'success' => TRUE,
          'message' => $result['message'],
          'content' => $content_info,
          'wireframe_data' => $result['wireframe_data'] ?? [],
        ]);
      }
      else {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $result['message'],
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('wireframe_converter')->error('Error in processConversion: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Error processing conversion: ' . $e->getMessage(),
      ]);
    }
  }

} 