<?php

namespace Ngld\CommonBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerRef
{
    protected static $container;

    public static function set(ContainerInterface $container)
    {
        self::$container = $container;
    }

    public static function get()
    {
        return self::$container;
    }
}
