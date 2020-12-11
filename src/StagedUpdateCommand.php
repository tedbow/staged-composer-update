<?php

namespace Effulgentsia\StagedComposerUpdate;

use Composer\Command\BaseCommand;
use Composer\Console\Application;
use Composer\Factory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StagedUpdateCommand extends BaseCommand {

  protected function configure() {
    $this->setName('staged-update')->setDefinition([
      new InputOption('prefer-lowest', null, InputOption::VALUE_NONE, 'Prefer lowest versions of dependencies.'),
    ]);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $composer = $this->getComposer();

    $composerFile = $composer->getConfig()->getConfigSource()->getName();
    $lockFile = Factory::getLockFile($composerFile);
    $vendorDirectory = $composer->getConfig()->get('vendor-dir');
    $localRepoDirectory = './_local';
    $phpstanDirectory = './phpstan';

    $stagedDirectory = '/tmp/composer';
    $stagedComposerFile = $stagedDirectory . '/composer.json';
    $stagedLockFile = $stagedDirectory . '/composer.lock';
    $stagedVendorDirectory = $stagedDirectory . '/vendor';
    $stagedLocalRepoDirectory = $stagedDirectory . '/_local';
    $stagedPhpstanDirectory = $stagedDirectory . '/phpstan';

    copy($composerFile, $stagedComposerFile);
    copy($lockFile, $stagedLockFile);
    exec('rsync -a --del ' . escapeshellarg($vendorDirectory . '/') . ' ' . escapeshellarg($stagedVendorDirectory . '/'));
    exec('rsync -a --del ' . escapeshellarg($localRepoDirectory . '/') . ' ' . escapeshellarg($stagedLocalRepoDirectory . '/'));
    exec('rsync -a --del ' . escapeshellarg($phpstanDirectory . '/') . ' ' . escapeshellarg($stagedPhpstanDirectory . '/'));

    $cwd = getcwd();
    chdir($stagedDirectory);
    $child_app = new Application();
    $child_input = new ArrayInput(['command' => 'update', '--prefer-lowest' => $input->getOption('prefer-lowest')], $this->getDefinition());
    $result = $child_app->run($child_input, $output);
    chdir($cwd);

    return $result;
  }

}
