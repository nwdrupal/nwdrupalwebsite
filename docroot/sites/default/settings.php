<?php
/**
 * @file
 * Platform.sh example settings.php file for Drupal 8.
 */

// Install with the 'standard' profile for this example.
$settings['install_profile'] = 'standard';

// You should modify the hash_salt so that it is specific to your application.
$settings['hash_salt'] = '7tXX3zNpJqFSGLfjo44H9489NiQMjwSs8R3qlGkzcefgvNBTp2779oSJTp8Yrmik';

$config_directories['sync'] = realpath($app_root . '/../config/' . basename($site_path));

$settings['trusted_host_patterns'] = [
  '^www\.nwdrupal\.org\.uk',
  'nwdrupal\.org\.uk',
  '^www\.nwdrupal\.local',
  'nwdrupal\.local',
];

/**
 * Default Drupal 8 settings.
 *
 * These are already explained with detailed comments in Drupal's
 * default.settings.php file.
 *
 * See https://api.drupal.org/api/drupal/sites!default!default.settings.php/8
 */
$databases = array();
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = __DIR__ . '/services.yml';

$config_directories[CONFIG_SYNC_DIRECTORY] = '../config/sync';
// Automatic Platform.sh settings.
if (file_exists($app_root . '/' . $site_path . '/settings.platformsh.php')) {
  include $app_root . '/' . $site_path . '/settings.platformsh.php';
}
// Local settings. These come last so that they can override anything.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
