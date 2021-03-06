<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle\Config;

use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;

class ConfigResolver implements ConfigResolverInterface
{
    use DependencyResolverTrait;

    /**
     * @var ConfigInterface[]
     */
    protected $configs = [];

    /**
     * {@inheritdoc}
     */
    public function add(ConfigInterface $config): self
    {
        $this->configs[] = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundleConfigs($development): array
    {
        $bundles = [];

        // Only add bundles which match the environment
        foreach ($this->configs as $config) {
            if (($development && $config->loadInDevelopment()) || (!$development && $config->loadInProduction())) {
                $bundles[$config->getName()] = $config;
            } else {
                unset($bundles[$config->getName()]);
            }
        }

        $loadingOrder = $this->buildLoadingOrder();
        $replaces = $this->buildReplaceMap();
        $normalizedOrder = $this->normalizeLoadingOrder($loadingOrder, $replaces);
        $resolvedOrder = $this->orderByDependencies($normalizedOrder);

        return $this->order($bundles, $resolvedOrder);
    }

    /**
     * @return array<string,string>
     */
    private function buildReplaceMap(): array
    {
        $replace = [];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            foreach ($bundle->getReplace() as $package) {
                $replace[$package] = $name;
            }
        }

        return $replace;
    }

    /**
     * @return array<string,string>
     */
    private function buildLoadingOrder(): array
    {
        $loadingOrder = [];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            $loadingOrder[$name] = [];

            foreach ($bundle->getLoadAfter() as $package) {
                $loadingOrder[$name][] = $package;
            }
        }

        return $loadingOrder;
    }

    /**
     * @return array<string,string>
     */
    private function order(array $bundles, array $ordered): array
    {
        $return = [];

        foreach ($ordered as $package) {
            if (array_key_exists($package, $bundles)) {
                $return[$package] = $bundles[$package];
            }
        }

        return $return;
    }

    /**
     * @return array<string,string>
     */
    private function normalizeLoadingOrder(array $loadingOrder, array $replace): array
    {
        foreach ($loadingOrder as $bundleName => &$loadAfter) {
            if (isset($replace[$bundleName])) {
                unset($loadingOrder[$bundleName]);
            } else {
                $this->replaceBundleNames($loadAfter, $replace);
            }
        }

        return $loadingOrder;
    }

    private function replaceBundleNames(array &$loadAfter, array $replace): void
    {
        foreach ($loadAfter as &$bundleName) {
            if (isset($replace[$bundleName])) {
                $bundleName = $replace[$bundleName];
            }
        }
    }
}
