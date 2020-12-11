<?php

namespace Effulgentsia\StagedComposerUpdate;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable, CommandProvider {

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return [
      'Composer\Plugin\Capability\CommandProvider' => static::class,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return [new StagedUpdateCommand()];
  }

}
