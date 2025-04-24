<?php

namespace Drupal\email_template_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\email_template_system\Entity\EmailTemplate;

use Drupal\email_template_system\Form\SendEmailForm;

class EmailTemplateController extends ControllerBase {


    /**
     * Displays the email form.
     */
    public function emailForm() {
        /*return [
        '#theme' => 'email_template_form', // Use a theme template or remove if using raw output.
        '#email_form' => \Drupal::formBuilder()->getForm('Drupal\send_email_form\Form\SendEmailForm');,
        ];*/
        return \Drupal::formBuilder()->getForm('Drupal\email_template_system\Form\SendEmailForm');
    }


    /**
     * Returns a list of custom entities.
     */
    public function listEmailTemplates() {
        // Query all custom entities
        $entities = EmailTemplate::loadMultiple();
        
        // Create an empty array to hold the rows for the table
        $rows = [];

        foreach ($entities as $entity) {
            // Add each entity to the rows array with a label and a link to view the entity.
            //print_r($entity);exit;
            $rows[] = [
            'data' => [
                $entity->get('email_title')->value,  // Display the entity's label
                Link::fromTextAndUrl(t('View'), Url::fromRoute('entity.EmailTemplate.canonical', ['email_template' => $entity->id()])),  // Create a view link to the entity
            ],
            ];
        }

        return [
            '#theme' => 'email_template',  // The name of the theme hook.
            '#email_templates' => $entities,      // Pass the custom entity to the template.
            '#cache' => [
              'contexts' => ['url.path'],  // Cache based on the URL path (you can adjust this based on your needs).
            ],
          ];
    }

    public function getTitle(EmailTemplate $email_template) {
        return $email_template->label();
    }
}