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
        new InputOption('mode', null, InputOption::VALUE_OPTIONAL, 'full, stage, copy-back', 'full')
    ]);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
      // @todo Add staged dir option
    $stagedDirectory = sys_get_temp_dir() . '/staged-composer-update';
    $excludeDirectory = 'sites/default/files';
    $mode = $input->getOption('mode');
    if (!in_array($mode, ['full', 'stage', 'copy-back'])) {
        throw new \UnexpectedValueException("Unknown mode $mode");
    }

    $result = 0;
    if ($mode === 'full' || $mode === 'stage') {
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
            'command' => 'require',
            'packages' => $input->getArgument('packages'),
          ]
        );
        $result = $child_app->run($child_input, $output);
        chdir($cwd);
        if ($mode === 'stage') {
            return $result;
        }
    }

    if ($mode === 'full' || $mode === 'copy-back') {
        exec('rsync -a --del --exclude=' . escapeshellarg('/' . $excludeDirectory . '/') . ' ' . escapeshellarg($stagedDirectory . '/') . ' ./');
        return $result;
    }

  }

}
