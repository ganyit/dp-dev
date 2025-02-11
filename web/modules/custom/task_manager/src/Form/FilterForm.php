<?php

namespace Drupal\task_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class FilterForm extends FormBase {

  public function getFormId() {
    return 'payment_list_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => '',
      '#size' => 30,
      '#maxlength' => 128,
      '#description' => $this->t('Enter a keyword to filter the listing.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the listing page with the search term as query parameter.
    $search_term = $form_state->getValue('search');
    $url = Url::fromRoute('task_manager.payment_list', [], ['query' => ['search' => $search_term]]);
    $form_state->setRedirectUrl($url);
  }
}
