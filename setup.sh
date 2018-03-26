#!/bin/bash

# Get Phenkal and put it into the src directory.
if [ ! -f src/phenkal/build.xml ]; then
  git clone https://github.com/hashbangcode/phenkal.git src/phenkal
  rm -rf src/phenkal/.git
  mv src/phenkal/drupal8_composer.json src/phenkal/composer.json
  mv src/phenkal/example.build.properties src/phenkal/build.properties
  cd src/phenkal/
  composer install
  cd ../..
fi

if [ ! -f src/drupal-composer/composer.json ]; then
  git clone https://github.com/drupal-composer/drupal-project.git src/drupal-composer
  rm -rf src/drupal-composer/.git
  # Alter some things in the src/composer/composer.json file.
  php ./src/composer_update.php
fi

if [ ! -f drush/policy.drush.inc ]; then
  # Get the drush policy include file.
  mkdir drush
  curl -sS -o drush/README.md https://raw.githubusercontent.com/drupal-composer/drupal-project/8.x/drush/README.md
  curl -sS -o drush/policy.drush.inc https://raw.githubusercontent.com/drupal-composer/drupal-project/8.x/drush/policy.drush.inc
fi

if [ ! -f drupalvm/Vagrantfile ]; then
  # Get drupalvm.
  git clone https://github.com/geerlingguy/drupal-vm.git src/drupalvm
  # Remove .git directory from drupalvm.
  rm -rf src/drupalvm/.git
fi

if [ ! -d docroot ]; then
  # Setup a default docroot directory.
  mkdir docroot
fi

if [ ! -d config ]; then
  # Setup a default docroot directory.
  mkdir -p config/sync
fi

# Prompt developer to change the settings files.
tput setaf 2
echo "---------------------------------------------------"
echo "Change the settings file before running your VM."
echo " ------- settings/config.yml ------- "
echo "---------------------------------------------------"
tput sgr0

if [ -x "$(command -v say)" ]; then
  say "Change the settings file in settings/config.yml before running your VM."
fi
