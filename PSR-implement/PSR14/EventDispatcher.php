<?php

namespace PsrImplement\PSR14;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{

    /** @var ListenerProviderInterface */
    protected $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    public function dispatch(object $event)
    {
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            if (
                $event instanceof StoppableEventInterface
                && $event->isPropagationStopped()
            ) {
                return $event;
            } else {
                $listener($event);
            }
        }
        return $event;
    }
}
