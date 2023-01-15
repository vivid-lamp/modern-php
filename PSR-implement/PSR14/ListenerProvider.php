<?php

namespace PsrImplement\PSR14;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{

    /** @var callable[][] */
    protected $listeners;

    public function listen(string $eventTag, callable $listener)
    {
        $this->listeners[$eventTag][] = $listener;
    }

    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners[get_class($event)] ?? [];
    }
}
