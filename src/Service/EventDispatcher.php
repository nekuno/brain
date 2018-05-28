<?php

namespace Service;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher as BaseDispatcher;
use Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventDispatcher extends BaseDispatcher
{
    public function dispatch($eventName, Event $event = null)
    {
        return parent::dispatch($eventName, $event);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        parent::addSubscriber($subscriber);
    }

    public function dispatchError(\Exception $e, $process)
    {
        return parent::dispatch(\AppEvents::EXCEPTION_ERROR, new ExceptionEvent($e, $process));
    }

    public function dispatchWarning(\Exception $e, $process)
    {
        return parent::dispatch(\AppEvents::EXCEPTION_WARNING, new ExceptionEvent($e, $process));
    }

    public function dispatchUrlUnprocessed(\Exception $e, $process)
    {
        return parent::dispatch(\AppEvents::URL_UNPROCESSED, new ExceptionEvent($e, $process));
    }
}