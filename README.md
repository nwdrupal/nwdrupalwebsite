# Platform.sh Drupal 8.0 Example

This is a no-thrills example of a minimal repository to deploy a Drupal 8.0 instance on Platform.sh

This example is based on using the Drush Make build profile. You can see there is not much in terms of files comitted to this repository. You can learn (much) more on [Platform.sh Drupal Hosting Documentation](https://docs.platform.sh/toolstacks/php/drupal)

This is the whole layout of the repository (it will still make for a perfectly functional web site on http://platform.sh !)
```
.platform/
         /routes.yaml
         /services.yaml
libraries/
         /README.txt
modules/
         /README.txt
themes/
         /README.txt
.platform.app.yaml
project.make
```

in `.platform.app.yaml` we have the basic configuration of our applicaiton (we call it php), saying this is a Drupal 
application, that we depdend on a database called `database` and that we what to run updatedb on deployment .. and set
up a cron.

In `.platform/routes.yaml` we just say that we will redirect www to the naked domain, and that the application that 
will be serving HTTP will be the one we called `php`.

In `.platform/services.yaml` we say we want a MySQL instance, a Redis and a Solr. That would cover most basic Drupal
needs, right?

We also give you some nice empty (and totally not required) directories, so you would know where you are supposed to put 
your custom themes modules and libraries. "Normal", unforked contributed modules, themes and libraries should be put in 
the `project.make` file  (which contains our base Drupal version).
