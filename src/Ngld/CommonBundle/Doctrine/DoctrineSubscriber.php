<?php

namespace Ngld\CommonBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class DoctrineSubscriber implements EventSubscriber
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'preFlush',
        );
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $this->injectContainer($args);
    }

    public function preFlush(LifecycleEventArgs $args)
    {
        $this->injectContainer($args);
    }

    public function injectContainer(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        
        if ($entity instanceof ContainerAware) {
            $entity->setContainer($this->container);
        }
    }
}