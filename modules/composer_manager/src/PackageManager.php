<?php

/**
 * @file
 * Contains \Drupal\composer_manager\PackageManager.
 */

namespace Drupal\composer_manager;

/**
 * Manages composer packages.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * A cache of loaded packages.
   *
   * @var array
   */
  protected $packages = [];

  /**
   * Constructs a PackageManager object.
   *
   * @param string $root
   *   The drupal root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorePackage() {
    if (!isset($this->packages['core'])) {
      $this->packages['core'] = JsonFile::read($this->root . '/core/composer.json');
    }

    return $this->packages['core'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPackages() {
    if (!isset($this->packages['extension'])) {
      $listing = new ExtensionDiscovery($this->root);
      // Get all profiles, and modules belonging to those profiles.
      // @todo Scan themes as well?
      $profiles = $listing->scan('profile');
      $profile_directories = array_map(function ($profile) {
        return $profile->getPath();
      }, $profiles);
      $listing->setProfileDirectories($profile_directories);
      $modules = $listing->scan('module');
      $extensions = $profiles + $modules;

      $this->packages['extension'] = [];
      foreach ($extensions as $extension_name => $extension) {
        $filename = $this->root . '/' . $extension->getPath() . '/composer.json';
        if (is_readable($filename)) {
          $extension_package = JsonFile::read($filename);
          if (!empty($extension_package['require']) || !empty($extension_package['require-dev'])) {
            $this->packages['extension'][$extension_name] = JsonFile::read($filename);
          }
        }
      }
    }

    return $this->packages['extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredPackages() {
    if (!isset($this->packages['required'])) {
      $core_package = $this->getCorePackage();
      $merged_extension_package = $this->buildMergedExtensionPackage();

      $packages = [];
      foreach ($merged_extension_package['require'] as $package_name => $constraint) {
        if (substr($package_name, 0, 7) != 'drupal/') {
          // Skip Drupal module requirements, add the rest.
          $packages[$package_name] = [
            'constraint' => $constraint,
          ];
        }
      }

      $installed_packages = JsonFile::read($this->root . '/vendor/composer/installed.json');
      foreach ($installed_packages as $package) {
        $package_name = $package['name'];
        if (!isset($packages[$package_name])) {
          continue;
        }

        // Add additional information available only for installed packages.
        $packages[$package_name] += [
          'description' => !empty($package['description']) ? $package['description'] : '',
          'homepage' => !empty($package['homepage']) ? $package['homepage'] : '',
          'require' => !empty($package['require']) ? $package['require'] : [],
          'version' => $package['version'],
        ];
        if ($package['version'] == 'dev-master') {
          $packages[$package_name]['version'] .= '#' . $package['source']['reference'];
        }
      }

      // Process and cache the package list.
      $this->packages['required'] = $this->processRequiredPackages($packages);
    }

    return $this->packages['required'];
  }

  /**
   * Formats and sorts the provided list of packages.
   *
   * @param array $packages
   *   The packages to process.
   *
   * @return array
   *   The processed packages.
   */
  protected function processRequiredPackages(array $packages) {
    foreach ($packages as $package_name => $package) {
      // Ensure the presence of all keys.
      $packages[$package_name] += [
        'constraint' => '',
        'description' => '',
        'homepage' => '',
        'require' => [],
        'required_by' => [],
        'version' => '',
      ];
      // Sort the keys to ensure consistent results.
      ksort($packages[$package_name]);
    }

    // Sort the packages by package name.
    ksort($packages);

    // Add information about dependent packages.
    $extension_packages = $this->getExtensionPackages();
    foreach ($packages as $package_name => $package) {
      foreach ($extension_packages as $extension_name => $extension_package) {
        if (isset($extension_package['require'][$package_name])) {
          $packages[$package_name]['required_by'] = [$extension_package['name']];
          break;
        }
      }
    }

    return $packages;
  }

  /**
   * {@inheritdoc}
   */
  public function needsComposerUpdate() {
    $needs_update = FALSE;
    foreach ($this->getRequiredPackages() as $package) {
      if (empty($package['version']) || empty($package['required_by'])) {
        $needs_update = TRUE;
        break;
      }
    }

    return $needs_update;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildRootPackage() {
    $root_package = JsonFile::read($this->root . '/composer.json');
    // Rebuild the merged keys.
    $merged_extension_package = $this->buildMergedExtensionPackage();
    $root_package['require'] = [
      'composer/installers' => '^1.0.21',
      'wikimedia/composer-merge-plugin' => '^1.3.0',
    ] + $merged_extension_package['require'];
    $root_package['require-dev'] = $merged_extension_package['require-dev'];
    $root_package['replace'] = [
      'drupal/core' => '~8.0',
    ] + $merged_extension_package['replace'];
    $root_package['repositories'] = $merged_extension_package['repositories'];
    // Ensure the presence of the Drupal Packagist repository.
    // @todo Remove once Drupal Packagist moves to d.o and gets added to
    // the root package by default.
    $root_package['repositories'][] = [
      'type' => 'composer',
      'url' => 'https://packagist.drupal-composer.org',
    ];

    JsonFile::write($this->root . '/composer.json', $root_package);
  }

  /**
   * Builds a package containing the merged fields of all extension packages.
   *
   * @return array
   *   An array with the follwing keys:
   *   - 'require': The merged requirements
   *   - 'require-dev': The merged dev requirements.
   *   - 'replace': The merged replace list.
   */
  protected function buildMergedExtensionPackage() {
    $package = [
      'require' => [],
      'require-dev' => [],
      'replace' => [],
      'repositories' => [],
    ];
    $keys = array_keys($package);
    foreach ($this->getExtensionPackages() as $extension_package) {
      foreach ($keys as $key) {
        if (isset($extension_package[$key])) {
          $package[$key] = array_merge($extension_package[$key], $package[$key]);
        }
      }
    }
    $package['require'] = $this->filterPlatformPackages($package['require']);
    $package['require-dev'] = $this->filterPlatformPackages($package['require-dev']);
    $package['repositories'] = array_unique($package['repositories'], SORT_REGULAR);
    // For some reason array_unique() casts the keys to string, which causes
    // problems when exported to JSON.
    $package['repositories'] = array_values($package['repositories']);

    return $package;
  }

  /**
   * Removes platform packages from the requirements.
   *
   * Platform packages include 'php' and its various extensions ('ext-curl',
   * 'ext-intl', etc). Drupal modules have their own methods for raising the PHP
   * requirement ('php' key in $extension.info.yml) or requiring additional
   * PHP extensions (hook_requirements()).
   *
   * @param array $requirements
   *   The requirements.
   *
   * @return array
   *   The filtered requirements array.
   */
  protected function filterPlatformPackages($requirements) {
    foreach ($requirements as $package => $constraint) {
      if (strpos($package, '/') === FALSE) {
        unset($requirements[$package]);
      }
    }

    return $requirements;
  }

}
