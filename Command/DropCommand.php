<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command for importing translation files
 */

class DropCommand extends Base {

  protected function configure() {
    parent::configure();

    $this
    ->setName('locale:mongodb:drop')
    ->setDescription('Drop mongo database with translations')
    ;
  }

  public function execute(InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;

    $this->output->writeln("Dropping database");
    $result = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->dropDB();
    if (isset($result['dropped'])) {
      $db = $result['dropped'];
      $this->output->writeln("Success, db $db has been dropped");
    } else {
      $this->output->writeln("Sth went wrong");
    }
  }

}
