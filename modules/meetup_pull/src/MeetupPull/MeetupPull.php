<?php

namespace Drupal\meetup_pull\MeetupPull;

use DMS\Service\Meetup\MeetupKeyAuthClient;
use Drupal\node\Entity\Node;

class MeetupPull {

  public function meetupPull() {

    // nwdug group id = 4396922


    // Get the Meetup API Key.
    $mymodule_config = \Drupal::config('meetup_pull.settings');
    $key = $mymodule_config->get('api_key');

    // If no API key is present then return an error.
    if ($key == '') {
      return 'API Key is empty.';
    }

    // Get the Meetup client.
    $client = MeetupKeyAuthClient::factory(array('key' => $key));

    // Get all the (future) events for the group.
    $events = $client->getEvents(array('group_urlname' => 'nwdrupal', 'page' => 10));

    // Initialise event counts.
    $count_existing = 0;
    $count_new = 0;

    foreach ($events as $event) {
      // Extract the meetup.
      $meetup_event_id = $event['id'];

      // Set default FALSE status for venues.
      $venue = FALSE;

      if (isset($event['venue'])) {
        // If the Venue has been set then
        $venue = $event['venue'];
        $tids = \Drupal::entityQuery('taxonomy_term')
          ->condition('name', $venue['name'], '=')
          ->condition('vid', 'venue')
          ->execute();
        if (count($tids) > 0) {
          $venue = array_pop($tids);
        }
      }

      // Extract RSVP Count.
      $rsvp = $event['yes_rsvp_count'];

      // Extract date/time
      $date = $event['time'];

      // Extract URL of event.
      $event_url = $event['event_url'];

      $dateTime = new \DateTime();
      $dateTime->setTimestamp(substr($date, 0, -3));
      // Time format in MySQL is '2015-11-12T18:00:00'.
      $date = $dateTime->format('Y-m-d\TH:i:00');

      $path_components = array(
        'year' => $dateTime->format('Y'),
        'month' => $dateTime->format('m'),
        'day' => $dateTime->format('d')
      );

      $event_title = $event['name'];

      $event_body = array(
        'value' => $event['description'],
        'format' => filter_default_format(),
      );

      // Search the a node with the same meetup event ID already in the system.
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'event')
        ->condition('field_event_meetup_id', $meetup_event_id, '=')
        ->execute();

      if (count($nids) > 0) {
        // Event exists, update it.
        $count_existing++;

        $nid = array_pop($nids);

        // Load the found event node.
        $node = \Drupal::entityManager()->getStorage('node')->load($nid);

        // Apply all of the settings we extracted from the Meetup API and save the node.
        $node->field_event_rsvps = $rsvp;
        $node->title = $event_title;
        $node->body = $event_body;
        $node->field_event_date = $date;
        $node->field_event_url = $event_url;
        if ($venue !== FALSE) {
          $node->field_event_venue = $venue;
        }

        $node->save();

        $node_alias = '/event/' . $path_components['year'] . '/' . $path_components['month'] . '/' . $path_components['day'] . '/' . $this->slug($event_title);
        \Drupal::service('path.alias_storage')->save('/'.$node->urlInfo()->getInternalPath(), $node_alias, $node->language()->getId());

      }
      else {
        // Event doesn't exist, create it.
        $count_new++;

        // Set all of the settings we extracted from the Meetup API and create the node.
        $new_node = array(
          'type' => 'event',
          'title' => $event_title,
          'body' => $event_body,
          'field_event_date' => $date,
          'field_event_rsvps' => $rsvp,
          'field_event_meetup_id' => $meetup_event_id,
          'field_event_url' => $event_url
        );

        if ($venue !== FALSE) {
          $new_node['field_event_venue'] = $venue;
        }

        $node = Node::create($new_node);
        $node->save();

        $node_alias = '/event/' . $path_components['year'] . '/' . $path_components['month'] . '/' . $path_components['day'] . '/' . $this->slug($event_title);
        \Drupal::service('path.alias_storage')->save('/'.$node->urlInfo()->getInternalPath(), $node_alias, $node->language()->getId());

      }
    }

    return 'Meetup events pulled and updated. ' . $count_new . ' new events and ' . $count_existing . ' existing event processed.<br>' . ($count_existing + $count_new) . ' events procssed in totoal.';
  }

  /**
   * Generate a slug from a string.
   * 
   * @param string $string
   *
   * @return string
   */
  public function slug($string) {
    // Trim the string.
    $slug = trim($string);
    // Only take alphanumerical characters, but keep the spaces and dashes too.
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $slug);
    // Replace spaces by dashes.
    $slug = str_replace(' ', '-', $slug);
    // Make it lowercase.
    $slug = strtolower($slug);

    return $slug;
  }

}
