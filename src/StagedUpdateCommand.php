<?php

namespace Effulgentsia\StagedComposerUpdate;

use Composer\Command\BaseCommand;
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StagedUpdateCommand extends BaseCommand {

  protected function configure() {
    $this->setName('staged-update')->setDefinition([
      new InputArgument('packages', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Packages that should be updated, if not provided all packages are.'),
      new InputOption('prefer-lowest', null, InputOption::VALUE_NONE, 'Prefer lowest versions of dependencies.'),
    ]);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $stagedDirectory = sys_get_temp_dir() . '/staged-composer-update';
    $excludeDirectory = 'sites/default/files';

    if (!is_dir($stagedDirectory)) {
      mkdir($stagedDirectory);
    }
    exec('rsync -a --del --exclude=' . escapeshellarg('/' . $excludeDirectory . '/') . ' ./ ' . escapeshellarg($stagedDirectory . '/'));
    $cwd = getcwd();
    chdir($stagedDirectory);

    $child_app = new Application();
    $child_app->setAutoExit(false);
    $child_input = new ArrayInput(
      [
      'command' => 'update',
      'packages' => $input->getArgument('packages'),
      '--prefer-lowest' => $input->getOption('prefer-lowest')
      ],
      $this->getDefinition()
    );
    $result = $child_app->run($child_input, $output);

    chdir($cwd);
    exec('rsync -a --del --exclude=' . escapeshellarg('/' . $excludeDirectory . '/') . ' ' . escapeshellarg($stagedDirectory . '/') . ' ./');
    return $result;
  }

}
