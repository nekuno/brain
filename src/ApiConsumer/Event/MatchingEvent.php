<?php


namespace ApiConsumer\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MatchingEvent
 * @package ApiConsumer\Event
 */
class MatchingEvent extends Event {

    protected $data;

    /**
     * @return mixed
     */
    public function getData()
    {

        return $this->data;
    }

    public function __construct(array $data)
    {

        $this->data = $data;
    }

}
