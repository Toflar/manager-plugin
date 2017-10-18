<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;

class JsonParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse($resource, $type = null)
    {
        $configs = [];
        $json = $this->parseJsonFile($resource);

        $this->parseBundles($json, $configs);

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && 'json' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses the file and returns the configuration array.
     *
     * @param string $file The absolute file path
     *
     * @throws \InvalidArgumentException If $file is not a file
     * @throws \RuntimeException         If the file cannot be decoded
     *
     * @return array The configuration array
     */
    private function parseJsonFile($file)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("$file is not a file");
        }

        $json = json_decode(file_get_contents($file), true);

        if (null === $json) {
            throw new \RuntimeException("File $file cannot be decoded");
        }

        return $json;
    }

    /**
     * Parses the bundle array and generates config objects.
     *
     * @param array $bundles
     * @param array $configs
     *
     * @throws \RuntimeException
     */
    private function parseBundles(array $bundles, array &$configs)
    {
        foreach ($bundles as $options) {
            // Only one value given, must be class name
            if (!\is_array($options)) {
                $options = ['bundle' => $options];
            }

            if (!isset($options['bundle'])) {
                throw new \RuntimeException(
                    sprintf('Missing class name for bundle config (%s)', json_encode($options))
                );
            }

            if (isset($options['optional']) && $options['optional'] && !class_exists($options['bundle'])) {
                continue;
            }

            $config = new BundleConfig($options['bundle']);

            if (isset($options['replace'])) {
                $config->setReplace($options['replace']);
            }

            if (isset($options['development'])) {
                if (true === $options['development']) {
                    $config->setLoadInProduction(false);
                } elseif (false === $options['development']) {
                    $config->setLoadInDevelopment(false);
                }
            }

            if (isset($options['load-after'])) {
                $config->setLoadAfter($options['load-after']);
            }

            $configs[] = $config;
        }
    }
}
