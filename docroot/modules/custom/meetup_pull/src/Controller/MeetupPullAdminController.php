<?php
/**
 * @file
 * Contains \Drupal\example\Controller\ExampleController.
 */
namespace Drupal\meetup_pull\Controller;
use Drupal\Core\Controller\ControllerBase;

class MeetupPullAdminController {
		public function admin() {

				return array(
						'#type' => 'markup',
						'#markup' => 'Meetup Pull! :)'
				);
		}

	public function force() {

			$form = \Drupal::formBuilder()->getForm('Drupal\meetup_pull\Form\MeetupPullForce');

			return $form;
	}
}