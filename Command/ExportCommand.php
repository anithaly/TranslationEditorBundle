<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Yaml\Dumper;

/**
 * Command for exporting translations into files
 */

class ExportCommand extends Base {

  protected function configure() {
    parent::configure();

    $this
    ->setName('locale:editor:export')
    ->setDescription('Export translations into files')
    ->addArgument('filename')
    ;
  }

  public function execute(InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;
    $this->files = array();

    $filename = $this->input->getArgument('filename');

    if (!empty($filename) && is_dir($filename)) {
      $this->output->writeln("Exporting translations to <info>$filename</info>...");
      $finder = new Finder();
      $finder->files()->in($filename)->name('*.yml');

      foreach ($finder as $file) {
        $this->output->writeln("Found <info>".$file->getRealpath()."</info>...");
        $this->files[] = $file->getRealpath();
      }

    } else {
      $dirApp = $this->getContainer()->getParameter('kernel.root_dir');
      $dirSrc = $this->getContainer()->getParameter('kernel.root_dir').'/../src';

      $this->scanDir($dirApp);
      $this->scanDir($dirSrc);
    }

    if (!count($this->files)) {
      $this->output->writeln("<error>No files found.</error>");
      return;
    }
    $this->output->writeln(sprintf("Found %d files, exporting...", count($this->files)));

    foreach ($this->files as $filename) {
      $this->export($filename);
    }
  }

  public function export($filename) {
    $fname = basename($filename);
    $this->output->writeln("Exporting to <info>".$filename."</info>...");

    list($domain, $locale, $loader) = explode('.', $fname);

    switch ($loader) {
      case 'yml':
        $data = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection()->findOne(array('filename' => $filename));

        if (!$data) {
          $this->output->writeln("Could not find data for that file");
          return;
        }

        // todo test it
        foreach ($data['translations'] as $key => $val) {
          if (empty($val)) {
            unset($data['translations'][$key]);
          }
        }

        $this->output->writeln("Writing ".count($data['translations'])." translations to $filename");

        $dumper = new Dumper();
        $ymlString = $dumper->dump($data['translations'], 1);

        file_put_contents($filename, $ymlString);

        break;
      case 'xliff':
        $this->output->writeln("Skipping, not implemented");
        break;
    }
  }

  protected function scanDir($dirToCheck) {
    $this->output->writeln("Scanning ".$dirToCheck."...");
    $dirFinder = new Finder();
    $dirFinder->directories()->in($dirToCheck)->name('translations');

    foreach ($dirFinder as $dir) {
      $fileFinder = new Finder();
      $fileFinder->files()->in($dir->getRealpath())->name('*');
      foreach ($fileFinder as $file) {
        $this->output->writeln("Found <info>".$file->getRealpath()."</info>...");
        $this->files[] = $file->getRealpath();
      }
    }
  }
}
