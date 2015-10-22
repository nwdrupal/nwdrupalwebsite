<?php

namespace Drupal\meetup_pull\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\meetup_pull\MeetupPull\MeetupPull;

class MeetupPullForce extends FormBase {

  public function getFormId() {
    return 'meetup_pull_run_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Force Pull Meetup Events'
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $meetupPull = new MeetupPull();

    drupal_set_message($this->t($meetupPull->meetupPull()));
  }

}