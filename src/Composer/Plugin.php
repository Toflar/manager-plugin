<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * Constructor.
     *
     * @param Installer|null $installer
     */
    public function __construct(Installer $installer = null)
    {
        $this->installer = $installer ?: new Installer();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, as all features are provided through event listeners
    }

    /**
     * Dumps the Contao Manager plugins.
     *
     * @param Event $event
     */
    public function dumpPlugins(Event $event): void
    {
        $event->getIO()->write('<info>contao/manager-plugin:</info> Generating plugin class...');

        $this->installer->dumpPlugins(
            $event->getComposer()->getRepositoryManager()->getLocalRepository(),
            $event->getIO()
        );

        $event->getIO()->write('<info>contao/manager-plugin:</info> ...done generating plugin class');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'dumpPlugins',
            ScriptEvents::POST_UPDATE_CMD => 'dumpPlugins',
        ];
    }
}