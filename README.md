# TranslationEditorBundle

The TranslationEditorBundle is a Symfony2 bundle that provides web base UI to manage Symfony2 translations. It is forked from https://github.com/servergrove/TranslationEditorBundle and improved.

## How it works?

Import translations files from your project to MongoDB, edit in editor, then export back to files.

Translations are imported from and dumped back to 'src' and 'app/Resources' directories.

Currently only YML files are supported.

The following command line tools are provided:

* Import all translations files

  ./app/console locale:editor:import

* Import translation file

  ./app/console locale:editor:import [filename]

* Export all translation files

  ./app/console locale:editor:export

* Export to translation file

  ./app/console locale:editor:export [filename]


## Screenshots

<img src="http://farm8.staticflickr.com/7158/6668570353_1b852e0e7b_b_d.jpg" />

## Installation:

Clone or add to composer:

  "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/anithaly/TranslationEditorBundle"
        }
    ]

  "require": {
    "anithaly/translation-editor-bundle": "dev-master"
  }

Then run composer update

Enable it in your app/AppKernel.php (we recommend that you do it only for the dev environments)

  public function registerBundles()
  {
    ...

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
          ...
            $bundles[] = new ServerGrove\Bundle\TranslationEditorBundle\ServerGroveTranslationEditorBundle();
        }

    ...
  }

## Configuration:

We recommend that you only enable this bundle for the development environments, so only add the configuration in config_dev.yml

The collection parameter allows you to define the collection that will contain the translations for the project, so you can have multiple Symfony2 projects in the same mongodb server.

The mongodb parameters defines the mongodb server to connect to.

  parameters:
    translation_editor.collection: mytranslations
    translation_editor.mongodb: mongodb://localhost:27017

  # enable bundle extension
  server_grove_translation_editor: ~

Add the routing configuration to app/config/routing_dev.yml

  SGTranslationEditorBundle:
    resource: "@ServerGroveTranslationEditorBundle/Resources/config/routing.yml"
    prefix:   /

## Usage:

1. Import translation files into mongodb

  ./app/console locale:editor:import

2. Load editor in browser, edit your translations

  http://your-project.url/translations/editor

3. Export changes to translation files

  ./app/console locale:editor:export

## WARNING

**PLEASE** Backup your translation files before using the editor. **Use a source control system like git, even svn is ok**. We are not responsible for lost information.

## TODO

## MongoDB Installation: