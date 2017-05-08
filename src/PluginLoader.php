<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin;

use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\Dependency\UnresolvableDependenciesException;

/**
 * Finds Contao manager plugins from Composer's installed.json.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PluginLoader
{
    use DependencyResolverTrait;

    const BUNDLE_PLUGINS = 'Contao\ManagerPlugin\Bundle\BundlePluginInterface';
    const CONFIG_PLUGINS = 'Contao\ManagerPlugin\Config\ConfigPluginInterface';
    const ROUTING_PLUGINS = 'Contao\ManagerPlugin\Routing\RoutingPluginInterface';

    /**
     * @var string
     */
    private $installedJson;

    /**
     * @var array
     */
    private $plugins;

    /**
     * Constructor.
     *
     * @param string $installedJson
     */
    public function __construct($installedJson)
    {
        $this->installedJson = $installedJson;
    }

    /**
     * Gets instances of manager plugins.
     *
     * @return array
     */
    public function getInstances()
    {
        $this->load();

        return $this->plugins;
    }

    /**
     * Gets instances of manager plugins of given type (see class constants).
     *
     * @param string $type
     * @param bool   $reverseOrder
     *
     * @return array
     */
    public function getInstancesOf($type, $reverseOrder = false)
    {
        $plugins = array_filter(
            $this->getInstances(),
            function ($plugin) use ($type) {
                return is_a($plugin, $type);
            }
        );

        return $reverseOrder ? array_reverse($plugins) : $plugins;
    }

    /**
     * @param array $plugins
     *
     * @throws UnresolvableDependenciesException
     *
     * @return array
     */
    protected function orderPlugins(array $plugins)
    {
        $this->plugins = [];

        $ordered = [];
        $dependencies = [];
        $packages = array_keys($plugins);

        // Load the manager bundle first
        if (isset($plugins['contao/manager-bundle'])) {
            array_unshift($packages, 'contao/manager-bundle');
            $packages = array_unique($packages);
        }

        // Walk through the packages
        foreach ($packages as $packageName) {
            $dependencies[$packageName] = [];

            if ($plugins[$packageName] instanceof DependentPluginInterface) {
                $dependencies[$packageName] = $plugins[$packageName]->getPackageDependencies();
            }
        }

        foreach ($this->orderByDependencies($dependencies) as $packageName) {
            $ordered[$packageName] = $plugins[$packageName];
        }

        return $ordered;
    }

    /**
     * Loads plugins from Composer's installed.json.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws UnresolvableDependenciesException
     */
    private function load()
    {
        if (null !== $this->plugins) {
            return;
        }

        if (!is_file($this->installedJson)) {
            throw new \InvalidArgumentException(
                sprintf('Composer installed.json was not found at "%s"', $this->installedJson)
            );
        }

        $plugins = [];
        $json = json_decode(file_get_contents($this->installedJson), true);

        if (null === $json) {
            throw new \RuntimeException(sprintf('File "%s" cannot be decoded', $this->installedJson));
        }

        foreach ($json as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
                if (!class_exists($package['extra']['contao-manager-plugin'])) {
                    throw new \RuntimeException(
                        sprintf('Contao Manager Plugin "%s" was not found.', $package['extra']['contao-manager-plugin'])
                    );
                }

                $plugins[$package['name']] = new $package['extra']['contao-manager-plugin']();
            }
        }

        $this->plugins = $this->orderPlugins($plugins);

        // Instantiate a global plugin to load AppBundle or other customizations
        $appPlugin = '\ContaoManagerPlugin';
        if (class_exists($appPlugin)) {
            $this->plugins['app'] = new $appPlugin();
        }
    }
}
