<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Command for importing translation files
 */

class ImportCommand extends Base {

  protected function configure() {
    parent::configure();

    $this
    ->setName('locale:editor:import')
    ->setDescription('Import translation files into MongoDB for using through /translations/editor')
    ->addArgument('filename')
    ;
  }

  public function execute(InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;
    $this->files = array();

    $filename = $this->input->getArgument('filename');

    if(!empty($filename) && is_dir($filename)) {
      $this->output->writeln("Importing translations from <info>$filename</info>...");
      $finder = new Finder();
      $finder->files()->in($filename)->name('*.yml');

      foreach($finder as $file) {
        $this->output->writeln("Found <info>".$file->getRealpath()."</info>...");
        $this->files[] = $file->getRealpath();
      }
    } else {
      $dirApp = $this->getContainer()->getParameter('kernel.root_dir');
      $dirSrc = $this->getContainer()->getParameter('kernel.root_dir').'/../src';

      $this->scanDir($dirApp);
      $this->scanDir($dirSrc);
    }

    if(!count($this->files)) {
      $this->output->writeln("<error>No files found.</error>");
      return;
    }
    $this->output->writeln(sprintf("Found %d files, importing...", count($this->files)));

    foreach($this->files as $filename) {
      $this->import($filename);
    }
  }

  public function import($filename) {
    $fname = basename($filename);
    $this->output->writeln("Processing <info>".$filename."</info>...");

    list($domain, $locale, $loader) = explode('.', $fname);

    $this->setIndexes();

    switch($loader) {
      case 'yml':
        $YamlLoader = new YamlFileLoader();
        $array = $YamlLoader->load($filename, $locale, $domain);
        $catalogue = new MessageCatalogue($locale);
        $catalogue->addCatalogue($array);
        $dictionary = $catalogue->all();

        foreach ($dictionary as $domainTranslations) {
          $this->output->writeln("  Found ".count($domainTranslations)." translations...");

          $fileTranslations = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection()->findOne(array('filename' => $filename));

          if(!$fileTranslations) {
            $fileTranslations = array(
              'filename' => $filename,
              'domain' => $domain,
              'locale' => $locale,
              'loader' => $loader,
              'translations' => $domainTranslations,
           );
          }
          $this->updateValue($fileTranslations);
        }

        break;
      case 'xliff':
        $this->output->writeln("  Skipping, not implemented");
        break;
    }
  }

  protected function scanDir($dirToCheck) {
    $this->output->writeln("Scanning ".$dirToCheck."...");
    $dirFinder = new Finder();
    $dirFinder->directories()->in($dirToCheck)->name('translations');

    foreach($dirFinder as $dir) {
      $fileFinder = new Finder();
      $fileFinder->files()->in($dir->getRealpath())->name('/^[\w]+\.[\w]+\.(yml|xliff)$/');
      foreach($fileFinder as $file) {
        $this->output->writeln("Found <info>".$file->getRealpath()."</info>...");
        $this->files[] = $file->getRealpath();
      }
    }
  }

  protected function setIndexes() {
    $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();
    $collection->ensureIndex(array("filename" => 1, 'locale' => 1, 'domain' => 1));
  }

  protected function updateValue($fileTranslations) {
    $collection = $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();

    $criteria = array(
      'filename' => $fileTranslations['filename'],
   );

    return $collection->update($criteria, $fileTranslations, array('upsert' => true));
  }
}
