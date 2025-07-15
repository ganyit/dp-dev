<?php

namespace Drupal\figma_importer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\figma_importer\Service\FigmaApiService;

class FigmaImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'figma_import_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['figma_importer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('figma_importer.settings');

    $form['figma_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Figma API Key'),
      '#default_value' => $config->get('figma_api_key'),
      '#required' => TRUE,
    ];

    $form['figma_file_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Figma File ID'),
      '#default_value' => $config->get('figma_file_id'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import from Figma'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('figma_importer.settings');
    $api_key = $form_state->getValue('figma_api_key');
    $file_id = $form_state->getValue('figma_file_id');

    // Save API key and file ID to config.
    $config->set('figma_api_key', $api_key)
      ->set('figma_file_id', $file_id)
      ->save();

    /** @var FigmaApiService $figma_api */
    $figma_api = \Drupal::service('figma_importer.figma_api');
    $data = $figma_api->fetchFile($api_key, $file_id);

    if ($data && !empty($data['document']['children'][0]['children'])) {
      $frames = $data['document']['children'][0]['children'];
      $created = 0;

      foreach ($frames as $frame) {
        if ($frame['type'] === 'FRAME') {
          $html = '<div class="figma-frame">';
          $html .= $this->renderFigmaElements($frame['children'] ?? [], $figma_api, $file_id, $api_key);
          $html .= '</div>';

          $node = \Drupal::entityTypeManager()->getStorage('node')->create([
            'type' => 'page',
            'title' => $frame['name'],
            'body' => [
              [
                'value' => $html,
                'format' => 'full_html',
              ],
            ],
            'status' => 1,
          ]);

          $node->save();
          $created++;
        }
      }

      $this->messenger()->addStatus($this->t('@count Drupal pages created from Figma frames.', ['@count' => $created]));
    }
    else {
      $this->messenger()->addError($this->t('Failed to fetch Figma file data or no frames found.'));
      \Drupal::logger('figma_importer')->error('Figma API fetch failed or returned no frames for file ID: @file_id', ['@file_id' => $file_id]);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Recursively render Figma elements.
   */
  private function renderFigmaElements(array $elements, $figma_api, string $file_id, string $api_key): string {
    $html = '';

    foreach ($elements as $element) {
      switch ($element['type']) {
        case 'TEXT':
          $text = htmlspecialchars($element['characters'] ?? '');
          $style = $element['style'] ?? [];
          $size = $style['fontSize'] ?? 16;
          $weight = $style['fontWeight'] ?? 400;
          $html .= '<p style="font-size:' . $size . 'px; font-weight:' . $weight . ';">' . $text . '</p>';
          break;

        case 'RECTANGLE':
          if (!empty($element['fills'][0]['imageRef'])) {
            // Render image using imageRef.
            $image_id = $element['id'];
            $image_url = $figma_api->getImageUrl($file_id, $image_id, $api_key);
            if ($image_url) {
              $html .= '<img src="' . $image_url . '" alt="Figma Image" style="max-width:100%;" />';
            }
          }
          elseif (!empty($element['fills'][0]['color'])) {
            $color = $element['fills'][0]['color'];
            $r = round($color['r'] * 255);
            $g = round($color['g'] * 255);
            $b = round($color['b'] * 255);
            $width = round($element['absoluteBoundingBox']['width'] ?? 100);
            $height = round($element['absoluteBoundingBox']['height'] ?? 100);
            $html .= '<div style="background-color: rgb(' . "$r,$g,$b" . '); width:' . $width . 'px; height:' . $height . 'px; display:inline-block;"></div>';
          }
          break;

        case 'FRAME':
        case 'GROUP':
          if (!empty($element['children'])) {
            $html .= '<div class="figma-nested">';
            $html .= $this->renderFigmaElements($element['children'], $figma_api, $file_id, $api_key);
            $html .= '</div>';
          }
          break;
      }
    }

    return $html;
  }

}
