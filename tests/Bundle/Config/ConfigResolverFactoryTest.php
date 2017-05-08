<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Config;

use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverInterface;

class ConfigResolverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $factory = new ConfigResolverFactory();

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory', $factory);
    }

    public function testCreate()
    {
        $factory = new ConfigResolverFactory();
        $resolver = $factory->create();

        $this->assertInstanceOf(ConfigResolverInterface::class, $resolver);
    }
}
