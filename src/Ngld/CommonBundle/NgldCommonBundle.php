<?php

namespace Ngld\CommonBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ngld\CommonBundle\DependencyInjection\ContainerRef;

class NgldCommonBundle extends Bundle
{
    public function boot()
    {
        ContainerRef::set($this->container);
    }
}
