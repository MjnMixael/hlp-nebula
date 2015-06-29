<?php

namespace Ngld\CommonBundle\Twig;

class HelpersExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('parse_url', 'parse_url'),
        );
    }

    public function getName()
    {
        return 'ngld_common_helpers';
    }
}