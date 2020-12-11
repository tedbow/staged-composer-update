<?php

namespace Effulgentsia\StagedComposerUpdate;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\MetapackageInstaller;
use Composer\Installer\PluginInstaller;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Locker;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;

class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \Composer\Composer
   */
  private $composer;

  /**
   * @var \Composer\IO\IOInterface
   */
  private $io;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;

    $oldVendorDirectory = $composer->getConfig()->get('vendor-dir');
    $newVendorDirectory = '/tmp/composer/vendor';

    exec('rsync -a --del ' . escapeshellarg($oldVendorDirectory . '/') . ' ' . escapeshellarg($newVendorDirectory . '/'));
    $composer->getConfig()->merge(['config' => ['vendor-dir' => $newVendorDirectory]]);

    $fs = new Filesystem();
    $binaryInstaller = new BinaryInstaller($io, $composer->getConfig()->get('bin-dir'), $composer->getConfig()->get('bin-compat'), $fs);
    $im = $composer->getInstallationManager();
    $im->addInstaller(new LibraryInstaller($io, $composer, null, $fs, $binaryInstaller));
    $im->addInstaller(new PluginInstaller($io, $composer, $fs, $binaryInstaller));
    $im->addInstaller(new MetapackageInstaller($io));
  }

  /**
   * {@inheritdoc}
   */
  public function init(Event $event) {
    $composerFile = $this->composer->getConfig()->getConfigSource()->getName();
    $oldLockFile = Factory::getLockFile($composerFile);
    $newLockFile = '/tmp/composer/composer.lock';

    copy($oldLockFile, $newLockFile);
    $locker = new Locker($this->io, new JsonFile($newLockFile, null, $this->io), $this->composer->getInstallationManager(), file_get_contents($composerFile));
    $this->composer->setLocker($locker);
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
  public static function getSubscribedEvents() {
    return [
      PluginEvents::INIT => ['init', 1000],
    ];
  }

}
