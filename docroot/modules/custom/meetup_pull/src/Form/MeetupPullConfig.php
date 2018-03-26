<?php

namespace Drupal\meetup_pull\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

class MeetupPullConfig extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'meetup_pull_settings_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('meetup_pull.settings');

    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Meetup API'),
      '#default_value' => $config->get('api_key')
    );

    $form['group_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Group Name'),
      '#default_value' => $config->get('group_name')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('meetup_pull.settings');

    $config->set('api_key', $form_state->getValue('api_key'))->save();
    $config->set('group_name', $form_state->getValue('group_name'))->save();

    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return array('meetup_pull.settings');
  }
}