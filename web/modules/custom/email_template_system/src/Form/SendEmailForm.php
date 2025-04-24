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
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Name'),
      '#required' => TRUE,
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
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
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

    $send = $this->mailManager->mail('send_email_form', 'send_email', $email, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);

    if ($send['result']) {
      $this->messenger->addMessage($this->t('Email sent successfully to @email.', ['@email' => $email]));
    } else {
      $this->messenger->addMessage($this->t('Failed to send email.'), 'error');
    }  
  }
}
