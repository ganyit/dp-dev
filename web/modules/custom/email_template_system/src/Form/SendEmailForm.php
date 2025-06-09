<?php

namespace Drupal\email_template_system\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a send email form.
 */
class SendEmailForm extends FormBase {

  protected $mailManager;
  protected $messenger;

  /**
   * Inject dependencies.
   */
  public function __construct(MailManagerInterface $mail_manager, MessengerInterface $messenger) {
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
  }

  /**
   * Creates an instance of the form class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('messenger')
    );  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'send_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  // Use entity query to load all email_templates
  $storage = \Drupal::entityTypeManager()->getStorage('email_template');

  // Query all email templates.
  $query = $storage->getQuery()->accessCheck(TRUE);;
  $ids = $query->execute();

  // Load entities by their IDs.
  $email_templates = $storage->loadMultiple($ids);
  $options = [];
    // Loop through and output the loaded email templates.
  foreach ($email_templates as $email_template) {
    // Assuming your entity has a 'label' field or another field to identify it.
    $label = $email_template->label();  // Or replace with your own field like $email_template->get('field_name');
    $options[$email_template->id()] = $email_template->get('email_title')->value;
  }

    $form['email_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Email Template'),
      '#options' => $options,
      '#ajax' => [
        'callback' => '::updateEmailTemplateContent',
        'event' => 'change',
      ],
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Email Subject'),
      '#default_value' => '',
      '#required' => TRUE,
      '#prefix' => '<div class="js-email-subject">',  // Start the wrapper
      '#suffix' => '</div>',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your Email'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your Message'),
      '#required' => TRUE,
      '#prefix' => '<div class="js-email-message">',  // Start the wrapper
      '#suffix' => '</div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /*public function updateEmailTemplateContent(array &$form, FormStateInterface $form_state): array {
    $storage = \Drupal::entityTypeManager()->getStorage('email_template');
    $selected_etid = $form_state->getValue('email_title');
    $email_subject = '';
    $email_body = '';
    if (!empty($selected_etid)) {
      $email_emplate = $storage->load($selected_etid);
      if ($email_emplate && $email_emplate->hasField('email_subject')) {
        $email_subject = $email_emplate->get('email_subject')->value;
        $email_body = $email_emplate->get('email_body')->value;
      }
    }

    // Update the text field with the node content
    $form['subject']['#value'] = $email_subject;
    $form['message']['#value'] = $email_body;

    return [
      $form['subject'],
      $form['message'],
    ];
  }*/

  public function updateEmailTemplateContent(array &$form, FormStateInterface $form_state){
    $storage = \Drupal::entityTypeManager()->getStorage('email_template');
    $selected_etid = $form_state->getValue('email_title');
    $email_subject = '';
    $email_body = '';
    if (!empty($selected_etid)) {
      $email_emplate = $storage->load($selected_etid);
      if ($email_emplate && $email_emplate->hasField('email_subject')) {
        $email_subject = $email_emplate->get('email_subject')->value;
        $email_body = $email_emplate->get('email_body')->value;
      }
    }
    // Update the text field with the node content
    $form['subject']['#value'] = $email_subject;
    $form['message']['#value'] = $email_body;
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand(".js-email-subject", $form['subject']));
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand(".js-email-message", $form['message']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $subject = $form_state->getValue('subject');
    $message = $form_state->getValue('message');

    $params = [
      'subject' => $subject,
      'message' => $message,
    ];

    $send = $this->mailManager->mail('email_template_system', 'send_email', $email, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);

    if ($send['result']) {
      $this->messenger->addMessage($this->t('Email sent successfully to @email.', ['@email' => $email]));
    } else {
      $this->messenger->addMessage($this->t('Failed to send email.'), 'error');
    }  
  }
}
