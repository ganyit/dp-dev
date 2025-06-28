<?php
namespace Drupal\ai_comment_moderation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AiModerationSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['ai_comment_moderation.settings'];
  }

  public function getFormId() {
    return 'ai_moderation_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ai_comment_moderation.settings');

    $form['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_api_key'),
      '#description' => $this->t('Enter your OpenAI API key.'),
      '#required' => TRUE,
      '#maxlength' => 256,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ai_comment_moderation.settings')
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
