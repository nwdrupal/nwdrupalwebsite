#!/usr/bin/env php
<?php
/**
 * @file
 * Composer update script file.
 */

/**
 * The web root directory.
 */
define('WEB_ROOT', 'docroot');

// Run the composer update function.
composer_update();

/**
 * Perform some rearranging on the composer file.
 *
 * @throws \Exception
 */
function composer_update() {
  // Find the composer.json file.
  $composerJson = realpath(__DIR__ . '/drupal-composer/composer.json');

  if (!file_exists($composerJson)) {
    throw new Exception('composer.json file not found');
  }

  // Extract data from the composer.json file.
  $json = file_get_contents($composerJson);

  // Decode the json data.
  $composerContents = json_decode($json);

  // Change the location of the ScriptHandler.php file.
  $composerContents->autoload->classmap = ['scripts/composer/ScriptHandler.php'];

  // Write the composer.json file back into the repo.
  $json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
  file_put_contents($composerJson, json_encode($composerContents, $json_options));

  echo 'Composer file updated.';
}
